<?php

namespace App\Services;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\MarksScopeState;
use App\Models\Subject;
use App\Services\ResultCalculation\BoardResultCalculator;
use Illuminate\Support\Facades\DB;

class ResultMarksDraftService
{
    public function __construct(
        private ResultMarksScopeService $scopes,
        private ResultMarksPopulationService $population,
        private ResultLifecycleEventService $events,
        private BoardResultCalculator $calculator,
        private ResultComponentMarksValidationService $componentMarksValidation,
    ) {}

    public function save(array $input, CultivationAdmin $actor, ?string $ipAddress = null, bool $legacy = false): array
    {
        $this->scopes->assertActor($actor);
        $base = $this->baseScope($input);
        $subject = Subject::find($base['subjectId']);
        $exam = Exam::find($base['examId']);
        if (!$subject || !$exam) throw ResultLifecycleException::missing();

        $students = $this->population->resolve(
            $base,
            $subject,
            $actor,
            $this->nullableId($input['optionalGroupId'] ?? null),
            (string) ($input['gender'] ?? 'all'),
        );
        $byId = $students->keyBy(fn ($student) => (int) $student->id);
        $submittedIds = array_map('intval', $input['studentId'] ?? []);
        $rows = [];
        foreach ($submittedIds as $index => $studentId) {
            if (!$byId->has($studentId)) {
                throw ResultLifecycleException::forbidden(
                    'One or more submitted students are outside the authorized marks scope.'
                );
            }
            $student = $byId->get($studentId);
            $actualSection = $this->nullableId($student->sectionName);
            if ($base['groupId'] !== null && $actualSection !== $base['groupId']) {
                throw ResultLifecycleException::invalid('InvalidMarksIdentity', 'Student section does not match the requested scope.');
            }
            $scope = $base;
            $scope['groupId'] = $actualSection;
            $rows[] = [
                'student' => $student,
                'scope' => $scope,
                'raw' => [
                    'subjectMarks' => $this->markAt($input['cqMarks'] ?? [], $index),
                    'objectMarks' => $this->markAt($input['mcqMarks'] ?? [], $index),
                    'practicalMarks' => $this->markAt($input['practical'] ?? [], $index),
                ],
            ];
            $this->assertRawMarks(end($rows)['raw'], $subject);
        }

        if ($rows === []) {
            throw ResultLifecycleException::forbidden('No submitted student belongs to the authorized marks scope.');
        }
        $partitions = collect($rows)->groupBy(fn ($row) => $this->scopes->key($row['scope']))->sortKeys();
        $existingStates = [];
        foreach ($partitions as $scopeKey => $partition) {
            $scope = $partition->first()['scope'];
            $existingStates[$scopeKey] = $this->scopes->find($scope);
            $this->scopes->assertNotPublished($scope);
            if ($existingStates[$scopeKey]?->status === MarksScopeState::STATUS_CONFIRMED) {
                throw ResultLifecycleException::conflict('ScopeAlreadyConfirmed', 'Confirmed marks are read-only. Reopen the scope first.');
            }
            $this->assertRevision($input, $scopeKey, $existingStates[$scopeKey], $legacy);
        }

        return DB::transaction(function () use ($partitions, $existingStates, $input, $subject, $exam, $actor, $ipAddress) {
            $locked = [];
            foreach ($partitions as $scopeKey => $partition) {
                $scope = $partition->first()['scope'];
                $state = $this->scopes->lockOrCreate($scope);
                $this->scopes->assertNotPublished($scope);
                if ($state->status !== MarksScopeState::STATUS_DRAFT) {
                    throw ResultLifecycleException::conflict('ScopeAlreadyConfirmed', 'Confirmed marks are read-only.');
                }
                $this->assertLockedRevision($input, $scopeKey, $state, $existingStates[$scopeKey] === null);
                $locked[$scopeKey] = $state;
            }

            $correlation = $this->events->correlationUuid();
            $changedTotal = 0;
            $unchangedTotal = 0;
            $revisions = [];
            foreach ($partitions as $scopeKey => $partition) {
                $state = $locked[$scopeKey];
                $scope = $partition->first()['scope'];
                $studentIds = $partition->pluck('student.id')->map(fn ($id) => (string) $id)->all();
                $existing = Marksheet::query()
                    ->where('sessionId', (string) $scope['sessionId'])
                    ->where('classId', (string) $scope['classId'])
                    ->where('examId', (string) $scope['examId'])
                    ->where('subjectId', (string) $scope['subjectId'])
                    ->when($scope['groupId'] === null, fn ($q) => $q->whereNull('groupId'), fn ($q) => $q->where('groupId', (string) $scope['groupId']))
                    ->whereIn('studentId', $studentIds)->get()->keyBy(fn ($mark) => (int) $mark->studentId);

                $changes = [];
                foreach ($partition as $row) {
                    $old = $existing->get((int) $row['student']->id);
                    $before = $old ? $this->rawState($old) : null;
                    if ($before === $row['raw']) {
                        $unchangedTotal++;
                        continue;
                    }
                    $derived = $this->derived($row, $old, $exam, $subject);
                    $mark = $old ?: new Marksheet();
                    $mark->fill([
                        'studentId' => (string) $row['student']->id,
                        'sessionId' => (string) $scope['sessionId'],
                        'classId' => (string) $scope['classId'],
                        'groupId' => $scope['groupId'] === null ? null : (string) $scope['groupId'],
                        'examId' => (string) $scope['examId'],
                        'subjectId' => (string) $scope['subjectId'],
                    ] + $row['raw'] + $derived);
                    if (!$old) {
                        $mark->entered_by = $actor->id;
                        $mark->entered_by_role = $this->events->actorRole($actor);
                    }
                    $mark->teacher_id = $actor->id;
                    $mark->updated_by = $actor->id;
                    $mark->updated_by_role = $this->events->actorRole($actor);
                    $mark->save();
                    $changes[] = [
                        'studentId' => (string) $row['student']->id,
                        'before' => $before,
                        'after' => $row['raw'] + $derived,
                    ];
                    $changedTotal++;
                }

                if ($changes !== []) {
                    $beforeState = ['status' => $state->status, 'revision' => (int) $state->revision];
                    $state->revision = (int) $state->revision + 1;
                    $state->save();
                    $afterState = ['status' => $state->status, 'revision' => (int) $state->revision];
                    $this->events->append(
                        $existing->isEmpty() ? 'draft_marks_created' : 'draft_marks_updated',
                        $scope, $actor, $correlation, $beforeState, $afterState, $changes, null, $ipAddress
                    );
                }
                $revisions[$scopeKey] = (int) $state->revision;
            }

            return [
                'success' => true,
                'changed_student_count' => $changedTotal,
                'unchanged_student_count' => $unchangedTotal,
                'skipped_student_count' => 0,
                'affected_scopes' => array_keys($revisions),
                'current_revisions' => $revisions,
            ];
        }, 3);
    }

    private function baseScope(array $input): array
    {
        foreach (['sessionId', 'classId', 'examId', 'subjectId'] as $key) {
            if (!isset($input[$key]) || (int) $input[$key] <= 0) {
                throw ResultLifecycleException::missing();
            }
        }
        return [
            'sessionId' => (int) $input['sessionId'],
            'classId' => (int) $input['classId'],
            'groupId' => $this->nullableId($input['groupId'] ?? null),
            'examId' => (int) $input['examId'],
            'subjectId' => (int) $input['subjectId'],
        ];
    }

    private function assertRevision(array $input, string $key, ?MarksScopeState $state, bool $legacy): void
    {
        $submitted = $this->submittedRevision($input, $key);
        if ($submitted === null && !($legacy && $state === null)) {
            throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'A current scope revision is required.');
        }
        if ($state && $submitted !== (int) $state->revision) {
            throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'Marks changed since this form was loaded. Reload and try again.');
        }
    }

    private function assertLockedRevision(array $input, string $key, MarksScopeState $state, bool $wasNew): void
    {
        $submitted = $this->submittedRevision($input, $key);
        if (!($wasNew && $submitted === null) && $submitted !== (int) $state->revision) {
            throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'Marks changed since this form was loaded. Reload and try again.');
        }
    }

    private function submittedRevision(array $input, string $key): ?int
    {
        $value = $input['scope_revisions'][$key] ?? $input['scope_revision'] ?? null;
        return is_numeric($value) ? (int) $value : null;
    }

    private function markAt(array $values, int $index): ?float
    {
        return $this->componentMarksValidation->normalize($values[$index] ?? null);
    }

    private function nullableId(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function rawState(Marksheet $mark): array
    {
        return [
            'subjectMarks' => $mark->subjectMarks === null ? null : (float) $mark->subjectMarks,
            'objectMarks' => $mark->objectMarks === null ? null : (float) $mark->objectMarks,
            'practicalMarks' => $mark->practicalMarks === null ? null : (float) $mark->practicalMarks,
        ];
    }

    private function derived(array $row, ?Marksheet $old, Exam $exam, Subject $subject): array
    {
        if (!collect($row['raw'])->contains(fn ($value) => $value !== null)) {
            return ['totalMarks' => null, 'laterGrade' => null, 'gradePoint' => null];
        }
        $result = $this->calculator->calculateSubject($row['student'], $exam, [
            'id' => $old?->id,
            'subjectId' => $subject->id,
        ] + $row['raw'], $subject);
        return [
            'totalMarks' => $result->obtainedMarks,
            'laterGrade' => $result->letterGrade,
            'gradePoint' => $result->gradePoint,
        ];
    }

    private function assertRawMarks(array $raw, Subject $subject): void
    {
        $this->componentMarksValidation->assertWithinMaximums($raw, $subject);
    }
}

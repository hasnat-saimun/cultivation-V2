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

class ResultMarksConfirmationService
{
    public function __construct(
        private ResultMarksScopeService $scopes,
        private ResultMarksPopulationService $population,
        private ResultLifecycleEventService $events,
        private BoardResultCalculator $calculator,
    ) {}

    public function confirm(array $input, CultivationAdmin $actor, ?string $ipAddress = null): array
    {
        $this->scopes->assertActor($actor);
        $scope = $this->scope($input);
        $state = $this->scopes->find($scope);
        if (!$state) throw ResultLifecycleException::missing();
        $revision = $this->revision($input);
        if ($state->status === MarksScopeState::STATUS_CONFIRMED) {
            if ($revision === (int) $state->revision) return $this->response($scope, $state);
            throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'The submitted scope revision is stale.');
        }
        if ($revision !== (int) $state->revision) {
            throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'Marks changed since this form was loaded.');
        }
        $this->scopes->assertNotPublished($scope);

        $subject = Subject::find($scope['subjectId']);
        $exam = Exam::find($scope['examId']);
        if (!$subject || !$exam) throw ResultLifecycleException::missing();
        $confirmWithBlanks = (string) ($input['confirm_blank_marks'] ?? '0') === '1';
        $students = $this->population->resolve(
            $scope,
            $subject,
            $actor,
            $this->nullableId($input['optionalGroupId'] ?? null),
            'all',
            true,
        );
        if ($students->isEmpty()) {
            throw ResultLifecycleException::invalid('ScopeIncomplete', 'No applicable active students were found for this subject scope.');
        }
        $preflight = $this->verifyComplete($scope, $students, $exam, $subject, false, $confirmWithBlanks);

        return DB::transaction(function () use ($scope, $revision, $preflight, $students, $exam, $subject, $actor, $ipAddress, $confirmWithBlanks) {
            $state = $this->scopes->query($scope)->lockForUpdate()->first();
            if (!$state) throw ResultLifecycleException::missing();
            $this->scopes->assertNotPublished($scope);
            if ($state->status === MarksScopeState::STATUS_CONFIRMED) {
                if ((int) $state->revision === $revision) return $this->response($scope, $state);
                throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'The submitted scope revision is stale.');
            }
            if ($state->status !== MarksScopeState::STATUS_DRAFT) {
                throw ResultLifecycleException::conflict('LifecycleTransitionConflict', 'The scope is not Draft.');
            }
            if ((int) $state->revision !== $revision) {
                throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'Marks changed during confirmation.');
            }
            $locked = $this->verifyComplete($scope, $students, $exam, $subject, true, $confirmWithBlanks);
            if ($locked['fingerprint'] !== $preflight['fingerprint']) {
                throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'Marks changed during confirmation.');
            }

            $before = ['status' => $state->status, 'revision' => (int) $state->revision];
            $state->status = MarksScopeState::STATUS_CONFIRMED;
            $state->confirmed_by = $actor->id;
            $state->confirmed_at = now();
            $state->save();
            $this->events->append(
                'subject_confirmed',
                $scope,
                $actor,
                $this->events->correlationUuid(),
                $before,
                [
                    'status' => $state->status,
                    'revision' => (int) $state->revision,
                    'confirmed_by' => (int) $actor->id,
                    'confirmed_at' => $state->confirmed_at?->toISOString(),
                ],
                [
                    'student_count' => $students->count(),
                    'marks_fingerprint' => $locked['fingerprint'],
                    'blank_override' => $confirmWithBlanks,
                ],
                null,
                $ipAddress,
            );
            return $this->response($scope, $state);
        }, 3);
    }

    private function verifyComplete(
        array $scope,
        $students,
        Exam $exam,
        Subject $subject,
        bool $lock = false,
        bool $allowBlankConfirmation = false
    ): array
    {
        $query = Marksheet::query()
            ->where('sessionId', (string) $scope['sessionId'])
            ->where('classId', (string) $scope['classId'])
            ->where('examId', (string) $scope['examId'])
            ->where('subjectId', (string) $scope['subjectId'])
            ->when($scope['groupId'] === null, fn ($q) => $q->whereNull('groupId'), fn ($q) => $q->where('groupId', (string) $scope['groupId']))
            ->whereIn('studentId', $students->pluck('id')->all())
            ->orderBy('studentId');
        if ($lock) $query->lockForUpdate();
        $marks = $query->get()->keyBy(fn ($mark) => (int) $mark->studentId);
        $errors = [];
        $blankFieldCount = 0;
        $blankStudentIds = [];
        $fingerprintRows = [];
        foreach ($students as $student) {
            $mark = $marks->get((int) $student->id);
            if (!$mark) {
                $errors[] = ['studentId' => (int) $student->id, 'issue' => 'missing_marks_row'];
                continue;
            }

            $subjectBlank = $mark->subjectMarks === null;
            $mcqBlank = $mark->objectMarks === null;
            $practicalBlank = $mark->practicalMarks === null;
            $studentBlankCount = (int) $subjectBlank + (int) $mcqBlank + (int) $practicalBlank;
            if ($studentBlankCount > 0) {
                $blankFieldCount += $studentBlankCount;
                $blankStudentIds[] = (int) $student->id;
            }

            $result = $this->calculator->calculateSubject($student, $exam, $mark, $subject);
            if ($result->status === 'Incomplete') {
                if (!$allowBlankConfirmation) {
                    $errors[] = ['studentId' => (int) $student->id, 'issue' => 'incomplete_components'];
                    continue;
                }
            }
            if (!$this->cacheMatches($mark, $result)
                && !($allowBlankConfirmation && $result->status === 'Incomplete')) {
                $errors[] = ['studentId' => (int) $student->id, 'issue' => 'compatibility_cache_mismatch'];
                continue;
            }
            $fingerprintRows[] = [
                (int) $student->id,
                $mark->subjectMarks === null ? null : (float) $mark->subjectMarks,
                $mark->objectMarks === null ? null : (float) $mark->objectMarks,
                $mark->practicalMarks === null ? null : (float) $mark->practicalMarks,
            ];
        }

        $hasBlankRelatedError = collect($errors)->contains(fn ($error) => in_array($error['issue'] ?? '', ['missing_marks_row', 'incomplete_components'], true));
        if ($hasBlankRelatedError && !$allowBlankConfirmation) {
            $blankStudentIds = array_values(array_unique($blankStudentIds));
            $sampleStudentIds = array_slice($blankStudentIds, 0, 5);
            throw ResultLifecycleException::invalid(
                'BlankMarksConfirmationRequired',
                'Some mark fields are blank. Confirm with blank-mark override or save as draft.',
                [
                    'requires_override' => true,
                    'blank_student_count' => count($blankStudentIds),
                    'blank_field_count' => $blankFieldCount,
                    'sample_student_ids' => $sampleStudentIds,
                ]
            );
        }

        if ($errors !== []) {
            $summary = collect($errors)->countBy('issue')->map(fn ($count, $issue) => ['issue' => $issue, 'count' => $count])->values()->all();
            throw ResultLifecycleException::invalid(
                'ScopeIncomplete',
                'The subject scope is incomplete and cannot be confirmed.',
                $summary,
            );
        }
        return ['fingerprint' => hash('sha256', json_encode($fingerprintRows, JSON_PRESERVE_ZERO_FRACTION))];
    }

    private function cacheMatches(Marksheet $mark, $result): bool
    {
        return $this->sameNumber($mark->totalMarks, $result->obtainedMarks)
            && (string) $mark->laterGrade === (string) $result->letterGrade
            && $this->sameNumber($mark->gradePoint, $result->gradePoint);
    }

    private function sameNumber(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) return $left === null && $right === null;
        return abs((float) $left - (float) $right) < 0.00001;
    }

    private function scope(array $input): array
    {
        foreach (['sessionId', 'classId', 'examId', 'subjectId'] as $key) {
            if (!isset($input[$key]) || (int) $input[$key] <= 0) throw ResultLifecycleException::missing();
        }
        return [
            'sessionId' => (int) $input['sessionId'],
            'classId' => (int) $input['classId'],
            'groupId' => $this->nullableId($input['groupId'] ?? null),
            'examId' => (int) $input['examId'],
            'subjectId' => (int) $input['subjectId'],
        ];
    }

    private function revision(array $input): int
    {
        if (!isset($input['scope_revision']) || !is_numeric($input['scope_revision'])) {
            throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'A current scope revision is required.');
        }
        return (int) $input['scope_revision'];
    }

    private function nullableId(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function response(array $scope, MarksScopeState $state): array
    {
        return [
            'success' => true,
            'scope' => $scope,
            'status' => $state->status,
            'revision' => (int) $state->revision,
            'confirmed_by' => $state->confirmed_by,
            'confirmed_at' => $state->confirmed_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Services;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Models\Marksheet;
use App\Models\MarksScopeState;
use App\Models\ResultPublish;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherResultWorkspaceService
{
    public function __construct(
        private TeacherResultExamEligibilityService $exams,
        private MarksEntryAuthorizationService $authorization,
        private ResultMarksPopulationService $population,
        private ResultMarksScopeService $scopeStates,
        private TeacherAssignmentAcademicScopeService $academicScope,
    ) {}

    public function assignments(CultivationAdmin $teacher): Collection
    {
        $assignments = DB::table('teacher_class_subjects as tcs')
            ->join('class_manages as cls', 'cls.id', '=', 'tcs.class_id')
            ->join('subjects as sub', 'sub.id', '=', 'tcs.subject_id')
            ->join('session_manages as ses', 'ses.id', '=', 'tcs.session_id')
            ->leftJoin('section_manages as sec', 'sec.id', '=', 'tcs.section_id')
            ->leftJoin('departments as dep', 'dep.id', '=', 'tcs.group_id')
            ->where('tcs.teacher_id', $teacher->id)
            ->whereNotNull('tcs.subject_id')
            ->where(fn ($query) => $query->whereNull('tcs.section_id')->orWhereNotNull('sec.id'))
            ->where(fn ($query) => $query->whereNull('tcs.group_id')->orWhereNotNull('dep.id'))
            ->select([
                'tcs.teacher_id',
                'tcs.session_id', 'ses.session as session_name',
                'tcs.class_id', 'cls.className as class_name',
                'tcs.section_id', 'sec.section as section_name',
                'tcs.group_id', 'dep.departmentName as group_name',
                'tcs.subject_id', 'sub.subjectName as subject_name',
                'tcs.gender_scope',
            ])
            ->groupBy([
                'tcs.teacher_id', 'tcs.session_id', 'ses.session', 'tcs.class_id', 'cls.className',
                'tcs.section_id', 'sec.section', 'tcs.group_id', 'dep.departmentName',
                'tcs.subject_id', 'sub.subjectName', 'tcs.gender_scope',
            ])
            ->orderBy('cls.className')->orderBy('sub.subjectName')
            ->get();
        $assignments = $assignments
            ->map(fn ($assignment) => $this->decorateAssignment($assignment, $teacher))
            ->groupBy(fn ($assignment) => $this->effectiveScopeKey(
                (int) ($assignment->teacher_id ?? $teacher->id),
                $this->nullableId($assignment->session_id) ?? 0,
                $this->nullableId($assignment->class_id) ?? 0,
                $this->nullableId($assignment->section_id),
                $this->nullableId($assignment->group_id),
                $this->nullableId($assignment->subject_id) ?? 0,
            ))
            ->map(function (Collection $matching) {
                $assignment = clone $matching->first();
                $genders = $matching->pluck('gender_scope')->map(
                    fn ($gender) => $this->normalizeGender((string) $gender)
                )->unique()->values();
                $assignment->gender_scope = $genders->contains('all')
                    || ($genders->contains('male') && $genders->contains('female'))
                        ? 'all'
                        : (string) $genders->first();
                $assignment->gender_scope_label = ucfirst($assignment->gender_scope);

                return $assignment;
            })->values();
        $exams = $this->exams->eligibleForClasses($assignments->pluck('class_id')->all());

        return $assignments
            ->map(function ($assignment) use ($exams) {
                $assignment->exams = $exams->filter(fn ($exam) =>
                    (string) $exam->className === TeacherResultExamEligibilityService::ALL_CLASSES_VALUE
                    || (int) $exam->className === (int) $assignment->class_id
                )->values();
                return $assignment;
            })
            ->filter(fn ($assignment) => $assignment->exams->isNotEmpty())
            ->values();
    }

    /** @return array<string,int|string|null> */
    public function normalizeScope(array $input): array
    {
        foreach (['sessionId', 'classId', 'subjectId'] as $key) {
            if (! isset($input[$key]) || ! is_numeric($input[$key]) || (int) $input[$key] <= 0) {
                throw ResultLifecycleException::missing();
            }
        }

        return [
            'sessionId' => (int) $input['sessionId'],
            'classId' => (int) $input['classId'],
            'groupId' => $this->nullableId($input['groupId'] ?? null),
            'optionalGroupId' => $this->nullableId($input['optionalGroupId'] ?? null),
            'subjectId' => (int) $input['subjectId'],
            'examId' => isset($input['examId']) && is_numeric($input['examId']) ? (int) $input['examId'] : 0,
            'gender' => $this->normalizeGender((string) ($input['gender'] ?? 'all')),
        ];
    }

    /** @return array{scope:array<string,int|string|null>,exam:\App\Models\Exam,subject:Subject} */
    public function authorize(CultivationAdmin $teacher, array $input): array
    {
        try {
            $scope = $this->normalizeScope($input);
        } catch (ResultLifecycleException $exception) {
            $this->diagnostic('invalid_scope', $teacher, $input);
            throw $exception;
        }
        if (!$teacher->isTeacher()) {
            $this->diagnostic('actor_not_teacher', $teacher, $scope);
            throw ResultLifecycleException::forbidden();
        }
        if (! $this->authorization->canEnterMarksFor(
            $teacher,
            $scope['classId'],
            $scope['subjectId'],
            $scope['groupId'],
            $scope['optionalGroupId'],
            $scope['sessionId'],
        )) {
            $this->diagnostic('assignment_lookup_rejected', $teacher, $scope);
            throw ResultLifecycleException::forbidden();
        }

        $subject = Subject::find($scope['subjectId']);
        if (! $subject) {
            $this->diagnostic('subject_missing', $teacher, $scope);
            throw ResultLifecycleException::missing();
        }

        try {
            $exam = $this->exams->resolve($teacher, $scope, $scope['examId']);
        } catch (ResultLifecycleException $exception) {
            $this->diagnostic('exam_class_incompatible_or_missing', $teacher, $scope);
            throw $exception;
        }
        return compact('scope', 'exam', 'subject');
    }

    private function diagnostic(string $reason, CultivationAdmin $teacher, array $scope): void
    {
        if (!app()->environment(['local', 'testing'])) return;
        Log::info('teacher_result_authorization_rejected', [
            'reason' => $reason,
            'authenticated_teacher_id' => (int) $teacher->id,
            'is_teacher' => $teacher->isTeacher(),
            'session_id' => isset($scope['sessionId']) && is_numeric($scope['sessionId']) ? (int) $scope['sessionId'] : null,
            'class_id' => isset($scope['classId']) && is_numeric($scope['classId']) ? (int) $scope['classId'] : null,
            'section_id' => isset($scope['groupId']) && is_numeric($scope['groupId']) ? (int) $scope['groupId'] : null,
            'department_id' => isset($scope['optionalGroupId']) && is_numeric($scope['optionalGroupId']) ? (int) $scope['optionalGroupId'] : null,
            'subject_id' => isset($scope['subjectId']) && is_numeric($scope['subjectId']) ? (int) $scope['subjectId'] : null,
            'exam_id' => isset($scope['examId']) && is_numeric($scope['examId']) ? (int) $scope['examId'] : null,
        ]);
    }

    /** @return array<string,mixed> */
    public function workspace(CultivationAdmin $teacher, array $input): array
    {
        $authorized = $this->authorize($teacher, $input);
        $scope = $authorized['scope'];
        $students = $this->population->resolve(
            $scope,
            $authorized['subject'],
            $teacher,
            $scope['optionalGroupId'],
            $scope['gender'],
        );
        $students = $this->orderedUniqueStudents($students);
        $sectionIds = $students->pluck('sectionName')->map(fn ($id) => $this->nullableId($id))->unique()->values();
        $marks = Marksheet::query()
            ->where('sessionId', (string) $scope['sessionId'])
            ->where('classId', (string) $scope['classId'])
            ->where('examId', (string) $scope['examId'])
            ->where('subjectId', (string) $scope['subjectId'])
            ->when($scope['groupId'] === null,
                function ($query) use ($sectionIds) {
                    $positive = $sectionIds->filter()->map(fn ($id) => (string) $id)->all();
                    $query->where(function ($groups) use ($positive) {
                        $groups->whereNull('groupId');
                        if ($positive !== []) $groups->orWhereIn('groupId', $positive);
                    });
                },
                fn ($query) => $query->where('groupId', (string) $scope['groupId']))
            ->whereIn('studentId', $students->pluck('id')->all())
            ->get()
            ->keyBy(fn ($mark) => (int) $mark->studentId);
        $stateScopes = $sectionIds->isEmpty()
            ? collect([$scope['groupId']])
            : $sectionIds;
        $states = $stateScopes->mapWithKeys(function ($sectionId) use ($scope) {
            $stateScope = $this->stateScope($scope);
            $stateScope['groupId'] = $sectionId;
            $key = $sectionId === null ? 'class' : 'section:'.$sectionId;
            return [$key => $this->scopeStates->find($stateScope)];
        });
        $scopeRevisions = $states->map(fn ($state) => (int) ($state?->revision ?? 1))->all();
        $state = $states->count() === 1 ? $states->first() : null;
        $published = ResultPublish::query()
            ->where('status', ResultPublish::STATUS_PUBLISHED)
            ->where('sessionId', (string) $scope['sessionId'])
            ->where('classId', (string) $scope['classId'])
            ->where('examId', (string) $scope['examId'])
            ->where(function ($query) use ($scope) {
                $query->whereNull('groupId');
                if ($scope['groupId'] !== null) $query->orWhere('groupId', (string) $scope['groupId']);
            })
            ->exists();
        $displayStatus = $published
            ? 'Published'
            : ($states->contains(fn ($item) => $item?->status === MarksScopeState::STATUS_CONFIRMED)
                ? 'Confirmed'
                : 'Draft');

        return $authorized + [
            'labels' => $this->labels($teacher, $scope, $authorized['subject'], $authorized['exam']),
            'students' => $students,
            'marks' => $marks,
            'state' => $state,
            'status' => $displayStatus,
            'revision' => (int) ($state?->revision ?? 1),
            'editable' => ! $published && $states->every(fn ($item) => ($item?->status ?? MarksScopeState::STATUS_DRAFT) === MarksScopeState::STATUS_DRAFT),
            'confirmable' => ! $published && $states->count() === 1 && $state?->status === MarksScopeState::STATUS_DRAFT,
            'scopeRevisions' => $scopeRevisions,
        ];
    }

    public function recentActivity(CultivationAdmin $teacher): Collection
    {
        return DB::table('result_lifecycle_events')
            ->where('actor_id', $teacher->id)
            ->where('actor_role', 'teacher')
            ->latest('created_at')->limit(5)
            ->get(['action', 'created_at'])
            ->map(fn ($event) => [
                'label' => match ((string) $event->action) {
                    'draft_marks_created' => 'Draft marks saved',
                    'draft_marks_updated' => 'Draft marks updated',
                    'subject_confirmed' => 'Subject result confirmed',
                    default => 'Result activity recorded',
                },
                'occurred_at' => $event->created_at,
            ]);
    }

    /** @return array<string,int|string|null> */
    public function serviceInput(array $input, array $scope): array
    {
        return array_merge($input, [
            'sessionId' => $scope['sessionId'],
            'classId' => $scope['classId'],
            'groupId' => $scope['groupId'],
            'optionalGroupId' => $scope['optionalGroupId'],
            'examId' => $scope['examId'],
            'subjectId' => $scope['subjectId'],
            'gender' => $scope['gender'],
        ]);
    }

    private function labels(CultivationAdmin $teacher, array $scope, Subject $subject, object $exam): array
    {
        $row = DB::table('session_manages as ses')
            ->crossJoin('class_manages as cls')
            ->leftJoin('section_manages as sec', fn ($join) => $join->where('sec.id', $scope['groupId'] ?? 0))
            ->leftJoin('departments as dep', fn ($join) => $join->where('dep.id', $scope['optionalGroupId'] ?? 0))
            ->where('ses.id', $scope['sessionId'])->where('cls.id', $scope['classId'])
            ->select('ses.session', 'cls.className', 'sec.section', 'dep.departmentName')->first();
        $section = $scope['groupId'] === null ? 'All Sections' : $row?->section;
        $department = $scope['optionalGroupId'] === null ? 'All Departments' : $row?->departmentName;
        if (!$row || !$section || !$department) {
            $this->diagnostic('display_relation_missing', $teacher, $scope);
            throw ResultLifecycleException::missing();
        }
        return [
            'session' => $row->session, 'class' => $row->className,
            'section' => $section, 'department' => $department,
            'subject' => $subject->subjectName, 'exam' => $exam->examName,
            'gender' => ucfirst($scope['gender']),
        ];
    }

    private function normalizeGender(string $value): string
    {
        return match (strtolower(trim($value))) {
            'male', '1', 'm' => 'male',
            'female', '2', 'f' => 'female',
            default => 'all',
        };
    }

    private function stateScope(array $scope): array
    {
        return [
            'sessionId' => $scope['sessionId'],
            'classId' => $scope['classId'],
            'groupId' => $scope['groupId'],
            'examId' => $scope['examId'],
            'subjectId' => $scope['subjectId'],
        ];
    }

    private function nullableId(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function decorateAssignment(object $assignment, CultivationAdmin $teacher): object
    {
        $assignment->teacher_id = (int) ($assignment->teacher_id ?? $teacher->id);
        $assignment->session_id = $this->nullableId($assignment->session_id);
        $assignment->class_id = $this->nullableId($assignment->class_id);
        $assignment->section_id = $this->nullableId($assignment->section_id);
        $assignment->group_id = $this->nullableId($assignment->group_id);
        $assignment->subject_id = $this->nullableId($assignment->subject_id);
        $assignment->gender_scope = $this->normalizeGender((string) ($assignment->gender_scope ?? 'all'));
        $assignment->gender_scope_label = match ($assignment->gender_scope) {
            'male' => 'Male',
            'female' => 'Female',
            default => 'All',
        };

        $assignment->session_label = $assignment->session_name ?: 'Session unavailable';
        $assignment->class_label = $assignment->class_name ?: 'Class unavailable';
        $assignment->section_label = $assignment->section_id === null
            ? 'All Sections'
            : ($assignment->section_name ?: 'Section unavailable');
        $assignment->department_label = $assignment->group_id === null
            ? ($this->academicScope->requiresGroupName($assignment->class_name) ? 'All Departments' : 'Not Applicable')
            : ($assignment->group_name ?: 'Department unavailable');
        $assignment->subject_label = $assignment->subject_name ?: 'Subject unavailable';

        return $assignment;
    }

    private function effectiveScopeKey(
        int $teacherId,
        int $sessionId,
        int $classId,
        ?int $sectionId,
        ?int $departmentId,
        int $subjectId,
    ): string {
        return implode(':', [
            $teacherId,
            $sessionId,
            $classId,
            $sectionId === null ? 'n' : $sectionId,
            $departmentId === null ? 'n' : $departmentId,
            $subjectId,
        ]);
    }

    private function orderedUniqueStudents(Collection $students): Collection
    {
        return $students
            ->unique(fn ($student) => (int) $student->getKey())
            ->sort(function ($left, $right) {
                $leftGender = $this->studentGenderRank($left->gender ?? null);
                $rightGender = $this->studentGenderRank($right->gender ?? null);
                if ($leftGender !== $rightGender) return $leftGender <=> $rightGender;

                $leftRoll = $this->studentRoll($left->rollNumber ?? null);
                $rightRoll = $this->studentRoll($right->rollNumber ?? null);
                if ($leftRoll !== null && $rightRoll !== null && $leftRoll !== $rightRoll) {
                    return $leftRoll <=> $rightRoll;
                }
                if (($leftRoll === null) !== ($rightRoll === null)) {
                    return $leftRoll === null ? 1 : -1;
                }

                return (int) $left->getKey() <=> (int) $right->getKey();
            })
            ->values();
    }

    private function studentGenderRank(mixed $gender): int
    {
        return match ($this->normalizeGender((string) $gender)) {
            'male' => 0,
            'female' => 1,
            default => 2,
        };
    }

    private function studentRoll(mixed $roll): ?int
    {
        $roll = trim((string) $roll);
        return $roll !== '' && ctype_digit($roll) ? (int) $roll : null;
    }

}

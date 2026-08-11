<?php

namespace App\Services;

use App\Models\AcademicAttendance;
use App\Models\newAdmission;
use App\Services\Students\StudentGenderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class AcademicAttendanceService
{
    public const MAX_WORKING_DAYS = 366;

    public function __construct(private StudentGenderService $gender) {}

    public function students(array $scope, ?iterable $populationIds = null): Builder
    {
        $query = newAdmission::query()
            ->select('new_admissions.*')
            ->where('sessName', $scope['session_id'])
            ->where('className', $scope['class_id'])
            ->where('sectionName', $scope['section_id'])
            ->when($scope['department_id'] !== null, fn (Builder $q) => $q->where('departmentName', $scope['department_id']));

        if ($populationIds !== null) {
            $ids = collect($populationIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
            $ids->isEmpty() ? $query->whereRaw('1 = 0') : $query->whereIn('new_admissions.id', $ids);
        }

        return $this->applyPopulationOrder(
            $this->gender->apply($query, $scope['gender'] ?? StudentGenderService::ALL)
        );
    }

    public function selectForTranscript(Builder $query, int $examId): Builder
    {
        foreach (['working_days' => 'academic_working_days', 'present_days' => 'academic_present_days', 'absent_days' => 'academic_absent_days'] as $column => $alias) {
            $query->addSelect([$alias => AcademicAttendance::query()
                ->select($column)
                ->where('exam_id', $examId)
                ->whereColumn('session_id', 'new_admissions.sessName')
                ->whereColumn('class_id', 'new_admissions.className')
                ->whereColumn('section_id', 'new_admissions.sectionName')
                ->whereColumn('student_id', 'new_admissions.id')
                ->where(function (Builder $attendance) {
                    $attendance->whereColumn('department_id', 'new_admissions.departmentName')
                        ->orWhere(function (Builder $nullDepartment) {
                            $nullDepartment->whereNull('department_id')->whereNull('new_admissions.departmentName');
                        });
                })
                ->limit(1)]);
        }

        return $query;
    }

    public function records(array $scope, Collection $students): Collection
    {
        return AcademicAttendance::query()
            ->where('exam_id', $scope['exam_id'])
            ->where('session_id', $scope['session_id'])
            ->where('class_id', $scope['class_id'])
            ->where('section_id', $scope['section_id'])
            ->whereIn('student_id', $students->pluck('id'))
            ->get()->keyBy('student_id');
    }

    public function saveOne(array $scope, array $row, ?int $actorId): AcademicAttendance
    {
        return $this->saveBulk($scope, [$row], $actorId)->first();
    }

    public function saveBulk(array $scope, array $rows, ?int $actorId): Collection
    {
        if ($rows === []) {
            throw ValidationException::withMessages(['students' => 'At least one student attendance row is required.']);
        }

        $validated = collect($rows)->map(fn (array $row, int $index) => $this->validateRow($row, $index));
        $ids = $validated->pluck('student_id');
        if ($ids->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['students' => 'A student may appear only once in the attendance request.']);
        }

        $allowed = $this->students($scope)->whereIn('id', $ids)->get(['id', 'departmentName'])->keyBy('id');
        $outside = $ids->diff($allowed->keys());
        if ($outside->isNotEmpty()) {
            throw ValidationException::withMessages(['students' => 'One or more students are outside the selected academic scope.']);
        }

        return DB::transaction(function () use ($scope, $validated, $actorId, $allowed) {
            return $validated->map(function (array $row) use ($scope, $actorId, $allowed) {
                $student = $allowed->get($row['student_id']);
                $studentScope = $scope;
                $studentScope['department_id'] = $scope['department_id'] ?? $this->nullablePositive($student?->departmentName);
                $identity = $this->identity($studentScope, $row['student_id']);
                $existing = AcademicAttendance::where('scope_key', $identity['scope_key'])->lockForUpdate()->first();
                $values = $identity + [
                    'working_days' => $row['working_days'],
                    'present_days' => $row['present_days'],
                    'absent_days' => $row['absent_days'],
                    'updated_by' => $actorId,
                ];
                if (! $existing) $values['created_by'] = $actorId;

                return AcademicAttendance::updateOrCreate(['scope_key' => $identity['scope_key']], $values);
            });
        });
    }

    public function synchronize(int $workingDays, int $editedValue, string $editedField): array
    {
        if ($workingDays < 1 || $workingDays > self::MAX_WORKING_DAYS || $editedValue < 0 || $editedValue > $workingDays) {
            throw ValidationException::withMessages([$editedField => 'Attendance days must be within Working Days.']);
        }
        return $editedField === 'absent_days'
            ? ['working_days' => $workingDays, 'present_days' => $workingDays - $editedValue, 'absent_days' => $editedValue]
            : ['working_days' => $workingDays, 'present_days' => $editedValue, 'absent_days' => $workingDays - $editedValue];
    }

    public function forTranscript(newAdmission $student, int $examId): ?array
    {
        if (array_key_exists('academic_working_days', $student->getAttributes())) {
            return $student->academic_working_days === null ? null : [
                'workingDays' => (int) $student->academic_working_days,
                'presentDays' => (int) $student->academic_present_days,
                'absentDays' => (int) $student->academic_absent_days,
            ];
        }

        $session = $this->positive($student->sessName);
        $class = $this->positive($student->className);
        $section = $this->positive($student->sectionName);
        if (! $session || ! $class || ! $section) return null;

        $record = AcademicAttendance::where('scope_key', $this->scopeKey(
            $examId, $session, $class, $section, $this->nullablePositive($student->departmentName), (int) $student->id
        ))->first();

        return $record ? [
            'workingDays' => $record->working_days,
            'presentDays' => $record->present_days,
            'absentDays' => $record->absent_days,
        ] : null;
    }

    public function forTranscripts(iterable $students, int $examId): Collection
    {
        $students = collect($students);
        if ($students->isEmpty()) return collect();

        $records = AcademicAttendance::query()
            ->where('exam_id', $examId)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('scope_key');

        return $students->mapWithKeys(function (newAdmission $student) use ($examId, $records) {
            $session = $this->positive($student->sessName);
            $class = $this->positive($student->className);
            $section = $this->positive($student->sectionName);
            if (! $session || ! $class || ! $section) return [(int) $student->id => null];
            $record = $records->get($this->scopeKey($examId, $session, $class, $section, $this->nullablePositive($student->departmentName), (int) $student->id));
            return [(int) $student->id => $record ? [
                'workingDays' => $record->working_days,
                'presentDays' => $record->present_days,
                'absentDays' => $record->absent_days,
            ] : null];
        });
    }

    private function validateRow(array $row, int $index): array
    {
        $validator = Validator::make($row, [
            'student_id' => ['required', 'integer', 'min:1'],
            'working_days' => ['required', 'integer', 'min:1', 'max:'.self::MAX_WORKING_DAYS],
            'present_days' => ['required', 'integer', 'min:0'],
            'absent_days' => ['required', 'integer', 'min:0'],
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages(collect($validator->errors()->toArray())->mapWithKeys(
                fn ($messages, $field) => ["students.$index.$field" => $messages]
            )->all());
        }
        $data = array_map('intval', $validator->validated());
        if ($data['present_days'] > $data['working_days'] || $data['absent_days'] > $data['working_days']) {
            throw ValidationException::withMessages(["students.$index" => 'Present and Absent cannot exceed Working Days.']);
        }
        if ($data['present_days'] + $data['absent_days'] !== $data['working_days']) {
            throw ValidationException::withMessages(["students.$index" => 'Present plus Absent must equal Working Days.']);
        }
        return $data;
    }

    private function identity(array $scope, int $studentId): array
    {
        return [
            'exam_id' => $scope['exam_id'], 'session_id' => $scope['session_id'],
            'class_id' => $scope['class_id'], 'section_id' => $scope['section_id'],
            'department_id' => $scope['department_id'], 'student_id' => $studentId,
            'scope_key' => $this->scopeKey($scope['exam_id'], $scope['session_id'], $scope['class_id'], $scope['section_id'], $scope['department_id'], $studentId),
        ];
    }

    private function scopeKey(int $exam, int $session, int $class, int $section, ?int $department, int $student): string
    {
        return hash('sha256', implode(':', [$exam, $session, $class, $section, $department ?? 0, $student]));
    }

    private function positive(mixed $value): ?int { return is_numeric($value) && (int) $value > 0 ? (int) $value : null; }
    private function nullablePositive(mixed $value): ?int { return $this->positive($value); }

    private function applyPopulationOrder(Builder $query): Builder
    {
        $driver = $query->getConnection()->getDriverName();
        $numeric = static function (Builder $ordered, string $column) use ($driver): void {
            if ($driver === 'sqlite') {
                $valid = "TRIM(COALESCE({$column}, '')) <> '' AND TRIM(COALESCE({$column}, '')) NOT GLOB '*[^0-9]*'";
                $ordered->orderByRaw("CASE WHEN {$valid} THEN 0 ELSE 1 END")
                    ->orderByRaw("CASE WHEN {$valid} THEN CAST({$column} AS INTEGER) ELSE NULL END")
                    ->orderByRaw("TRIM(COALESCE({$column}, ''))");
                return;
            }

            $valid = "TRIM(COALESCE({$column}, '')) REGEXP '^[0-9]+$'";
            $ordered->orderByRaw("CASE WHEN {$valid} THEN 0 ELSE 1 END")
                ->orderByRaw("CASE WHEN {$valid} THEN CAST({$column} AS UNSIGNED) ELSE NULL END")
                ->orderByRaw("TRIM(COALESCE({$column}, ''))");
        };

        $query->reorder()
            ->orderByRaw("CASE LOWER(TRIM(CAST(new_admissions.gender AS CHAR))) WHEN '1' THEN 0 WHEN 'male' THEN 0 WHEN 'm' THEN 0 WHEN '2' THEN 1 WHEN 'female' THEN 1 WHEN 'f' THEN 1 ELSE 2 END");
        $numeric($query, 'new_admissions.rollNumber');
        $numeric($query, 'new_admissions.stdId');

        return $query->orderBy('new_admissions.id');
    }
}

<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherSubjectAssignmentAvailabilityService
{
    public const GENDER_ALL = 'all';
    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';

    /**
     * Normalize assignment context IDs so null/empty/0 are treated consistently.
     */
    public function normalizeContext(array $input): array
    {
        return [
            'session_id' => $this->normalizeNullableId($input['session_id'] ?? null),
            'class_id' => $this->normalizeRequiredId($input['class_id'] ?? null),
            'section_id' => $this->normalizeNullableId($input['section_id'] ?? null),
            'group_id' => $this->normalizeNullableId($input['group_id'] ?? null),
            'subject_id' => $this->normalizeRequiredId($input['subject_id'] ?? null),
        ];
    }

    public function normalizeGenderScope($genderScope): ?string
    {
        $scope = strtolower(trim((string) $genderScope));

        if ($scope === '') {
            return self::GENDER_ALL;
        }

        if (in_array($scope, ['1', 'm'], true)) {
            return self::GENDER_MALE;
        }

        if (in_array($scope, ['2', 'f'], true)) {
            return self::GENDER_FEMALE;
        }

        return in_array($scope, [self::GENDER_ALL, self::GENDER_MALE, self::GENDER_FEMALE], true)
            ? $scope
            : null;
    }

    public function availableGenderScopes(array $context, int|array|null $excludeRowIds = null): array
    {
        $context = $this->normalizeContext($context);
        $existingScopes = $this->existingGenderScopesForContext($context, $excludeRowIds);

        if (in_array(self::GENDER_ALL, $existingScopes, true)) {
            return [];
        }

        $hasMale = in_array(self::GENDER_MALE, $existingScopes, true);
        $hasFemale = in_array(self::GENDER_FEMALE, $existingScopes, true);

        if ($hasMale && $hasFemale) {
            return [];
        }

        if ($hasMale) {
            return [self::GENDER_FEMALE];
        }

        if ($hasFemale) {
            return [self::GENDER_MALE];
        }

        return [self::GENDER_ALL, self::GENDER_MALE, self::GENDER_FEMALE];
    }

    public function canAssignGender(array $context, string $genderScope, int|array|null $excludeRowIds = null): bool
    {
        $normalizedGender = $this->normalizeGenderScope($genderScope);
        if ($normalizedGender === null) {
            return false;
        }

        return in_array($normalizedGender, $this->availableGenderScopes($context, $excludeRowIds), true);
    }

    /**
     * Lock rows for the context inside a transaction to reduce overlap race conditions.
     */
    public function lockContextRows(array $context, int|array|null $excludeRowIds = null): Collection
    {
        $context = $this->normalizeContext($context);

        return $this->baseContextQuery($context, $excludeRowIds)
            ->lockForUpdate()
            ->get(['id', 'gender_scope']);
    }

    /**
     * Return subjects with allowed genders for the selected context.
     */
    public function subjectsWithAvailability(array $baseContext, ?string $selectedGender = null): Collection
    {
        $sessionId = $this->normalizeNullableId($baseContext['session_id'] ?? null);
        $classId = $this->normalizeRequiredId($baseContext['class_id'] ?? null);
        $sectionId = $this->normalizeNullableId($baseContext['section_id'] ?? null);
        $groupId = $this->normalizeNullableId($baseContext['group_id'] ?? null);

        $subjectRows = DB::table('subjects')
            ->select('id', 'subjectName')
            ->orderBy('subjectName', 'asc')
            ->get();

        $coverage = $this->baseAcademicScopeQuery($sessionId, $classId, $sectionId, $groupId)
            ->get(['subject_id', 'gender_scope'])
            ->groupBy(fn ($row) => (int) $row->subject_id)
            ->map(fn ($rows) => $rows
                ->map(fn ($row) => $this->normalizeGenderScope($row->gender_scope))
                ->filter()
                ->unique()
                ->values()
                ->all());

        $selectedGender = $selectedGender === null ? null : $this->normalizeGenderScope($selectedGender);

        return $subjectRows->map(function ($subject) use ($coverage) {
            return [
                'id' => (int) $subject->id,
                'name' => (string) $subject->subjectName,
                'available_gender_scopes' => $this->availableFromCoverage(
                    $coverage->get((int) $subject->id, [])
                ),
            ];
        })->filter(function (array $row) use ($selectedGender) {
            return $selectedGender === null
                ? !empty($row['available_gender_scopes'])
                : in_array($selectedGender, $row['available_gender_scopes'], true);
        })->values();
    }

    private function existingGenderScopesForContext(array $context, int|array|null $excludeRowIds = null): array
    {
        $rows = $this->baseContextQuery($context, $excludeRowIds)
            ->get(['gender_scope']);

        $scopes = [];
        foreach ($rows as $row) {
            $scope = $this->normalizeGenderScope($row->gender_scope);
            if ($scope === null) {
                continue;
            }
            $scopes[] = $scope;
        }

        return array_values(array_unique($scopes));
    }

    private function baseContextQuery(array $context, int|array|null $excludeRowIds = null): Builder
    {
        $query = $this->baseAcademicScopeQuery(
            $context['session_id'],
            $context['class_id'],
            $context['section_id'],
            $context['group_id'],
        )
            ->where('subject_id', $context['subject_id']);

        $excluded = collect(is_array($excludeRowIds) ? $excludeRowIds : [$excludeRowIds])
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        if ($excluded !== []) {
            $query->whereNotIn('id', $excluded);
        }

        return $query;
    }

    public function canGenderScopesOverlap(string $left, string $right): bool
    {
        $left = $this->normalizeGenderScope($left);
        $right = $this->normalizeGenderScope($right);
        if ($left === null || $right === null) {
            return true;
        }

        return $left === self::GENDER_ALL || $right === self::GENDER_ALL || $left === $right;
    }

    private function baseAcademicScopeQuery(?int $sessionId, int $classId, ?int $sectionId, ?int $groupId): Builder
    {
        $query = DB::table('teacher_class_subjects')
            ->where('class_id', $classId);

        if (Schema::hasColumn('teacher_class_subjects', 'session_id')) {
            if ($sessionId === null) {
                $query->whereNull('session_id');
            } else {
                $query->where('session_id', $sessionId);
            }
        }

        if ($sectionId === null) {
            $query->whereNull('section_id');
        } else {
            $query->where('section_id', $sectionId);
        }

        $query->where(function (Builder $groups) use ($groupId) {
            if ($groupId === null) {
                // All Departments intersects every concrete department.
                $groups->whereNull('group_id')->orWhereNotNull('group_id');
                return;
            }

            // A concrete department intersects its own rows and All Departments.
            $groups->whereNull('group_id')->orWhere('group_id', $groupId);
        });

        return $query;
    }

    private function availableFromCoverage(array $existingScopes): array
    {
        if (in_array(self::GENDER_ALL, $existingScopes, true)) {
            return [];
        }

        $hasMale = in_array(self::GENDER_MALE, $existingScopes, true);
        $hasFemale = in_array(self::GENDER_FEMALE, $existingScopes, true);

        if ($hasMale && $hasFemale) {
            return [];
        }
        if ($hasMale) {
            return [self::GENDER_FEMALE];
        }
        if ($hasFemale) {
            return [self::GENDER_MALE];
        }

        return [self::GENDER_ALL, self::GENDER_MALE, self::GENDER_FEMALE];
    }

    private function normalizeNullableId($value): ?int
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '' || $stringValue === '0') {
            return null;
        }

        if (!ctype_digit($stringValue)) {
            return null;
        }

        $id = (int) $stringValue;
        return $id > 0 ? $id : null;
    }

    private function normalizeRequiredId($value): int
    {
        $stringValue = trim((string) $value);
        return ctype_digit($stringValue) ? (int) $stringValue : 0;
    }
}

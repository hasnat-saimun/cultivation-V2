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

        return in_array($scope, [self::GENDER_ALL, self::GENDER_MALE, self::GENDER_FEMALE], true)
            ? $scope
            : null;
    }

    public function availableGenderScopes(array $context, ?int $excludeRowId = null): array
    {
        $context = $this->normalizeContext($context);
        $existingScopes = $this->existingGenderScopesForContext($context, $excludeRowId);

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

    public function canAssignGender(array $context, string $genderScope, ?int $excludeRowId = null): bool
    {
        $normalizedGender = $this->normalizeGenderScope($genderScope);
        if ($normalizedGender === null) {
            return false;
        }

        return in_array($normalizedGender, $this->availableGenderScopes($context, $excludeRowId), true);
    }

    /**
     * Lock rows for the context inside a transaction to reduce overlap race conditions.
     */
    public function lockContextRows(array $context, ?int $excludeRowId = null): Collection
    {
        $context = $this->normalizeContext($context);

        return $this->baseContextQuery($context, $excludeRowId)
            ->lockForUpdate()
            ->get(['id', 'gender_scope']);
    }

    /**
     * Return subjects with allowed genders for the selected context.
     */
    public function subjectsWithAvailability(array $baseContext): Collection
    {
        $sessionId = $this->normalizeNullableId($baseContext['session_id'] ?? null);
        $classId = $this->normalizeRequiredId($baseContext['class_id'] ?? null);
        $sectionId = $this->normalizeNullableId($baseContext['section_id'] ?? null);
        $groupId = $this->normalizeNullableId($baseContext['group_id'] ?? null);

        $subjectRows = DB::table('subjects')
            ->select('id', 'subjectName')
            ->orderBy('subjectName', 'asc')
            ->get();

        return $subjectRows->map(function ($subject) use ($sessionId, $classId, $sectionId, $groupId) {
            $context = [
                'session_id' => $sessionId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'group_id' => $groupId,
                'subject_id' => (int) $subject->id,
            ];

            return [
                'id' => (int) $subject->id,
                'name' => (string) $subject->subjectName,
                'available_gender_scopes' => $this->availableGenderScopes($context),
            ];
        })->filter(function (array $row) {
            return !empty($row['available_gender_scopes']);
        })->values();
    }

    private function existingGenderScopesForContext(array $context, ?int $excludeRowId = null): array
    {
        $rows = $this->baseContextQuery($context, $excludeRowId)
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

    private function baseContextQuery(array $context, ?int $excludeRowId = null): Builder
    {
        $query = DB::table('teacher_class_subjects')
            ->where('class_id', $context['class_id'])
            ->where('subject_id', $context['subject_id']);

        if (Schema::hasColumn('teacher_class_subjects', 'session_id')) {
            if ($context['session_id'] === null) {
                $query->whereNull('session_id');
            } else {
                $query->where(function ($q) use ($context) {
                    $q->whereNull('session_id')->orWhere('session_id', $context['session_id']);
                });
            }
        }

        if ($context['section_id'] === null) {
            $query->whereNull('section_id');
        } else {
            $query->where('section_id', $context['section_id']);
        }

        if ($context['group_id'] === null) {
            $query->whereNull('group_id');
        } else {
            $query->where('group_id', $context['group_id']);
        }

        if ($excludeRowId !== null) {
            $query->where('id', '!=', $excludeRowId);
        }

        return $query;
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

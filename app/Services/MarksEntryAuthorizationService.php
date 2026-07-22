<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MarksEntryAuthorizationService
{
    public function normalizeGenderScope(?string $scope): ?string
    {
        if ($scope === null) {
            return 'all';
        }

        $normalized = strtolower(trim((string) $scope));
        if ($normalized === '') {
            return 'all';
        }

        return in_array($normalized, ['all', 'male', 'female'], true) ? $normalized : null;
    }

    public function teacherCanSelectGender(
        ?CultivationAdmin $user,
        int $classId,
        ?int $sectionId,
        ?int $optionalGroupId,
        ?int $subjectId,
        string $requestedGender
    ): bool {
        if (!$user || !$user->isTeacher()) {
            return true;
        }

        $map = $this->teacherDepartmentGenderMap($user, $classId, $sectionId, $optionalGroupId, $subjectId);
        if (!$map['has_assignments']) {
            return false;
        }

        if ($requestedGender === 'all') {
            return true;
        }

        $requestedScope = $this->requestedGenderToScope($requestedGender);
        if ($requestedScope === null) {
            return $map['wildcard_all'];
        }

        if ($map['wildcard_all']) {
            return true;
        }

        return in_array($requestedScope, $map['all_scopes'], true);
    }

    public function applyTeacherStudentAuthorizationFilters(
        Builder $query,
        ?CultivationAdmin $user,
        int $classId,
        ?int $sectionId,
        ?int $optionalGroupId,
        ?int $subjectId,
        string $requestedGender = 'all'
    ): bool {
        if (!$user || !$user->isTeacher()) {
            return true;
        }

        $map = $this->teacherDepartmentGenderMap($user, $classId, $sectionId, $optionalGroupId, $subjectId);
        if (!$map['has_assignments']) {
            return false;
        }

        if ($optionalGroupId !== null) {
            $this->applyScopedGenderFilter($query, $map['all_scopes'], $requestedGender);
            return true;
        }

        if ($map['wildcard_all']) {
            return true;
        }

        $wildcardScopes = $map['wildcard_scopes'];
        $departmentScopes = $map['department_scopes'];

        $query->where(function (Builder $outer) use ($wildcardScopes, $departmentScopes, $requestedGender) {
            $hasAnyClause = false;

            if (!empty($wildcardScopes)) {
                $hasAnyClause = true;
                $outer->where(function (Builder $clause) use ($wildcardScopes, $requestedGender) {
                    $this->applyScopedGenderFilter($clause, $wildcardScopes, $requestedGender);
                });
            }

            foreach ($departmentScopes as $departmentId => $scopes) {
                if (empty($scopes)) {
                    continue;
                }

                $method = $hasAnyClause ? 'orWhere' : 'where';
                $hasAnyClause = true;

                $outer->{$method}(function (Builder $clause) use ($departmentId, $scopes, $requestedGender) {
                    $clause->where('departmentName', (int) $departmentId);
                    $this->applyScopedGenderFilter($clause, $scopes, $requestedGender);
                });
            }

            if (!$hasAnyClause) {
                $outer->whereRaw('1 = 0');
            }
        });

        return true;
    }

    public function authorizedClassIds(?CultivationAdmin $user): array
    {
        if (!$user || !$user->isTeacher()) {
            return [];
        }

        $ids = [];

        if (!empty($user->primary_class_id)) {
            $ids[] = (int) $user->primary_class_id;
        }

        $ids = array_merge($ids, array_map('intval', $user->access_class_array ?? []));

        if (Schema::hasTable('teacher_classes')) {
            $pivotClassIds = DB::table('teacher_classes')
                ->where('teacher_id', (int) $user->id)
                ->whereNotNull('class_id')
                ->pluck('class_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->toArray();
            $ids = array_merge($ids, $pivotClassIds);
        }

        if (Schema::hasTable('teacher_class_subjects')) {
            $compositeClassIds = DB::table('teacher_class_subjects')
                ->where('teacher_id', (int) $user->id)
                ->whereNotNull('class_id')
                ->pluck('class_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->toArray();
            $ids = array_merge($ids, $compositeClassIds);
        }

        $ids = array_values(array_unique(array_filter($ids, function ($id) {
            return $id > 0;
        })));

        return $ids;
    }

    public function authorizedSubjectsForMarks(
        ?CultivationAdmin $user,
        int $classId,
        ?int $sectionId = null,
        ?int $optionalGroupId = null,
        ?int $sessionId = null
    ): Collection {
        if (!$user || !$user->isTeacher()) {
            return collect();
        }

        $allowedClassIds = $this->authorizedClassIds($user);
        if (!empty($allowedClassIds) && !in_array($classId, $allowedClassIds, true)) {
            return collect();
        }

        $subjectIds = [];
        $compositeCount = 0;
        $legacyCount = 0;

        if (Schema::hasTable('teacher_class_subjects')) {
            $compositeQuery = DB::table('teacher_class_subjects as tcs')
                ->join('subjects as s', 's.id', '=', 'tcs.subject_id')
                ->join('class_manages as cm', 'cm.id', '=', 'tcs.class_id')
                ->leftJoin('section_manages as sm', 'sm.id', '=', 'tcs.section_id')
                ->leftJoin('departments as d', 'd.id', '=', 'tcs.group_id')
                ->where('tcs.teacher_id', (int) $user->id)
                ->where('tcs.class_id', $classId)
                ->whereNotNull('tcs.subject_id')
                ->where(function ($query) {
                    // Ignore stale section references (non-null section must exist).
                    $query->whereNull('tcs.section_id')->orWhereNotNull('sm.id');
                })
                ->where(function ($query) {
                    // Ignore stale group references (non-null group must exist).
                    $query->whereNull('tcs.group_id')->orWhereNotNull('d.id');
                })
                ->where(function ($query) use ($sectionId) {
                    if ($sectionId === null) {
                        $query->whereNull('tcs.section_id')->orWhereNotNull('sm.id');
                        return;
                    }

                    $query->whereNull('tcs.section_id')->orWhere('tcs.section_id', $sectionId);
                })
                ->where(function ($query) use ($optionalGroupId) {
                    if ($optionalGroupId === null) {
                        $query->whereNull('tcs.group_id')->orWhereNotNull('d.id');
                        return;
                    }

                    $query->whereNull('tcs.group_id')->orWhere('tcs.group_id', $optionalGroupId);
                });

            $compositeSubjectIds = $compositeQuery
                ->distinct()
                ->pluck('tcs.subject_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->toArray();

            $compositeCount = count($compositeSubjectIds);

            if (!empty($compositeSubjectIds)) {
                $subjectIds = array_merge($subjectIds, $compositeSubjectIds);
            }
        }

        // Composite precedence is evaluated for the selected context only.
        if (empty($subjectIds) && Schema::hasTable('teacher_subjects')) {
            $legacyQuery = DB::table('teacher_subjects as ts')
                ->join('subjects as s', 's.id', '=', 'ts.subject_id')
                ->where('ts.teacher_id', (int) $user->id)
                ->whereNotNull('ts.subject_id');

            if (Schema::hasColumn('subjects', 'assign_class')) {
                $legacyQuery->where(function ($query) use ($classId) {
                    $query
                        ->whereNull('s.assign_class')
                        ->orWhere('s.assign_class', '')
                        ->orWhere('s.assign_class', '0')
                        ->orWhere('s.assign_class', (string) $classId)
                        ->orWhere('s.assign_class', 'like', $classId . ',%')
                        ->orWhere('s.assign_class', 'like', '%,' . $classId . ',%')
                        ->orWhere('s.assign_class', 'like', '%,' . $classId);
                });
            }

            if (Schema::hasTable('teacher_classes')) {
                $legacyQuery->whereExists(function ($query) use ($user, $classId) {
                    $query->selectRaw('1')
                        ->from('teacher_classes as tc')
                        ->whereColumn('tc.teacher_id', 'ts.teacher_id')
                        ->where('tc.class_id', $classId)
                        ->where('tc.teacher_id', (int) $user->id);
                });
            }

            if ($sectionId !== null && Schema::hasTable('teacher_sections')) {
                $hasAnyTeacherSectionRows = DB::table('teacher_sections as tsec')
                    ->where('tsec.teacher_id', (int) $user->id)
                    ->where(function ($scope) use ($classId) {
                        $scope->whereNull('tsec.class_id')->orWhere('tsec.class_id', $classId);
                    })
                    ->exists();

                if ($hasAnyTeacherSectionRows) {
                    $legacyQuery->whereExists(function ($query) use ($user, $sectionId, $classId) {
                        $query->selectRaw('1')
                            ->from('teacher_sections as tsec')
                            ->join('section_manages as sm2', 'sm2.id', '=', 'tsec.section_id')
                            ->whereColumn('tsec.teacher_id', 'ts.teacher_id')
                            ->where(function ($scope) use ($classId) {
                                $scope->whereNull('tsec.class_id')->orWhere('tsec.class_id', $classId);
                            })
                            ->where('tsec.section_id', $sectionId)
                            ->where('tsec.teacher_id', (int) $user->id);
                    });
                }
            }

            $legacyRows = $legacyQuery
                ->select('ts.subject_id')
                ->distinct()
                ->get();

            $legacyCount = $legacyRows->count();
            $legacySubjectIds = $legacyRows->pluck('subject_id')->map(function ($id) {
                return (int) $id;
            })->all();

            $subjectIds = array_merge($subjectIds, $legacySubjectIds);
        }

        $subjectIds = array_values(array_unique(array_filter($subjectIds, function ($id) {
            return $id > 0;
        })));

        $this->logDebug(
            $user,
            $classId,
            $sectionId,
            $optionalGroupId,
            $compositeCount,
            $legacyCount,
            count($subjectIds)
        );

        if (empty($subjectIds)) {
            return collect();
        }

        return Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('subjectName')
            ->get(['id', 'subjectName']);
    }

    public function canEnterMarksFor(
        ?CultivationAdmin $user,
        int $classId,
        int $subjectId,
        ?int $sectionId = null,
        ?int $optionalGroupId = null,
        ?int $sessionId = null
    ): bool {
        if (!$user || !$user->isTeacher()) {
            return true;
        }

        $allowed = $this->authorizedSubjectsForMarks($user, $classId, $sectionId, $optionalGroupId, $sessionId);

        return $allowed->pluck('id')->contains($subjectId);
    }

    public function hasAnyMarksAssignment(?CultivationAdmin $user): bool
    {
        if (!$user || !$user->isTeacher()) {
            return true;
        }

        $teacherId = (int) $user->id;
        if ($teacherId <= 0) {
            return false;
        }

        if (Schema::hasTable('teacher_class_subjects')) {
            if (DB::table('teacher_class_subjects')->where('teacher_id', $teacherId)->whereNotNull('subject_id')->exists()) {
                return true;
            }
        }

        if (Schema::hasTable('teacher_subjects')) {
            if (DB::table('teacher_subjects')->where('teacher_id', $teacherId)->whereNotNull('subject_id')->exists()) {
                return true;
            }
        }

        return false;
    }

    private function logDebug(
        ?CultivationAdmin $user,
        int $classId,
        ?int $sectionId,
        ?int $optionalGroupId,
        int $compositeCount,
        int $legacyCount,
        int $authorizedCount
    ): void {
        if (!app()->environment(['local', 'testing'])) {
            return;
        }

        Log::info('marks_subject_authorization', [
            'session_admin_id' => app(CultivationAdminResolver::class)->currentSessionAdminId(),
            'resolved_admin_id' => $user ? (int) $user->id : null,
            'selected_class_id' => $classId,
            'selected_section_id' => $sectionId,
            'selected_group_id' => $optionalGroupId,
            'composite_assignment_count' => $compositeCount,
            'legacy_assignment_count' => $legacyCount,
            'authorized_subject_count' => $authorizedCount,
        ]);
    }

    private function teacherDepartmentGenderMap(
        CultivationAdmin $user,
        int $classId,
        ?int $sectionId,
        ?int $optionalGroupId,
        ?int $subjectId
    ): array {
        $rows = collect();

        if (Schema::hasTable('teacher_class_subjects')) {
            $rows = DB::table('teacher_class_subjects as tcs')
                ->leftJoin('section_manages as sm', 'sm.id', '=', 'tcs.section_id')
                ->leftJoin('departments as d', 'd.id', '=', 'tcs.group_id')
                ->where('tcs.teacher_id', (int) $user->id)
                ->where('tcs.class_id', $classId)
                ->where(function ($query) use ($subjectId) {
                    if ($subjectId === null) {
                        $query->whereNull('tcs.subject_id')->orWhereNotNull('tcs.subject_id');
                        return;
                    }

                    $query->whereNull('tcs.subject_id')->orWhere('tcs.subject_id', $subjectId);
                })
                ->where(function ($query) use ($sectionId) {
                    if ($sectionId === null) {
                        $query->whereNull('tcs.section_id')->orWhereNotNull('sm.id');
                        return;
                    }

                    $query->whereNull('tcs.section_id')->orWhere('tcs.section_id', $sectionId);
                })
                ->where(function ($query) use ($optionalGroupId) {
                    if ($optionalGroupId === null) {
                        $query->whereNull('tcs.group_id')->orWhereNotNull('d.id');
                        return;
                    }

                    $query->whereNull('tcs.group_id')->orWhere('tcs.group_id', $optionalGroupId);
                })
                ->select('tcs.group_id', 'tcs.gender_scope')
                ->get();
        }

        $wildcardScopes = [];
        $departmentScopes = [];
        foreach ($rows as $row) {
            $scope = $this->normalizeGenderScope($row->gender_scope);
            if ($scope === null) {
                continue;
            }

            $groupId = $row->group_id !== null ? (int) $row->group_id : null;

            if ($groupId === null) {
                $wildcardScopes[] = $scope;
                continue;
            }

            $departmentScopes[$groupId] = $departmentScopes[$groupId] ?? [];
            $departmentScopes[$groupId][] = $scope;
        }

        $wildcardScopes = $this->reduceScopes($wildcardScopes);
        foreach ($departmentScopes as $departmentId => $scopes) {
            $departmentScopes[$departmentId] = $this->reduceScopes($scopes);
        }

        $allScopes = $this->reduceScopes($wildcardScopes);
        foreach ($departmentScopes as $scopes) {
            $allScopes = $this->reduceScopes(array_merge($allScopes, $scopes));
        }

        $wildcardAll = in_array('all', $wildcardScopes, true);

        if ($optionalGroupId !== null) {
            $groupScopes = $departmentScopes[(int) $optionalGroupId] ?? [];
            $allScopes = $this->reduceScopes(array_merge($wildcardScopes, $groupScopes));
            $wildcardAll = in_array('all', $allScopes, true);
        }

        return [
            'has_assignments' => !empty($wildcardScopes) || !empty($departmentScopes),
            'wildcard_scopes' => $wildcardScopes,
            'department_scopes' => $departmentScopes,
            'all_scopes' => $allScopes,
            'wildcard_all' => $wildcardAll,
        ];
    }

    private function reduceScopes(array $scopes): array
    {
        $normalized = [];
        foreach ($scopes as $scope) {
            $resolved = $this->normalizeGenderScope($scope === null ? null : (string) $scope);
            if ($resolved === null) {
                continue;
            }

            $normalized[] = $resolved;
        }

        $normalized = array_values(array_unique($normalized));

        if (in_array('all', $normalized, true)) {
            return ['all'];
        }

        $ordered = [];
        if (in_array('male', $normalized, true)) {
            $ordered[] = 'male';
        }
        if (in_array('female', $normalized, true)) {
            $ordered[] = 'female';
        }

        return $ordered;
    }

    private function requestedGenderToScope(string $requestedGender): ?string
    {
        $value = strtolower(trim($requestedGender));

        if (in_array($value, ['1', 'male', 'm'], true)) {
            return 'male';
        }

        if (in_array($value, ['2', 'female', 'f'], true)) {
            return 'female';
        }

        return null;
    }

    private function applyScopedGenderFilter(Builder $query, array $authorizedScopes, string $requestedGender): void
    {
        $authorizedScopes = $this->reduceScopes($authorizedScopes);
        if (in_array('all', $authorizedScopes, true)) {
            return;
        }

        $requestedScope = $this->requestedGenderToScope($requestedGender);
        $effectiveScopes = $authorizedScopes;

        if ($requestedScope !== null) {
            if (in_array($requestedScope, $authorizedScopes, true)) {
                $effectiveScopes = [$requestedScope];
            } else {
                $query->whereRaw('1 = 0');
                return;
            }
        }

        $allowedValues = [];
        foreach ($effectiveScopes as $scope) {
            if ($scope === 'male') {
                $allowedValues = array_merge($allowedValues, ['1', 'male', 'Male', 'm', 'M']);
            } elseif ($scope === 'female') {
                $allowedValues = array_merge($allowedValues, ['2', 'female', 'Female', 'f', 'F']);
            }
        }

        $allowedValues = array_values(array_unique($allowedValues));
        if (empty($allowedValues)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('gender', $allowedValues);
    }
}

<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MarksEntryAuthorizationService
{
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
        ?int $optionalGroupId = null
    ): Collection {
        if (!$user || !$user->isTeacher()) {
            return Subject::query()
                ->orderBy('subjectName')
                ->get(['id', 'subjectName']);
        }

        $allowedClassIds = $this->authorizedClassIds($user);
        if (!empty($allowedClassIds) && !in_array($classId, $allowedClassIds, true)) {
            return collect();
        }

        $subjectIds = [];
        $compositeCount = 0;
        $legacyCount = 0;
        $hasAnyCompositeAssignments = false;

        if (Schema::hasTable('teacher_class_subjects')) {
            $hasAnyCompositeAssignments = DB::table('teacher_class_subjects')
                ->where('teacher_id', (int) $user->id)
                ->exists();

            $compositeQuery = DB::table('teacher_class_subjects')
                ->where('teacher_id', (int) $user->id)
                ->where('class_id', $classId)
                ->where(function ($query) use ($sectionId) {
                    if ($sectionId === null) {
                        $query->whereNull('section_id')->orWhereNotNull('section_id');
                        return;
                    }

                    $query->whereNull('section_id')->orWhere('section_id', $sectionId);
                })
                ->where(function ($query) use ($optionalGroupId) {
                    if ($optionalGroupId === null) {
                        $query->whereNull('group_id')->orWhereNotNull('group_id');
                        return;
                    }

                    $query->whereNull('group_id')->orWhere('group_id', $optionalGroupId);
                })
                ->whereNotNull('subject_id');

            $compositeCount = (int) (clone $compositeQuery)->count();

            $compositeSubjectIds = $compositeQuery
                ->distinct()
                ->pluck('subject_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->toArray();

            if (!empty($compositeSubjectIds)) {
                $subjectIds = array_merge($subjectIds, $compositeSubjectIds);
            }
        }

        if (!$hasAnyCompositeAssignments && Schema::hasTable('teacher_subjects')) {
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
                $legacyQuery->whereExists(function ($query) use ($user, $sectionId) {
                    $query->selectRaw('1')
                        ->from('teacher_sections as tsec')
                        ->whereColumn('tsec.teacher_id', 'ts.teacher_id')
                        ->where(function ($scope) {
                            $scope->whereNull('tsec.class_id')->orWhereNotNull('tsec.class_id');
                        })
                        ->where('tsec.section_id', $sectionId)
                        ->where('tsec.teacher_id', (int) $user->id);
                });
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
        ?int $optionalGroupId = null
    ): bool {
        if (!$user || !$user->isTeacher()) {
            return true;
        }

        $allowed = $this->authorizedSubjectsForMarks($user, $classId, $sectionId, $optionalGroupId);

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
}

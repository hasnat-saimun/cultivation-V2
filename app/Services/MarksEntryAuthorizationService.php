<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        if (Schema::hasTable('teacher_subjects')) {
            $legacySubjectIds = DB::table('teacher_subjects')
                ->where('teacher_id', (int) $user->id)
                ->whereNotNull('subject_id')
                ->pluck('subject_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->toArray();

            $subjectIds = array_merge($subjectIds, $legacySubjectIds);
        }

        if (Schema::hasTable('teacher_class_subjects')) {
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

            $compositeSubjectIds = $compositeQuery
                ->distinct()
                ->pluck('subject_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->toArray();

            $subjectIds = array_merge($subjectIds, $compositeSubjectIds);
        }

        $subjectIds = array_values(array_unique(array_filter($subjectIds, function ($id) {
            return $id > 0;
        })));

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
}

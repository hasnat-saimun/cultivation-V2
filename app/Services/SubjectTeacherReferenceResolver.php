<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SubjectTeacherReferenceResolver
{
    public function inspect(int $subjectId, array $remain, array $migrate): array
    {
        $scope = array_values(array_unique(array_merge($remain, $migrate)));

        return DB::table('teacher_subjects as ts')
            ->leftJoin('cultivation_admins as teacher', 'teacher.id', '=', 'ts.teacher_id')
            ->where('ts.subject_id', $subjectId)
            ->orderBy('ts.id')
            ->get(['ts.id', 'ts.teacher_id', 'ts.subject_id', 'teacher.adminName', 'teacher.adminUser'])
            ->map(function ($row) use ($subjectId, $remain, $migrate, $scope) {
                $known = DB::table('teacher_class_subjects')
                    ->where('teacher_id', $row->teacher_id)
                    ->where('subject_id', $subjectId)
                    ->whereNotNull('class_id')
                    ->distinct()->orderBy('class_id')->pluck('class_id')->map(fn ($id) => (int) $id)->all();
                $outside = array_values(array_diff($known, $scope));
                $inRemain = array_values(array_intersect($known, $remain));
                $inMigrate = array_values(array_intersect($known, $migrate));
                $automatic = $known && !$outside;
                $action = null;
                if ($automatic) {
                    $action = $inRemain && $inMigrate ? 'both' : ($inMigrate ? 'move' : 'keep');
                }

                return [
                    'row_id' => (int) $row->id,
                    'teacher_id' => (int) $row->teacher_id,
                    'teacher_name' => $row->adminName ?: $row->adminUser ?: 'Teacher #'.$row->teacher_id,
                    'subject_id' => (int) $row->subject_id,
                    'known_class_ids' => $known,
                    'outside_scope_class_ids' => $outside,
                    'automatic' => $automatic,
                    'action' => $action,
                ];
            })->all();
    }

    public function apply(array $inspection, int $sourceId, int $destinationId, array $manualResolutions): void
    {
        foreach ($inspection as $item) {
            $action = $item['automatic'] ? $item['action'] : ($manualResolutions[$item['row_id']] ?? null);
            if (!in_array($action, ['keep', 'move', 'both', 'scoped'], true)) {
                throw new RuntimeException('Every unresolved teacher subject reference requires an explicit resolution.');
            }
            if ($action === 'scoped') {
                if (!$item['known_class_ids'] || $item['outside_scope_class_ids']) {
                    throw new RuntimeException('Class-scoped resolution cannot be used because exact class ownership is not proven.');
                }
                $action = $item['action'];
            }

            if ($action === 'keep') {
                continue;
            }
            if ($action === 'move') {
                DB::table('teacher_subjects')->insertOrIgnore(['teacher_id' => $item['teacher_id'], 'subject_id' => $destinationId, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('teacher_subjects')->where('id', $item['row_id'])->where('subject_id', $sourceId)->delete();
                continue;
            }
            DB::table('teacher_subjects')->insertOrIgnore(['teacher_id' => $item['teacher_id'], 'subject_id' => $destinationId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}

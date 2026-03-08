<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_routines') || !Schema::hasTable('exam_routine_items') || !Schema::hasTable('class_routines') || !Schema::hasTable('class_routine_items')) {
            return;
        }

        $sourceRoutines = DB::table('exam_routines')
            ->where('status', 'class_routine')
            ->orderBy('id')
            ->get();

        foreach ($sourceRoutines as $source) {
            $exists = DB::table('class_routines')
                ->where('title', $source->title)
                ->where('assignClass', $source->assignClass)
                ->where('assignSession', $source->assignSession)
                ->where('assignSection', $source->assignSection ?? null)
                ->where('assignDepartment', $source->assignDepartment)
                ->exists();

            if ($exists) {
                continue;
            }

            $newRoutineId = DB::table('class_routines')->insertGetId([
                'title' => $source->title,
                'assignClass' => $source->assignClass,
                'assignSection' => $source->assignSection ?? null,
                'assignDepartment' => $source->assignDepartment,
                'assignSession' => $source->assignSession,
                'status' => null,
                'attachment' => null,
                'created_at' => $source->created_at,
                'updated_at' => $source->updated_at,
            ]);

            $sourceItems = DB::table('exam_routine_items')
                ->where('exam_routine_id', $source->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            foreach ($sourceItems as $entry) {
                DB::table('class_routine_items')->insert([
                    'class_routine_id' => $newRoutineId,
                    'class_day' => $entry->exam_day,
                    'start_time' => $entry->start_time,
                    'end_time' => $entry->end_time,
                    'class_time' => $entry->exam_time,
                    'subject_id' => $entry->subject_id,
                    'subject_name' => $entry->subject_name,
                    'sort_order' => (int)($entry->sort_order ?? 0),
                    'created_at' => $entry->created_at,
                    'updated_at' => $entry->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Data migration rollback is intentionally omitted.
    }
};

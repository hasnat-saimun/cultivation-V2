<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_routine_items')) {
            return;
        }

        if (Schema::hasColumn('exam_routine_items', 'subject_name') && Schema::hasColumn('exam_routine_items', 'subject_id') && Schema::hasTable('subjects')) {
            $rows = DB::table('exam_routine_items')
                ->whereNull('subject_id')
                ->whereNotNull('subject_name')
                ->select('id', 'subject_name')
                ->get();

            foreach ($rows as $row) {
                $matchedSubjectId = DB::table('subjects')
                    ->where('subjectName', $row->subject_name)
                    ->value('id');

                if (!empty($matchedSubjectId)) {
                    DB::table('exam_routine_items')
                        ->where('id', $row->id)
                        ->update(['subject_id' => $matchedSubjectId]);
                }
            }
        }

        if (Schema::hasColumn('exam_routine_items', 'subject_name')) {
            Schema::table('exam_routine_items', function (Blueprint $table) {
                $table->dropColumn('subject_name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('exam_routine_items')) {
            return;
        }

        if (!Schema::hasColumn('exam_routine_items', 'subject_name')) {
            Schema::table('exam_routine_items', function (Blueprint $table) {
                $table->string('subject_name')->nullable()->after('subject_id');
            });
        }

        if (Schema::hasColumn('exam_routine_items', 'subject_id') && Schema::hasColumn('exam_routine_items', 'subject_name') && Schema::hasTable('subjects')) {
            $rows = DB::table('exam_routine_items')
                ->whereNotNull('subject_id')
                ->select('id', 'subject_id')
                ->get();

            foreach ($rows as $row) {
                $subjectName = DB::table('subjects')
                    ->where('id', $row->subject_id)
                    ->value('subjectName');

                DB::table('exam_routine_items')
                    ->where('id', $row->id)
                    ->update(['subject_name' => $subjectName]);
            }
        }
    }
};

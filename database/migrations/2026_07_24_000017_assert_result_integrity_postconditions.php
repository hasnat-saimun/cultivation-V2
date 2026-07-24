<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['marksheets', 'marks_scope_states', 'result_lifecycle_events', 'result_publishes'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("Result Engine postcondition failed: {$table} is missing.");
            }
        }

        $invalidMarks = DB::table('marksheets')->whereNull('normalizedGroupScope')
            ->orWhereRaw("normalizedGroupScope NOT IN ('class') AND normalizedGroupScope NOT REGEXP '^section:[1-9][0-9]*$'")
            ->count();
        $invalidStates = DB::table('marks_scope_states')->whereNotIn('status', ['draft', 'confirmed'])
            ->orWhere('revision', '<', 1)->orWhereNull('normalizedGroupScope')->count();
        $invalidPublications = DB::table('result_publishes')->whereNotIn('status', ['published', 'unpublished'])
            ->orWhere('revision', '<', 1)->orWhereNull('normalizedGroupScope')->count();
        $missingStates = DB::table('marksheets as m')
            ->leftJoin('marks_scope_states as s', function ($join) {
                $join->on('s.sessionId', '=', 'm.sessionId')->on('s.classId', '=', 'm.classId')
                    ->on('s.normalizedGroupScope', '=', 'm.normalizedGroupScope')
                    ->on('s.examId', '=', 'm.examId')->on('s.subjectId', '=', 'm.subjectId');
            })->whereNull('s.id')->count();

        if ($invalidMarks || $invalidStates || $invalidPublications || $missingStates) {
            throw new RuntimeException(
                "Result Engine postconditions failed: marks={$invalidMarks}, states={$invalidStates}, ".
                "publications={$invalidPublications}, missing_states={$missingStates}."
            );
        }
    }

    public function down(): void
    {
    }
};

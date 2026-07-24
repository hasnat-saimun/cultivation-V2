<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $beforeMarks = DB::table('marksheets')->count();
        $beforePublications = DB::table('result_publishes')->count();

        DB::transaction(function () {
            DB::table('marksheets')->whereNotNull('sessionId')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')->from('session_manages')
                        ->whereRaw('session_manages.id=CAST(TRIM(marksheets.sessionId) AS UNSIGNED)');
                })
                ->orderBy('id')->eachById(function ($row) {
                    $matches = DB::table('session_manages')->where('session', trim((string) $row->sessionId))->pluck('id');
                    if ($matches->count() !== 1) {
                        throw new RuntimeException('Ambiguous legacy marksheets.sessionId encountered during normalization.');
                    }
                    DB::table('marksheets')->where('id', $row->id)->update(['sessionId' => (string) $matches->first()]);
                });

            foreach (['studentId', 'sessionId', 'classId', 'examId', 'subjectId'] as $column) {
                DB::statement("UPDATE marksheets SET {$column}=TRIM({$column})");
            }
            DB::statement("UPDATE marksheets SET groupId=NULL WHERE groupId IS NULL OR TRIM(groupId) IN ('','0')");
            DB::statement("UPDATE marksheets SET groupId=CAST(CAST(TRIM(groupId) AS UNSIGNED) AS CHAR) WHERE groupId IS NOT NULL");

            foreach (['examId', 'sessionId', 'classId'] as $column) {
                DB::statement("UPDATE result_publishes SET {$column}=TRIM({$column})");
            }
            DB::statement("UPDATE result_publishes SET groupId=NULL WHERE groupId IS NULL OR TRIM(groupId) IN ('','0')");
            DB::statement("UPDATE result_publishes SET groupId=CAST(CAST(TRIM(groupId) AS UNSIGNED) AS CHAR) WHERE groupId IS NOT NULL");
        });

        if ($beforeMarks !== DB::table('marksheets')->count() || $beforePublications !== DB::table('result_publishes')->count()) {
            throw new RuntimeException('Result identifier normalization changed table row counts.');
        }
    }

    public function down(): void
    {
        // Canonicalization is intentionally not reversed: original equivalent spellings are unknowable.
    }
};

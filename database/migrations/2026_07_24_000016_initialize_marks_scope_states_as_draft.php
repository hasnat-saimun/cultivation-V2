<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $scopes = DB::table('marksheets')
            ->select(['sessionId', 'classId', 'groupId', 'examId', 'subjectId'])
            ->distinct()
            ->orderBy('sessionId')->orderBy('classId')->orderBy('groupId')->orderBy('examId')->orderBy('subjectId')
            ->get();

        foreach ($scopes->chunk(500) as $chunk) {
            $now = now();
            DB::table('marks_scope_states')->insertOrIgnore($chunk->map(fn ($scope) => [
                'sessionId' => $scope->sessionId,
                'classId' => $scope->classId,
                'groupId' => $scope->groupId,
                'examId' => $scope->examId,
                'subjectId' => $scope->subjectId,
                'status' => 'draft',
                'revision' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }

        if (DB::table('marks_scope_states')->count() < $scopes->count()) {
            throw new RuntimeException('Not every existing marks scope was initialized as Draft.');
        }
    }

    public function down(): void
    {
        // Scope states may become lifecycle evidence; never delete them automatically.
    }
};

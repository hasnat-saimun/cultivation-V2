<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['studentId', 'sessionId', 'classId', 'examId', 'subjectId'] as $column) {
            DB::statement("ALTER TABLE marksheets MODIFY {$column} VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
        }
        DB::statement('ALTER TABLE marksheets MODIFY groupId VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
    }

    public function down(): void
    {
        foreach (['studentId', 'sessionId', 'classId', 'examId', 'subjectId', 'groupId'] as $column) {
            DB::statement("ALTER TABLE marksheets MODIFY {$column} VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL");
        }
    }
};

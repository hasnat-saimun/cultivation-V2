<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marksheets', function (Blueprint $table) {
            $table->index(
                ['studentId', 'sessionId', 'classId', 'groupId', 'examId', 'subjectId'],
                'marks_identity_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('marksheets', fn (Blueprint $table) => $table->dropIndex('marks_identity_lookup_idx'));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marksheets', function (Blueprint $table) {
            $table->unique(
                ['studentId', 'sessionId', 'classId', 'normalizedGroupScope', 'examId', 'subjectId'],
                'marks_business_identity_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::table('marksheets', fn (Blueprint $table) => $table->dropUnique('marks_business_identity_uq'));
    }
};

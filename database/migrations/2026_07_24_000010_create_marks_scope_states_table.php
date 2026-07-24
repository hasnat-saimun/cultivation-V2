<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marks_scope_states', function (Blueprint $table) {
            $table->id();
            $table->string('sessionId', 64);
            $table->string('classId', 64);
            $table->string('groupId', 64)->nullable();
            $table->string('examId', 64);
            $table->string('subjectId', 64);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('revision')->default(1);
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('reopened_by')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->string('reopen_reason', 500)->nullable();
            $table->timestamps();
            $table->index('status', 'marks_scope_status_idx');
            $table->index(['examId', 'classId', 'sessionId'], 'marks_scope_exam_class_session_idx');
            $table->index(['confirmed_by', 'confirmed_at'], 'marks_scope_confirmed_actor_idx');
        });
        DB::statement(
            "ALTER TABLE marks_scope_states ADD normalizedGroupScope VARCHAR(72) CHARACTER SET ascii COLLATE ascii_bin ".
            "GENERATED ALWAYS AS (CASE WHEN groupId IS NULL THEN 'class' ELSE CONCAT('section:',groupId) END) STORED"
        );
        Schema::table('marks_scope_states', function (Blueprint $table) {
            $table->unique(
                ['sessionId', 'classId', 'normalizedGroupScope', 'examId', 'subjectId'],
                'marks_scope_identity_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marks_scope_states');
    }
};

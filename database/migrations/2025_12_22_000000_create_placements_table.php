<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use a distinct table name to avoid conflicts with existing tables
        if (!Schema::hasTable('exam_placements')) {
            Schema::create('exam_placements', function (Blueprint $table) {
                $table->id();
                // Keep field types consistent with existing marksheets schema (strings used for IDs)
                $table->string('studentId', 64);
                $table->string('classId', 64);
                $table->string('sessionId', 64);
                $table->string('groupId', 64)->nullable();
                $table->string('examId', 64);
                $table->unsignedInteger('subjectsCount')->default(0);
                $table->decimal('totalGradePoints', 6, 2)->default(0);
                $table->decimal('gpa', 4, 2)->default(0);
                $table->unsignedInteger('totalMarks')->nullable();
                $table->unsignedInteger('position')->nullable();
                $table->string('status')->nullable(); // e.g. Pass/Fail/Merit
                $table->timestamps();

                $table->index(['sessionId', 'classId', 'groupId', 'examId']);
                $table->unique(['studentId', 'sessionId', 'classId', 'groupId', 'examId'], 'uniq_exam_placement');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_placements');
    }
};

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
        Schema::create('marksheets', function (Blueprint $table) {
            $table->id();
            $table->string('studentId')->nullable();
            $table->string('classId')->nullable();
            $table->string('sessionId')->nullable();
            $table->string('groupId')->nullable();
            $table->string('examId')->nullable();
            $table->string('subjectId')->nullable();
            $table->string('subjectMarks')->nullable();
            $table->string('objectMarks')->nullable();
            $table->string('practicalMarks')->nullable();
            $table->string('totalMarks')->nullable();
            $table->string('laterGrade')->nullable();
            $table->string('gradePoint')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marksheets');
    }
};

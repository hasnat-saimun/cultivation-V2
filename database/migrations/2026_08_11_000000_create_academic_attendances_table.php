<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedInteger('working_days');
            $table->unsignedInteger('present_days');
            $table->unsignedInteger('absent_days');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->char('scope_key', 64)->unique();
            $table->timestamps();

            $table->index(['exam_id', 'session_id', 'class_id', 'section_id', 'department_id'], 'academic_attendance_scope_idx');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_attendances');
    }
};

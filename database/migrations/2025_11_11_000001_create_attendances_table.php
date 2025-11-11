<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('attendances', function (Blueprint $table) {
			$table->id();
			$table->date('attendance_date');
			$table->unsignedBigInteger('class_id');
			$table->unsignedBigInteger('section_id')->nullable();
			$table->unsignedBigInteger('session_id')->nullable();
			$table->unsignedBigInteger('student_id');
			$table->unsignedBigInteger('teacher_id');
			$table->enum('status', ['Present','Absent','Late','Excused'])->default('Present');
			$table->timestamps();
			$table->unique(['attendance_date','class_id','section_id','student_id'], 'attendance_unique');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('attendances');
	}
};
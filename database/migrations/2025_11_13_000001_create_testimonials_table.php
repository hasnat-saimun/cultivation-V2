<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admission_id'); // links to newAdmission
            $table->string('ref_no')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('student_name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('ssc_year')->nullable();
            $table->string('roll_no')->nullable();
            $table->string('reg_no')->nullable();
            $table->string('gpa')->nullable();
            $table->string('grade')->nullable();
            $table->string('subject')->nullable();
            $table->date('dob')->nullable();
            $table->text('remarks')->nullable();
            $table->string('composed_by')->nullable();
            $table->date('composed_date')->nullable();
            $table->string('headmaster_name')->nullable();
            $table->timestamps();
            $table->foreign('admission_id')->references('id')->on('new_admissions')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('testimonials');
    }
};

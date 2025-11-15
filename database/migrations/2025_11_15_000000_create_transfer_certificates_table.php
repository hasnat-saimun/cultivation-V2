<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transfer_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admission_id');
            $table->string('ref_no')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('student_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('address')->nullable();
            $table->string('class_name')->nullable();
            $table->string('session')->nullable();
            $table->string('roll_no')->nullable();
            $table->string('reg_no')->nullable();
            $table->date('dob')->nullable();
            $table->string('leaving_class')->nullable();
            $table->date('leaving_date')->nullable();
            $table->string('reason')->nullable();
            $table->string('conduct')->nullable();
            $table->string('character')->nullable();
            $table->string('remarks')->nullable();
            $table->string('composed_by')->nullable();
            $table->date('composed_date')->nullable();
            $table->string('headmaster_name')->nullable();
            $table->timestamps();
            $table->foreign('admission_id')->references('id')->on('new_admissions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_certificates');
    }
};

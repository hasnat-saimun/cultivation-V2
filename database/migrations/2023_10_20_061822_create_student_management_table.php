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
        Schema::create('student_management', function (Blueprint $table) {
            $table->id();
            $table->string('admitId')->nullable();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('fathersName')->nullable();
            $table->string('mothersName')->nullable();
            $table->text('address')->nullable();
            $table->string('gender')->nullable();
            $table->string('dob')->nullable();
            $table->string('roll')->nullable();
            $table->string('mobile')->nullable();
            $table->string('blGroup')->nullable();
            $table->string('class')->nullable();
            $table->string('departmentName')->nullable();
            $table->string('email')->nullable();
            $table->string('session')->nullable();
            $table->string('section')->nullable();
            $table->string('religion')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_management');
    }
};

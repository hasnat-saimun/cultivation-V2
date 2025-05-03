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
        Schema::create('teacher_management', function (Blueprint $table) {
            $table->id();
            $table->string('teacherId')->nullable();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('fathersName')->nullable();
            $table->string('mothersName')->nullable();
            $table->string('gender')->nullable();
            $table->string('dob')->nullable();
            $table->string('designation')->nullable();
            $table->string('blGroup')->nullable();
            $table->string('religion')->nullable();
            $table->string('email')->nullable();
            $table->string('joinDate')->nullable();
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_management');
    }
};

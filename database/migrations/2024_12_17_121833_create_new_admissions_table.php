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
        Schema::create('new_admissions', function (Blueprint $table) {
            $table->id();
            $table->integer('stdId')->nullable();
            $table->string('fullName')->nullable();
            $table->string('sureName')->nullable();
            $table->string('father')->nullable();
            $table->string('mother')->nullable();
            $table->string('gender')->nullable();
            $table->string('dob')->nullable();
            $table->string('blGroup')->nullable();
            $table->string('religion')->nullable();
            $table->string('address')->nullable();
            $table->string('mail')->nullable();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->string('sessName')->nullable();
            $table->string('className')->nullable();
            $table->string('departmentName')->nullable();
            $table->string('sectionName')->nullable();
            $table->string('rollNumber')->nullable();
            $table->string('gurdianName')->nullable();
            $table->string('gurdianMobile')->nullable();
            $table->string('relationGurdian')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_admissions');
    }
};

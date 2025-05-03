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
        Schema::create('placement_cells', function (Blueprint $table) {
            $table->id();
            $table->string('fullName')->nullable();
            $table->string('sessionYear')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('companyName')->nullable();
            $table->string('joinDate')->nullable();
            $table->string('rollNumber')->nullable();
            $table->string('designation')->nullable();
            $table->string('jobDetails')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placement_cells');
    }
};

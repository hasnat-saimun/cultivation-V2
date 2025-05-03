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
        Schema::create('managing_comittees', function (Blueprint $table) {
            $table->id();
            $table->string('fullName')->nullable();
            $table->string('qualification')->nullable();
            $table->string('designation')->nullable();
            $table->string('jobDetails')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('validYear')->nullable();
            $table->string('status')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('managing_comittees');
    }
};

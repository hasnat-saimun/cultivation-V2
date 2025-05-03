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
        Schema::create('ex_principals', function (Blueprint $table) {
            $table->id();
            $table->string('fullName')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('startFrom')->nullable();
            $table->string('endTo')->nullable();
            $table->string('designation')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ex_principals');
    }
};

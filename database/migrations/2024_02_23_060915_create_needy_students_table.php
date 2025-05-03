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
        Schema::create('needy_students', function (Blueprint $table) {
            $table->id();
            $table->string('fullName')->nullable();
            $table->string('session')->nullable();
            $table->string('department')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('attachFile')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('needy_students');
    }
};

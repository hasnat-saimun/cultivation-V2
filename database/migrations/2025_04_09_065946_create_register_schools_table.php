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
        Schema::create('register_schools', function (Blueprint $table) {
            $table->id();
            $table->string('insName')->nullable();
            $table->string('insAddress')->nullable();
            $table->string('officeMobile')->nullable();
            $table->string('webName')->nullable();
            $table->string('officeMail')->nullable();
            $table->string('insLogo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('register_schools');
    }
};

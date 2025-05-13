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
        Schema::create('front_manages', function (Blueprint $table) {
            $table->id();
            $table->string('homeHeadline')->nullable();
            $table->string('homeDetails')->nullable();
            $table->string('educationMinistarName')->nullable();
            $table->string('educationMinistarImg')->nullable();
            $table->string('boardChairmanName')->nullable();
            $table->string('boardChairmanImg')->nullable();
            $table->string('boardChairmanLocation')->nullable();
            $table->string('ourMission')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('front_manages');
    }
};

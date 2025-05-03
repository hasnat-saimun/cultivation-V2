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
        Schema::create('institute_details', function (Blueprint $table) {
            $table->id();
            $table->string('insHeadline')->nullable();
            $table->longText('insDetails')->nullable();
            $table->string('landSize')->nullable();
            $table->string('establishDate')->nullable();
            $table->string('heroImg')->nullable();
            $table->string('mission')->nullable();
            $table->string('vision')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institute_details');
    }
};

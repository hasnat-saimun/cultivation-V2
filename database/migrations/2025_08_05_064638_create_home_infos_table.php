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
        Schema::create('home_infos', function (Blueprint $table) {
            $table->id();
            $table->string('slidImg1')->nullable();
            $table->string('slidImg2')->nullable();
            $table->string('slidImg3')->nullable();
            $table->string('eduMinImg')->nullable();
            $table->string('boardChairmanImg')->nullable();
            $table->string('principalImg')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->string('insName')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_infos');
    }
};

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
        Schema::create('server_configs', function (Blueprint $table) {
            $table->id();
            $table->string('institueName')->nullable();
            $table->string('address')->nullable();
            $table->string('officeMobile')->nullable();
            $table->string('officeEmail')->nullable();
            $table->string('loginPass')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('facebookPage')->nullable();
            $table->string('einNumber')->nullable();
            $table->string('twitterLink')->nullable();
            $table->string('youtubeChanel')->nullable();
            $table->string('linkedIn')->nullable();
            $table->string('sm_on_off')->nullable();
            $table->string('avatar')->nullable();
            $table->string('principalName')->nullable();
            $table->string('principalMobile')->nullable();
            $table->string('principalMail')->nullable();
            $table->string('principalSign')->nullable();
            $table->string('studentIdPrefix')->nullable();
            $table->string('teacherIdPrefix')->nullable();
            $table->string('staffIdPrefix')->nullable();
            $table->string('establishDate')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_configs');
    }
};

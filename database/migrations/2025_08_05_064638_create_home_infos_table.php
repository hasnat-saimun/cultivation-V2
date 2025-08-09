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
            $table->string('eduMinName')->nullable();
            $table->string('eduMinImg')->nullable();
            $table->string('eduMinDetail')->nullable();
            $table->string('boardChairmanName')->nullable();
            $table->string('boardChairmanImg')->nullable();
            $table->string('boardChairmanDetail')->nullable();
            $table->string('principalName')->nullable();
            $table->string('principalImg')->nullable();
            $table->string('principalDetail')->nullable();
            $table->string('founded')->nullable();
            $table->string('area')->nullable();
            $table->string('teacherTotal')->nullable();
            $table->string('studentTotal')->nullable();
            $table->longText('notice')->nullable();
            $table->string('wcMsgHadeline')->nullable();
            $table->longText('wclMsgDescription')->nullable();
            $table->longText('missionDescription')->nullable();
            $table->string('writerName')->nullable();
            $table->string('mainGoal')->nullable();
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

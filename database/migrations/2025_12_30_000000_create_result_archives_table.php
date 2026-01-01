<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('result_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('old_class')->nullable();
            $table->string('old_roll')->nullable();
            $table->string('old_session')->nullable();
            $table->string('old_section')->nullable();
            $table->json('result_data'); // store marks, grades, etc
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('result_archives');
    }
};

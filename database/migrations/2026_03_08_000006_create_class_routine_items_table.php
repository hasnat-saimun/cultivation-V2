<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_routine_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_routine_id');
            $table->string('class_day')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('class_time')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('class_routine_id')
                ->references('id')
                ->on('class_routines')
                ->onDelete('cascade');

            $table->index(['class_routine_id', 'class_day']);
            $table->index('subject_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_routine_items');
    }
};

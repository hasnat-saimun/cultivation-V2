<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_routine_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_routine_id');
            $table->date('exam_date')->nullable();
            $table->string('exam_day')->nullable();
            $table->string('exam_time')->nullable();
            $table->string('subject_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('exam_routine_id')
                ->references('id')
                ->on('exam_routines')
                ->onDelete('cascade');

            $table->index(['exam_routine_id', 'exam_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_routine_items');
    }
};

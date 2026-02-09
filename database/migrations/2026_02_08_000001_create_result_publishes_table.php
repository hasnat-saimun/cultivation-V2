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
        Schema::create('result_publishes', function (Blueprint $table) {
            $table->id();
            $table->string('examId', 64);
            $table->string('sessionId', 64);
            $table->string('classId', 64);
            $table->string('groupId', 64)->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['examId', 'sessionId', 'classId', 'groupId'], 'result_publish_scope_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_publishes');
    }
};

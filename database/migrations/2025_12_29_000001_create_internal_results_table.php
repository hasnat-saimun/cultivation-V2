<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('internal_results', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('assignClass')->nullable();
            $table->unsignedBigInteger('assignDepartment')->nullable();
            $table->unsignedBigInteger('assignSession')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_results');
    }
};

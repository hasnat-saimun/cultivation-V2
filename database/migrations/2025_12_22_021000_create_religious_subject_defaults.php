<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('religious_subject_defaults')) {
            Schema::create('religious_subject_defaults', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('classId');
                $table->unsignedBigInteger('subjectId');
                $table->timestamps();

                $table->unique(['classId'], 'uniq_religious_default_class');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('religious_subject_defaults');
    }
};

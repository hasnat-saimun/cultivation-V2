<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id'); // references cultivation_admins.id
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();
            $table->unique(['teacher_id','subject_id'],'teacher_subject_unique');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('teacher_subjects');
    }
};
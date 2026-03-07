<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_routine_items', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_routine_items', 'start_time')) {
                $table->time('start_time')->nullable()->after('exam_day');
            }

            if (!Schema::hasColumn('exam_routine_items', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }

            if (!Schema::hasColumn('exam_routine_items', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('exam_time');
                $table->index('subject_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_routine_items', function (Blueprint $table) {
            if (Schema::hasColumn('exam_routine_items', 'subject_id')) {
                $table->dropIndex(['subject_id']);
                $table->dropColumn('subject_id');
            }

            if (Schema::hasColumn('exam_routine_items', 'end_time')) {
                $table->dropColumn('end_time');
            }

            if (Schema::hasColumn('exam_routine_items', 'start_time')) {
                $table->dropColumn('start_time');
            }
        });
    }
};

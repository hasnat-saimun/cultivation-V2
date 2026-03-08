<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_routines', function (Blueprint $table) {
            if (Schema::hasColumn('class_routines', 'assignClass')) {
                $table->index('assignClass', 'class_routines_assign_class_idx');
            }
            if (Schema::hasColumn('class_routines', 'assignDepartment')) {
                $table->index('assignDepartment', 'class_routines_assign_department_idx');
            }
            if (Schema::hasColumn('class_routines', 'assignSession')) {
                $table->index('assignSession', 'class_routines_assign_session_idx');
            }
            if (Schema::hasColumn('class_routines', 'title')) {
                $table->index('title', 'class_routines_title_idx');
            }
        });

        Schema::table('class_routine_items', function (Blueprint $table) {
            if (Schema::hasColumn('class_routine_items', 'class_routine_id') && Schema::hasColumn('class_routine_items', 'start_time') && Schema::hasColumn('class_routine_items', 'end_time')) {
                $table->index(['class_routine_id', 'start_time', 'end_time'], 'class_routine_items_time_window_idx');
            }
            if (Schema::hasColumn('class_routine_items', 'class_routine_id') && Schema::hasColumn('class_routine_items', 'sort_order')) {
                $table->index(['class_routine_id', 'sort_order'], 'class_routine_items_sort_idx');
            }
        });

        Schema::table('exam_routines', function (Blueprint $table) {
            if (Schema::hasColumn('exam_routines', 'status')) {
                $table->index('status', 'exam_routines_status_idx');
            }
            if (Schema::hasColumn('exam_routines', 'assignClass')) {
                $table->index('assignClass', 'exam_routines_assign_class_idx');
            }
            if (Schema::hasColumn('exam_routines', 'assignDepartment')) {
                $table->index('assignDepartment', 'exam_routines_assign_department_idx');
            }
            if (Schema::hasColumn('exam_routines', 'assignSession')) {
                $table->index('assignSession', 'exam_routines_assign_session_idx');
            }
        });

        Schema::table('exam_routine_items', function (Blueprint $table) {
            if (Schema::hasColumn('exam_routine_items', 'exam_routine_id') && Schema::hasColumn('exam_routine_items', 'start_time') && Schema::hasColumn('exam_routine_items', 'end_time')) {
                $table->index(['exam_routine_id', 'start_time', 'end_time'], 'exam_routine_items_time_window_idx');
            }
            if (Schema::hasColumn('exam_routine_items', 'exam_routine_id') && Schema::hasColumn('exam_routine_items', 'sort_order')) {
                $table->index(['exam_routine_id', 'sort_order'], 'exam_routine_items_sort_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_routines', function (Blueprint $table) {
            $table->dropIndex('class_routines_assign_class_idx');
            $table->dropIndex('class_routines_assign_department_idx');
            $table->dropIndex('class_routines_assign_session_idx');
            $table->dropIndex('class_routines_title_idx');
        });

        Schema::table('class_routine_items', function (Blueprint $table) {
            $table->dropIndex('class_routine_items_time_window_idx');
            $table->dropIndex('class_routine_items_sort_idx');
        });

        Schema::table('exam_routines', function (Blueprint $table) {
            $table->dropIndex('exam_routines_status_idx');
            $table->dropIndex('exam_routines_assign_class_idx');
            $table->dropIndex('exam_routines_assign_department_idx');
            $table->dropIndex('exam_routines_assign_session_idx');
        });

        Schema::table('exam_routine_items', function (Blueprint $table) {
            $table->dropIndex('exam_routine_items_time_window_idx');
            $table->dropIndex('exam_routine_items_sort_idx');
        });
    }
};

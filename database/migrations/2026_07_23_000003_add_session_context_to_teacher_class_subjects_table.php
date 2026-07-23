<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teacher_class_subjects')) {
            return;
        }

        if (!Schema::hasColumn('teacher_class_subjects', 'session_id')) {
            Schema::table('teacher_class_subjects', function (Blueprint $table) {
                $table->unsignedBigInteger('session_id')->nullable()->after('teacher_id');
                $table->index('session_id', 'tcs_session_id_idx');
            });
        }

        Schema::table('teacher_class_subjects', function (Blueprint $table) {
            $table->index(
                ['session_id', 'class_id', 'section_id', 'group_id', 'subject_id', 'gender_scope'],
                'tcs_context_lookup_idx'
            );
        });

        // The legacy unique index omits session/group/gender and blocks valid rows.
        // Drop safely when present to allow context-aware assignment rows.
        try {
            Schema::table('teacher_class_subjects', function (Blueprint $table) {
                $table->dropUnique('teacher_class_subject_unique');
            });
        } catch (\Throwable $e) {
            // Intentionally ignored for environments where the index does not exist.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('teacher_class_subjects')) {
            return;
        }

        try {
            Schema::table('teacher_class_subjects', function (Blueprint $table) {
                $table->dropIndex('tcs_context_lookup_idx');
            });
        } catch (\Throwable $e) {
            // no-op
        }

        try {
            Schema::table('teacher_class_subjects', function (Blueprint $table) {
                $table->dropIndex('tcs_session_id_idx');
            });
        } catch (\Throwable $e) {
            // no-op
        }

        if (Schema::hasColumn('teacher_class_subjects', 'session_id')) {
            Schema::table('teacher_class_subjects', function (Blueprint $table) {
                $table->dropColumn('session_id');
            });
        }

        // Restore historical unique index for rollback parity.
        Schema::table('teacher_class_subjects', function (Blueprint $table) {
            $table->unique(['teacher_id', 'class_id', 'section_id', 'subject_id'], 'teacher_class_subject_unique');
        });
    }
};

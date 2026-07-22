<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('teacher_class_subjects', 'gender_scope')) {
            Schema::table('teacher_class_subjects', function (Blueprint $table) {
                $table->string('gender_scope', 10)->default('all')->after('group_id');
                $table->index('gender_scope');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teacher_class_subjects', 'gender_scope')) {
            Schema::table('teacher_class_subjects', function (Blueprint $table) {
                $table->dropIndex(['gender_scope']);
                $table->dropColumn('gender_scope');
            });
        }
    }
};

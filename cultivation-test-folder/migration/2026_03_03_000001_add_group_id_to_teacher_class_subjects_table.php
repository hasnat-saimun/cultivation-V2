<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('teacher_class_subjects', 'group_id')) {
            Schema::table('teacher_class_subjects', function (Blueprint $table) {
                $table->unsignedBigInteger('group_id')->nullable()->after('section_id');
                $table->index('group_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teacher_class_subjects', 'group_id')) {
            Schema::table('teacher_class_subjects', function (Blueprint $table) {
                $table->dropIndex(['group_id']);
                $table->dropColumn('group_id');
            });
        }
    }
};

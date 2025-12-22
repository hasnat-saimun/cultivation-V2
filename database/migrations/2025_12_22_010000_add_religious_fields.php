<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'isReligious')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('isReligious')->default(false)->after('assign_class');
            });
        }
        if (Schema::hasTable('new_admissions') && !Schema::hasColumn('new_admissions', 'religiousSubjectId')) {
            Schema::table('new_admissions', function (Blueprint $table) {
                $table->unsignedBigInteger('religiousSubjectId')->nullable()->after('sectionName');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'isReligious')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('isReligious');
            });
        }
        if (Schema::hasTable('new_admissions') && Schema::hasColumn('new_admissions', 'religiousSubjectId')) {
            Schema::table('new_admissions', function (Blueprint $table) {
                $table->dropColumn('religiousSubjectId');
            });
        }
    }
};

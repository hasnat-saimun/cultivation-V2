<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('new_admissions') && !Schema::hasColumn('new_admissions', 'fourthSubjectId')) {
            Schema::table('new_admissions', function (Blueprint $table) {
                $table->unsignedBigInteger('fourthSubjectId')->nullable()->after('religiousSubjectId');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('new_admissions') && Schema::hasColumn('new_admissions', 'fourthSubjectId')) {
            Schema::table('new_admissions', function (Blueprint $table) {
                $table->dropColumn('fourthSubjectId');
            });
        }
    }
};

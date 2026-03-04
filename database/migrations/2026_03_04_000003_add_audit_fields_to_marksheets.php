<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marksheets', function (Blueprint $table) {
            if (!Schema::hasColumn('marksheets', 'entered_by')) {
                $table->unsignedBigInteger('entered_by')->nullable()->after('teacher_id');
                $table->index('entered_by');
            }
            if (!Schema::hasColumn('marksheets', 'entered_by_role')) {
                $table->string('entered_by_role', 30)->nullable()->after('entered_by');
            }
            if (!Schema::hasColumn('marksheets', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('entered_by_role');
                $table->index('updated_by');
            }
            if (!Schema::hasColumn('marksheets', 'updated_by_role')) {
                $table->string('updated_by_role', 30)->nullable()->after('updated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marksheets', function (Blueprint $table) {
            if (Schema::hasColumn('marksheets', 'updated_by')) {
                $table->dropIndex(['updated_by']);
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('marksheets', 'updated_by_role')) {
                $table->dropColumn('updated_by_role');
            }
            if (Schema::hasColumn('marksheets', 'entered_by')) {
                $table->dropIndex(['entered_by']);
                $table->dropColumn('entered_by');
            }
            if (Schema::hasColumn('marksheets', 'entered_by_role')) {
                $table->dropColumn('entered_by_role');
            }
        });
    }
};

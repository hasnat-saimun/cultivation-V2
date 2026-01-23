<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_management', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_management', 'designation_id')) {
                $table->unsignedBigInteger('designation_id')->nullable()->after('designation');
                $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
            }
        });

        Schema::table('staff_management', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_management', 'designation_id')) {
                $table->unsignedBigInteger('designation_id')->nullable()->after('designation');
                $table->foreign('designation_id')->references('id')->on('designations')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_management', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_management', 'designation_id')) {
                $table->dropForeign(['designation_id']);
                $table->dropColumn('designation_id');
            }
        });

        Schema::table('staff_management', function (Blueprint $table) {
            if (Schema::hasColumn('staff_management', 'designation_id')) {
                $table->dropForeign(['designation_id']);
                $table->dropColumn('designation_id');
            }
        });
    }
};

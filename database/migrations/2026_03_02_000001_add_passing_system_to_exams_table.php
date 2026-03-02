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
        if (!Schema::hasColumn('exams', 'passingSystem')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->string('passingSystem')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('exams', 'passingSystem')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropColumn('passingSystem');
            });
        }
    }
};

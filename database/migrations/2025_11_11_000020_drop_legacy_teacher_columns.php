<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cultivation_admins')) {
            Schema::table('cultivation_admins', function (Blueprint $table) {
                if (Schema::hasColumn('cultivation_admins','accessClass')) {
                    $table->dropColumn('accessClass');
                }
                if (Schema::hasColumn('cultivation_admins','accessSubject')) {
                    $table->dropColumn('accessSubject');
                }
            });
        }
    }

    public function down(): void
    {
        // Columns intentionally removed; restore as nullable strings if rollback needed
        if (Schema::hasTable('cultivation_admins')) {
            Schema::table('cultivation_admins', function (Blueprint $table) {
                if (!Schema::hasColumn('cultivation_admins','accessClass')) {
                    $table->string('accessClass')->nullable();
                }
                if (!Schema::hasColumn('cultivation_admins','accessSubject')) {
                    $table->string('accessSubject')->nullable();
                }
            });
        }
    }
};

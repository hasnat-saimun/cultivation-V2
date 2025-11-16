<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cultivation_admins')) {
            Schema::table('cultivation_admins', function (Blueprint $table) {
                if (!Schema::hasColumn('cultivation_admins', 'avatar')) {
                    $table->string('avatar')->nullable()->after('adminMail');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cultivation_admins')) {
            Schema::table('cultivation_admins', function (Blueprint $table) {
                if (Schema::hasColumn('cultivation_admins', 'avatar')) {
                    $table->dropColumn('avatar');
                }
            });
        }
    }
};

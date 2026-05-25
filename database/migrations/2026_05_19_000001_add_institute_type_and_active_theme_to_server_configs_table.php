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
        Schema::table('server_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('server_configs', 'institute_type')) {
                $table->string('institute_type')->nullable()->after('mapEmbed');
            }
            if (!Schema::hasColumn('server_configs', 'active_theme')) {
                $table->string('active_theme')->nullable()->after('institute_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_configs', function (Blueprint $table) {
            if (Schema::hasColumn('server_configs', 'active_theme')) {
                $table->dropColumn('active_theme');
            }
            if (Schema::hasColumn('server_configs', 'institute_type')) {
                $table->dropColumn('institute_type');
            }
        });
    }
};

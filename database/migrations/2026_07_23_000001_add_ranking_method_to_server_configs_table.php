<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_configs', function (Blueprint $table) {
            $table->string('ranking_method', 30)
                ->default('grading')
                ->after('active_theme');
        });
    }

    public function down(): void
    {
        Schema::table('server_configs', function (Blueprint $table) {
            $table->dropColumn('ranking_method');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cultivation_admins', function (Blueprint $table) {
            if (!Schema::hasColumn('cultivation_admins', 'primary_section_id')) {
                $table->unsignedBigInteger('primary_section_id')->nullable();
                $table->index('primary_section_id');
            }
        });
    }

    public function down()
    {
        Schema::table('cultivation_admins', function (Blueprint $table) {
            if (Schema::hasColumn('cultivation_admins', 'primary_section_id')) {
                $table->dropIndex(['primary_section_id']);
                $table->dropColumn('primary_section_id');
            }
        });
    }
};

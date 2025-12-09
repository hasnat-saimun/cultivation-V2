<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMpoPdoFieldsToTeachersTable extends Migration
{
    public function up()
    {
        // Make migration idempotent: only add columns if they don't already exist
        if (!Schema::hasColumn('teacher_management', 'mpoIndex')) {
            Schema::table('teacher_management', function (Blueprint $table) {
                $table->string('mpoIndex')->nullable()->after('address');
            });
        }

        if (!Schema::hasColumn('teacher_management', 'pdsId')) {
            Schema::table('teacher_management', function (Blueprint $table) {
                $table->string('pdsId')->nullable()->after('mpoIndex');
            });
        }
    }

    public function down()
    {
        Schema::table('teacher_management', function (Blueprint $table) {
            $table->dropColumn(['mpoIndex', 'pdsId']);
        });
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMpoPdoFieldsToTeachersTable extends Migration
{
    public function up()
    {
        Schema::table('teacher_management', function (Blueprint $table) {
            $table->string('mpoIndex')->nullable()->after('address');
            $table->string('pdoId')->nullable()->after('mpoIndex');
        });
    }

    public function down()
    {
        Schema::table('teacher_management', function (Blueprint $table) {
            $table->dropColumn(['mpoIndex', 'pdoId']);
        });
    }
}
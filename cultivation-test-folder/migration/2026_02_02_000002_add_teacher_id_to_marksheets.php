<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('marksheets', function (Blueprint $table) {
            if (!Schema::hasColumn('marksheets', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('subjectId');
                $table->index('teacher_id');
            }
        });
    }

    public function down()
    {
        Schema::table('marksheets', function (Blueprint $table) {
            if (Schema::hasColumn('marksheets', 'teacher_id')) {
                $table->dropIndex(['teacher_id']);
                $table->dropColumn('teacher_id');
            }
        });
    }
};

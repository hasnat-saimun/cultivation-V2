<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_routines', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_routines', 'assignExam')) {
                $table->unsignedBigInteger('assignExam')->nullable()->after('assignSession');
                $table->index('assignExam');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_routines', function (Blueprint $table) {
            if (Schema::hasColumn('exam_routines', 'assignExam')) {
                $table->dropIndex(['assignExam']);
                $table->dropColumn('assignExam');
            }
        });
    }
};

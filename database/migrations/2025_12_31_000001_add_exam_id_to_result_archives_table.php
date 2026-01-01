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
        Schema::table('result_archives', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_id')->nullable()->after('old_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('result_archives', function (Blueprint $table) {
            $table->dropColumn('exam_id');
        });
    }
};

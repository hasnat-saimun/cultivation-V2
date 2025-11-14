<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('testimonials') && !Schema::hasColumn('testimonials', 'exam_name')) {
            Schema::table('testimonials', function (Blueprint $table) {
                $table->string('exam_name')->nullable()->after('education_board');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('testimonials') && Schema::hasColumn('testimonials', 'exam_name')) {
            Schema::table('testimonials', function (Blueprint $table) {
                $table->dropColumn('exam_name');
            });
        }
    }
};

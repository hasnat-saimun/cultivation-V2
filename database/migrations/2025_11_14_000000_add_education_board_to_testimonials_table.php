<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('testimonials') && !Schema::hasColumn('testimonials', 'education_board')) {
            Schema::table('testimonials', function (Blueprint $table) {
                $table->string('education_board')->nullable()->after('subject');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('testimonials') && Schema::hasColumn('testimonials', 'education_board')) {
            Schema::table('testimonials', function (Blueprint $table) {
                $table->dropColumn('education_board');
            });
        }
    }
};

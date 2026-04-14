<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_class_subjects', function (Blueprint $table) {
            $table->json('assigned_days')->nullable()->after('group_id')
                ->comment('JSON array of assigned days (e.g., ["Sunday", "Monday", "Wednesday"])');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_class_subjects', function (Blueprint $table) {
            $table->dropColumn('assigned_days');
        });
    }
};

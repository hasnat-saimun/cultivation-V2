<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE marksheets ADD normalizedGroupScope VARCHAR(72) CHARACTER SET ascii COLLATE ascii_bin ".
            "GENERATED ALWAYS AS (CASE WHEN groupId IS NULL THEN 'class' ELSE CONCAT('section:',groupId) END) STORED"
        );
        if (DB::table('marksheets')->whereNull('normalizedGroupScope')->exists()) {
            throw new RuntimeException('marksheets.normalizedGroupScope generation failed.');
        }
    }

    public function down(): void
    {
        Schema::table('marksheets', fn ($table) => $table->dropColumn('normalizedGroupScope'));
    }
};

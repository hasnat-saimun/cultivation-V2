<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE result_publishes ADD normalizedGroupScope VARCHAR(72) CHARACTER SET ascii COLLATE ascii_bin ".
            "GENERATED ALWAYS AS (CASE WHEN groupId IS NULL THEN 'class' ELSE CONCAT('section:',groupId) END) STORED"
        );
        if (DB::table('result_publishes')->whereNull('normalizedGroupScope')->exists()) {
            throw new RuntimeException('result_publishes.normalizedGroupScope generation failed.');
        }
    }

    public function down(): void
    {
        Schema::table('result_publishes', fn ($table) => $table->dropColumn('normalizedGroupScope'));
    }
};

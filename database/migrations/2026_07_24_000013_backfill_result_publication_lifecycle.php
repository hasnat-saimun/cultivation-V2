<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('result_publishes')->whereNull('status')->update([
            'status' => 'published',
            'revision' => 1,
            'legacyImported' => true,
        ]);

        if (DB::table('result_publishes')->whereNull('status')->orWhereNull('revision')->exists()) {
            throw new RuntimeException('Publication lifecycle backfill did not cover every existing row.');
        }

        DB::statement("ALTER TABLE result_publishes MODIFY status VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
        DB::statement('ALTER TABLE result_publishes MODIFY revision BIGINT UNSIGNED NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE result_publishes MODIFY legacyImported TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE result_publishes MODIFY status VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL");
        DB::statement('ALTER TABLE result_publishes MODIFY revision BIGINT UNSIGNED NULL');
        // Historical import truth is retained; data backfill is not reversed.
    }
};

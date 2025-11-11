<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cultivation_admins')) {
            // Only attempt to null columns if they exist
            $columns = Schema::getColumnListing('cultivation_admins');
            $updates = [];
            if (in_array('accessClass', $columns)) { $updates['accessClass'] = null; }
            if (in_array('accessSubject', $columns)) { $updates['accessSubject'] = null; }
            if (!empty($updates)) {
                DB::table('cultivation_admins')->update($updates);
            }
        }
    }

    public function down(): void
    {
        // No-op: legacy data intentionally removed; cannot restore
    }
};

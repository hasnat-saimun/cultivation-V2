<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('placements') && !Schema::hasTable('exam_placements')) {
            Schema::rename('placements', 'exam_placements');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_placements') && !Schema::hasTable('placements')) {
            Schema::rename('exam_placements', 'placements');
        }
    }
};

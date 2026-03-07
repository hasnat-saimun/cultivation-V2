<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_routines', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_routines', 'assignSection')) {
                $table->unsignedBigInteger('assignSection')->nullable()->after('assignClass');
                $table->index('assignSection');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_routines', function (Blueprint $table) {
            if (Schema::hasColumn('exam_routines', 'assignSection')) {
                $table->dropIndex(['assignSection']);
                $table->dropColumn('assignSection');
            }
        });
    }
};

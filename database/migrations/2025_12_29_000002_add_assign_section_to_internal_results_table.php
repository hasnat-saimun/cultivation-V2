<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('internal_results', function (Blueprint $table) {
            $table->string('assignSection')->nullable()->after('assignClass');
        });
    }

    public function down(): void
    {
        Schema::table('internal_results', function (Blueprint $table) {
            $table->dropColumn('assignSection');
        });
    }
};

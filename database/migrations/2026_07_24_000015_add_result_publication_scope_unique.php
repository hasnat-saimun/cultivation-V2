<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_publishes', function (Blueprint $table) {
            $table->unique(
                ['examId', 'sessionId', 'classId', 'normalizedGroupScope'],
                'result_publication_scope_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::table('result_publishes', fn (Blueprint $table) => $table->dropUnique('result_publication_scope_uq'));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_publishes', function (Blueprint $table) {
            $table->string('status', 20)->nullable()->after('groupId');
            $table->unsignedBigInteger('revision')->nullable()->after('status');
            $table->unsignedBigInteger('unpublished_by')->nullable()->after('published_at');
            $table->timestamp('unpublished_at')->nullable()->after('unpublished_by');
            $table->string('unpublish_reason', 500)->nullable()->after('unpublished_at');
            $table->boolean('legacyImported')->default(false)->after('unpublish_reason');
        });
    }

    public function down(): void
    {
        Schema::table('result_publishes', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'revision', 'unpublished_by', 'unpublished_at',
                'unpublish_reason', 'legacyImported',
            ]);
        });
    }
};

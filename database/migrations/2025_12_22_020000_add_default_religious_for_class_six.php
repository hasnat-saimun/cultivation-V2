<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'defaultReligiousForClassSix')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('defaultReligiousForClassSix')->default(false)->after('isReligious');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'defaultReligiousForClassSix')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('defaultReligiousForClassSix');
            });
        }
    }
};

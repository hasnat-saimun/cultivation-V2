<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('attendances', 'sms_sent')) {
                    $table->boolean('sms_sent')->default(0)->after('status');
                }
                if (!Schema::hasColumn('attendances', 'sms_sent_at')) {
                    $table->timestamp('sms_sent_at')->nullable()->after('sms_sent');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (Schema::hasColumn('attendances', 'sms_sent_at')) $table->dropColumn('sms_sent_at');
                if (Schema::hasColumn('attendances', 'sms_sent')) $table->dropColumn('sms_sent');
            });
        }
    }
};

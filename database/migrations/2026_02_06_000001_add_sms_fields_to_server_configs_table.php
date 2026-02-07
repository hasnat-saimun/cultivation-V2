<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('server_configs', function (Blueprint $table) {
            $table->string('sms_type')->nullable()->after('sm_on_off');
            $table->text('sms_body_present')->nullable()->after('sms_type');
            $table->text('sms_body_absent')->nullable()->after('sms_body_present');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_configs', function (Blueprint $table) {
            $table->dropColumn(['sms_type', 'sms_body_present', 'sms_body_absent']);
        });
    }
};

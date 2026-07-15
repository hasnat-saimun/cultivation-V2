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
        Schema::create('promotion_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('promotion_id', 64)->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->string('old_session')->nullable();
            $table->string('old_class')->nullable();
            $table->string('old_section')->nullable();
            $table->string('old_roll')->nullable();
            $table->string('new_session')->nullable();
            $table->string('new_class')->nullable();
            $table->string('new_section')->nullable();
            $table->string('new_roll')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['student_id', 'created_at'], 'promotion_audit_student_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_audit_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique('result_lifecycle_event_uuid_uq');
            $table->uuid('correlation_uuid')->nullable()->index('result_lifecycle_correlation_idx');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_role', 32)->nullable();
            $table->string('action', 64);
            $table->string('entity_type', 32);
            $table->string('sessionId', 64)->nullable();
            $table->string('classId', 64)->nullable();
            $table->string('groupId', 64)->nullable();
            $table->string('examId', 64)->nullable();
            $table->string('subjectId', 64)->nullable();
            $table->string('studentId', 64)->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('change_set')->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'action', 'created_at'], 'result_lifecycle_entity_action_idx');
            $table->index(['examId', 'classId', 'sessionId'], 'result_lifecycle_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_lifecycle_events');
    }
};

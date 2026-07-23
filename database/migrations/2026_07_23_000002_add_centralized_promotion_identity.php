<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_archives', function (Blueprint $table) {
            $table->string('promotion_cycle_id', 36)->nullable()->after('exam_id');
            $table->unique(
                ['student_id', 'promotion_cycle_id'],
                'result_archives_student_cycle_unique'
            );
        });

        Schema::table('promotion_audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_id')->nullable()->after('student_id');
            $table->string('promotion_cycle_id', 36)->nullable()->after('exam_id');
            $table->string('engine', 32)->nullable()->after('promotion_cycle_id');
            $table->string('old_department')->nullable()->after('old_section');
            $table->string('new_department')->nullable()->after('new_section');
            $table->string('actor_context', 64)->nullable()->after('ip_address');
            $table->timestamp('reverted_at')->nullable()->after('actor_context');
            $table->unsignedBigInteger('reverted_by')->nullable()->after('reverted_at');
            $table->string('revert_cycle_id', 36)->nullable()->after('reverted_by');
            $table->string('revert_reason', 255)->nullable()->after('revert_cycle_id');

            $table->unique(
                ['student_id', 'promotion_cycle_id'],
                'promotion_audit_student_cycle_unique'
            );
            $table->index(
                ['promotion_cycle_id', 'engine', 'reverted_at'],
                'promotion_audit_cycle_state_idx'
            );
            $table->index('revert_cycle_id', 'promotion_audit_revert_cycle_idx');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_audit_logs', function (Blueprint $table) {
            $table->dropUnique('promotion_audit_student_cycle_unique');
            $table->dropIndex('promotion_audit_cycle_state_idx');
            $table->dropIndex('promotion_audit_revert_cycle_idx');
            $table->dropColumn([
                'exam_id',
                'promotion_cycle_id',
                'engine',
                'old_department',
                'new_department',
                'actor_context',
                'reverted_at',
                'reverted_by',
                'revert_cycle_id',
                'revert_reason',
            ]);
        });

        Schema::table('result_archives', function (Blueprint $table) {
            $table->dropUnique('result_archives_student_cycle_unique');
            $table->dropColumn('promotion_cycle_id');
        });
    }
};

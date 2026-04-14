<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tuition_fees', function (Blueprint $table) {
            if (!Schema::hasColumn('tuition_fees', 'fee_month')) {
                $table->date('fee_month')->nullable()->after('feesType');
                $table->index(['stdId', 'feesType', 'fee_month'], 'tuition_fee_month_idx');
            }

            if (!Schema::hasColumn('tuition_fees', 'due_amount')) {
                $table->decimal('due_amount', 10, 2)->nullable()->after('amount');
            }

            if (!Schema::hasColumn('tuition_fees', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('due_amount');
            }

            if (!Schema::hasColumn('tuition_fees', 'payment_status')) {
                $table->string('payment_status', 20)->nullable()->after('paid_amount');
            }

            if (!Schema::hasColumn('tuition_fees', 'class_id')) {
                $table->unsignedBigInteger('class_id')->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('tuition_fees', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('class_id');
            }

            if (!Schema::hasColumn('tuition_fees', 'session_id')) {
                $table->unsignedBigInteger('session_id')->nullable()->after('section_id');
            }

            if (!Schema::hasColumn('tuition_fees', 'collected_by')) {
                $table->unsignedBigInteger('collected_by')->nullable()->after('session_id');
            }

            if (!Schema::hasColumn('tuition_fees', 'note')) {
                $table->text('note')->nullable()->after('collected_by');
            }
        });

        DB::table('tuition_fees')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $createdDate = !empty($row->created_at) ? date('Y-m-01', strtotime((string) $row->created_at)) : date('Y-m-01');
                $legacyAmount = is_numeric($row->amount ?? null) ? (float) $row->amount : 0.0;

                DB::table('tuition_fees')
                    ->where('id', $row->id)
                    ->update([
                        'fee_month' => $row->fee_month ?? $createdDate,
                        'due_amount' => $row->due_amount ?? $legacyAmount,
                        'paid_amount' => $row->paid_amount ?? $legacyAmount,
                        'payment_status' => $row->payment_status ?? ($legacyAmount > 0 ? 'paid' : 'unpaid'),
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tuition_fees', function (Blueprint $table) {
            if (Schema::hasColumn('tuition_fees', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('tuition_fees', 'collected_by')) {
                $table->dropColumn('collected_by');
            }
            if (Schema::hasColumn('tuition_fees', 'session_id')) {
                $table->dropColumn('session_id');
            }
            if (Schema::hasColumn('tuition_fees', 'section_id')) {
                $table->dropColumn('section_id');
            }
            if (Schema::hasColumn('tuition_fees', 'class_id')) {
                $table->dropColumn('class_id');
            }
            if (Schema::hasColumn('tuition_fees', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('tuition_fees', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
            if (Schema::hasColumn('tuition_fees', 'due_amount')) {
                $table->dropColumn('due_amount');
            }
            if (Schema::hasColumn('tuition_fees', 'fee_month')) {
                try {
                    $table->dropIndex('tuition_fee_month_idx');
                } catch (\Throwable $e) {
                    // ignore index drop failures on environments where index is missing
                }
                $table->dropColumn('fee_month');
            }
        });
    }
};

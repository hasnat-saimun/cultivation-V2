<?php

use App\Services\ResultIntegrityPreflight;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $findings = app(ResultIntegrityPreflight::class)->blockingFindings();
        if ($findings !== []) {
            $summary = collect($findings)->map(fn (array $finding) => $finding['table'].'.'.$finding['code'].'='.$finding['count'])->implode(', ');
            throw new RuntimeException(
                'Result Engine integrity preconditions failed: '.$summary.
                '. Run php artisan result-engine:integrity-preflight for details.'
            );
        }
    }

    public function down(): void
    {
    }
};

<?php

namespace App\Console\Commands;

use App\Services\ResultIntegrityPreflight;
use Illuminate\Console\Command;

class ResultEngineIntegrityPreflight extends Command
{
    protected $signature = 'result-engine:integrity-preflight {--json : Emit machine-readable JSON}';
    protected $description = 'Read-only Result Engine identity and publication integrity diagnostics';

    public function handle(ResultIntegrityPreflight $preflight): int
    {
        $findings = $preflight->inspect();
        $blocked = collect($findings)->contains(fn (array $finding) => $finding['blocking'] && $finding['count'] > 0);

        if ($this->option('json')) {
            $this->line(json_encode([
                'status' => $blocked ? 'BLOCKED' : 'PASS',
                'database' => $this->laravel['db']->connection()->getDatabaseName(),
                'findings' => $findings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Code', 'Table', 'Count', 'Result'],
                collect($findings)->map(fn (array $finding) => [
                    $finding['code'],
                    $finding['table'],
                    $finding['count'],
                    $finding['count'] > 0
                        ? ($finding['blocking']
                            ? 'BLOCKED'
                            : (($finding['category'] ?? null) === 'historical_legacy_exception'
                                ? 'HISTORICAL WARNING'
                                : 'NORMALIZE'))
                        : 'PASS',
                ])->all()
            );
            foreach ($findings as $finding) {
                if ($finding['count'] > 0 && $finding['samples'] !== []) {
                    $this->warn($finding['code'].' limited samples: '.json_encode($finding['samples']));
                }
            }
            $blocked ? $this->error('BLOCKED: resolve every reported integrity finding before migration.')
                : $this->info('PASS: Result Engine integrity preconditions are satisfied.');
        }

        return $blocked ? self::FAILURE : self::SUCCESS;
    }
}

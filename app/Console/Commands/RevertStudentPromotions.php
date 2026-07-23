<?php

namespace App\Console\Commands;

use App\Services\ResultCalculation\CentralizedPromotionReverter;
use App\Services\ResultCalculation\PromotionRevertException;
use Illuminate\Console\Command;

class RevertStudentPromotions extends Command
{
    protected $signature = 'students:promotion-revert
        {--promotion-cycle= : Required centralized promotion cycle ID}
        {--student=* : Selected student database ID; repeatable}
        {--all : Select every student in the promotion cycle}
        {--engine=centralized : Must be centralized}
        {--reason= : Optional operational reason}
        {--dry-run : Validate and preview without writing}';

    protected $description = 'Dry-run or atomically revert a centralized promotion cycle.';

    public function handle(CentralizedPromotionReverter $reverter): int
    {
        if (strtolower((string)$this->option('engine')) !== 'centralized') {
            $this->error('--engine must be centralized.');
            return self::INVALID;
        }
        $cycle = trim((string)$this->option('promotion-cycle'));
        if ($cycle === '') {
            $this->error('--promotion-cycle is required.');
            return self::INVALID;
        }
        $students = collect($this->option('student'))->filter(fn ($id) =>
            is_numeric($id) && (int)$id > 0
        )->map(fn ($id) => (int)$id)->unique()->values()->all();
        if (!$this->option('all') && $students === []) {
            $this->error('Use at least one --student or explicitly pass --all.');
            return self::INVALID;
        }

        try {
            $report = $reverter->process(
                $cycle, $students, (bool)$this->option('all'), (bool)$this->option('dry-run'),
                'artisan', $this->option('reason')
            );
            $this->table(
                ['Cycle','Checked','Would restore','Would mark reverted','Restored','Audits reverted','Revert cycle'],
                [[$report['promotionCycleId'],$report['studentsChecked'],$report['wouldRestoreCount'],
                    $report['wouldMarkRevertedCount'],$report['studentsRestored'],$report['auditsMarkedReverted'],
                    $report['revertCycleId'] ?? '-']]
            );
            if ($report['dryRun']) {
                $this->info('Dry-run complete: no records were modified. Real revert safe: '.($report['writeSafe']?'yes':'no'));
                foreach ($report['restorePayloads'] as $payload) {
                    $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES));
                }
            } else {
                $this->info('Centralized promotion revert committed successfully.');
            }
            return self::SUCCESS;
        } catch (PromotionRevertException $exception) {
            foreach ($exception->report['blockingErrors'] as $error) {
                $this->error($error['code'].': '.$error['message']
                    .(isset($error['studentId'])?' [student '.$error['studentId'].']':''));
            }
            $this->error('No promotion revert records were modified.');
            return self::FAILURE;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\ResultCalculation\PromotionPreviewBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PreviewResultEnginePromotions extends Command
{
    protected $signature = 'result-engine:promotion-preview
        {--exam= : Required exam ID}
        {--class= : Required source class ID}
        {--session= : Required source session ID}
        {--to-class= : Required destination class ID}
        {--to-session= : Required destination session ID}
        {--section= : Optional source section/group ID}
        {--group= : Alias for source section/group ID}
        {--department= : Optional source department ID}
        {--to-section= : Optional destination section/group ID}
        {--to-department= : Optional destination department ID}
        {--student= : Optional student database ID}
        {--limit=100 : Maximum students to inspect}
        {--all : Include unchanged safe rows}';

    protected $description = 'Read-only centralized promotion eligibility, destination-conflict and legacy-policy preview.';

    public function handle(PromotionPreviewBuilder $builder): int
    {
        foreach (['exam', 'class', 'session', 'to-class', 'to-session'] as $required) {
            if (!$this->positive($this->option($required))) {
                $this->error('--exam, --class, --session, --to-class and --to-session are required positive integer IDs.');
                return self::INVALID;
            }
        }

        $sourceSection = $this->id('section') ?? $this->id('group');
        try {
            $preview = $builder->build(
                (int) $this->option('exam'), (int) $this->option('class'), (int) $this->option('session'),
                (int) $this->option('to-class'), (int) $this->option('to-session'),
                $sourceSection, $this->id('department'), $this->id('to-section'), $this->id('to-department'),
                $this->id('student'), max(1, min(10000, (int) $this->option('limit')))
            );
        } catch (Throwable $exception) {
            Log::error('Promotion preview command failed safely.', [
                'exam_id' => $this->option('exam'), 'source_class_id' => $this->option('class'),
                'source_session_id' => $this->option('session'), 'destination_class_id' => $this->option('to-class'),
                'destination_session_id' => $this->option('to-session'), 'section_id' => $sourceSection,
                'student_id' => $this->id('student'), 'exception' => get_class($exception), 'stage' => 'preview_command',
            ]);
            $this->error('Promotion preview failed safely; no records were modified.');
            return self::FAILURE;
        }
        $rows = collect($preview['rows']);
        if (!$this->option('all')) {
            $rows = $rows->filter(fn ($row) => $row['eligibilityDiffers'] || $row['blockingReasons'] !== []
                || $row['warningReasons'] !== [] || $row['differenceReasons'] !== []);
        }
        if ($rows->isNotEmpty()) {
            $this->table(
                ['Student','Roll','Legacy','Central','GPA L/C','Placement','Destination','Proposed roll','Blockers','Warnings','Differences'],
                $rows->map(fn ($row) => [
                    $row['studentId'], $row['roll'] ?? '-', $row['legacyStatus'], $row['centralizedStatus'],
                    ($row['legacyGpa'] ?? '-').'/'.($row['centralizedGpa'] ?? '-'),
                    ($row['placementStatus'] ?? 'Missing').'/'.($row['placementPosition'] ?? '-'),
                    $row['destinationSessionId'].'/'.$row['destinationClassId'].'/'.($row['destinationSectionId'] ?? '-'),
                    $row['proposedRoll'] ?? '-', implode(',', $row['blockingReasons']),
                    implode(',', $row['warningReasons']), implode(',', $row['differenceReasons']),
                ])->all()
            );
        }
        $summary = $preview['summary'];
        $this->table(
            ['Checked','Pass','Fail','Incomplete','Legacy eligible','Central eligible','Differences','Already promoted','Destination conflicts','Roll conflicts','Archive conflicts','Missing placement','Duplicate source','Blockers','Warnings','Calc errors','Phase 9 eligible'],
            [[
                $summary['studentsChecked'], $summary['centralizedPass'], $summary['centralizedFail'], $summary['centralizedIncomplete'],
                $summary['legacyEligible'], $summary['centralizedNormallyEligible'], $summary['eligibilityDifferences'],
                $summary['alreadyPromoted'], $summary['destinationConflicts'], $summary['rollConflicts'],
                $summary['archiveConflicts'], $summary['missingPlacement'], $summary['duplicateSourceRecords'],
                $summary['blockingErrors'], $summary['nonBlockingWarnings'], $summary['calculationErrors'],
                $summary['phase9WouldBeEligible'],
            ]]
        );
        foreach ($summary['scopeBlockers'] as $issue) $this->error($issue['code'].': '.$issue['message']);
        foreach ($summary['scopeWarnings'] as $issue) $this->warn($issue['code'].': '.$issue['message']);
        $this->info('Read-only promotion preview complete: no records were modified.');
        $this->line('Phase 9 write currently safe: '.($summary['phase9WriteSafe'] ? 'yes' : 'no'));
        return $summary['calculationErrors'] > 0 || $summary['scopeBlockers'] !== [] ? self::FAILURE : self::SUCCESS;
    }

    private function id(string $name): ?int { return $this->positive($this->option($name)) ? (int) $this->option($name) : null; }
    private function positive(mixed $value): bool { return is_numeric($value) && (int) $value > 0; }
}

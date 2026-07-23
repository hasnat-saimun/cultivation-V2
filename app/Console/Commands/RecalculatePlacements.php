<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Marksheet;
use App\Models\Placement;
use App\Models\newAdmission;
use App\Services\ResultCalculation\CentralizedPlacementRecalculator;
use App\Services\ResultCalculation\PlacementRecalculationException;

class RecalculatePlacements extends Command
{
    protected $signature = 'placements:recalculate
        {sessionId? : Legacy positional session ID}
        {classId? : Legacy positional class ID}
        {examId? : Legacy positional exam ID}
        {groupId? : Legacy positional group ID}
        {--exam= : Exam ID}
        {--class= : Class ID}
        {--session= : Session ID}
        {--section= : Optional section/group ID}
        {--department= : Optional department ID}
        {--engine=legacy : legacy or centralized}
        {--dry-run : Build and validate without writing}
        {--force : Deliberately overwrite a published scope}';
    protected $description = 'Recalculate GPA-based placements for a given session/class/exam/group.';

    public function handle(CentralizedPlacementRecalculator $centralized): int
    {
        $engine = strtolower((string) $this->option('engine'));
        if (!in_array($engine, ['legacy', 'centralized'], true)) {
            $this->error('--engine must be legacy or centralized.');
            return self::INVALID;
        }
        $sessionId = (string) ($this->option('session') ?: $this->argument('sessionId'));
        $classId = (string) ($this->option('class') ?: $this->argument('classId'));
        $examId = (string) ($this->option('exam') ?: $this->argument('examId'));
        $groupId = $this->option('section') ?: $this->argument('groupId');
        $groupId = $groupId ? (string) $groupId : null;

        if ($engine === 'centralized') {
            if (!$this->positive($examId) || !$this->positive($classId) || !$this->positive($sessionId)) {
                $this->error('--exam, --class and --session are required positive integer IDs.');
                return self::INVALID;
            }
            try {
                $report = $centralized->recalculate(
                    (int) $examId, (int) $classId, (int) $sessionId,
                    $this->positive($groupId) ? (int) $groupId : null,
                    $this->positive($this->option('department')) ? (int) $this->option('department') : null,
                    (bool) $this->option('dry-run'), (bool) $this->option('force'), 'artisan',
                );
                $this->line('Engine: centralized');
                $this->line('Ranking method: '.$report['rankingMethod']);
                $this->table(['Checked','Pass','Fail','Incomplete','GPA changes','Status changes','Rank changes','New','Replace','Would write'], [[
                    $report['studentsChecked'], $report['passedRanked'], $report['failedRanked'], $report['incompleteUnranked'],
                    $report['gpaChanges'], $report['statusChanges'], $report['rankChanges'], $report['newRows'],
                    $report['rowsToReplace'], $report['wouldWriteCount'],
                ]]);
                foreach ($report['warnings'] as $warning) $this->warn($warning['code'].': '.$warning['message']);
                if ($report['dryRun']) $this->info('Dry-run complete: no records were modified. Real write permitted: '.($report['writePermitted'] ? 'yes' : 'no'));
                else $this->info("Centralized placements replaced: {$report['rowsReplaced']}; inserted: {$report['rowsInserted']}");
                return self::SUCCESS;
            } catch (PlacementRecalculationException $exception) {
                foreach ($exception->report['blockingErrors'] as $error) $this->error($error['code'].': '.$error['message']);
                $this->error('No placement records were modified.');
                return self::FAILURE;
            }
        }

        if ($sessionId === '' || $classId === '' || $examId === '') {
            $this->error('Legacy mode requires sessionId, classId and examId.');
            return self::INVALID;
        }

        $marksQuery = Marksheet::query()
            ->where('sessionId', $sessionId)
            ->where('classId', $classId)
            ->where('examId', $examId);
        if ($groupId !== null) { $marksQuery->where('groupId', $groupId); }

        $marks = $marksQuery->get();

        // Wipe existing
        $wipeQuery = Placement::query()
            ->where('sessionId', $sessionId)
            ->where('classId', $classId)
            ->where('examId', $examId);
        if ($groupId !== null) { $wipeQuery->where('groupId', $groupId); }
        $deleted = $wipeQuery->delete();
        $this->info("Deleted {$deleted} old placement rows");

        $grouped = $marks->groupBy('studentId');

        $rows = [];
        foreach ($grouped as $studentId => $items) {
            $subjectsCount = $items->count();
            $totalGradePoints = $items->reduce(fn($c, $i) => $c + (float) ($i->gradePoint ?? 0), 0.0);
            $totalMarks = $items->reduce(fn($c, $i) => $c + (int) ($i->totalMarks ?? 0), 0);
            $gpa = $subjectsCount > 0 ? round($totalGradePoints / $subjectsCount, 2) : 0.0;
            $hasFail = $items->contains(fn($i) => (float) ($i->gradePoint ?? 0) <= 0.0);
            $status = $hasFail ? 'Fail' : 'Pass';
            $admission = newAdmission::query()->find($studentId);
            $roll = $admission?->rollNumber ?? null;
            $rows[] = compact('studentId', 'sessionId', 'classId', 'groupId', 'examId', 'subjectsCount', 'totalGradePoints', 'gpa', 'totalMarks', 'status') + ['_roll' => $roll];
        }

        usort($rows, function ($a, $b) {
            if ($a['gpa'] === $b['gpa']) {
                if ($a['totalMarks'] === $b['totalMarks']) {
                    return ($a['_roll'] ?? PHP_INT_MAX) <=> ($b['_roll'] ?? PHP_INT_MAX);
                }
                return $b['totalMarks'] <=> $a['totalMarks'];
            }
            return $b['gpa'] <=> $a['gpa'];
        });

        $position = 1;
        foreach ($rows as $row) {
            Placement::create([
                'studentId' => (string) $row['studentId'],
                'sessionId' => $row['sessionId'],
                'classId' => $row['classId'],
                'groupId' => $row['groupId'],
                'examId' => $row['examId'],
                'subjectsCount' => $row['subjectsCount'],
                'totalGradePoints' => $row['totalGradePoints'],
                'gpa' => $row['gpa'],
                'totalMarks' => $row['totalMarks'],
                'position' => $position++,
                'status' => $row['status'],
            ]);
        }

        $this->info('Placements recalculated: ' . count($rows) . ' students');
        return self::SUCCESS;
    }

    private function positive(mixed $value): bool
    {
        return is_numeric($value) && (int) $value > 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\ResultCalculation\PlacementPreviewBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PreviewResultEnginePlacements extends Command
{
    protected $signature = 'result-engine:placement-preview
        {--exam= : Required exam ID}
        {--class= : Required class ID}
        {--session= : Required session ID}
        {--section= : Optional section/group ID}
        {--department= : Optional department ID}
        {--student= : Optional student database ID}
        {--limit=100 : Maximum students to inspect}
        {--all : Include unchanged rows}';

    protected $description = 'Read-only comparison of stored legacy placement data and centralized result-engine ranking inputs.';

    public function handle(PlacementPreviewBuilder $builder): int
    {
        $examId = $this->positiveOption('exam'); $classId = $this->positiveOption('class'); $sessionId = $this->positiveOption('session');
        if (!$examId || !$classId || !$sessionId) {
            $this->error('--exam, --class and --session are required positive integer IDs.');
            return self::INVALID;
        }
        $sectionId = $this->positiveOption('section'); $departmentId = $this->positiveOption('department'); $studentId = $this->positiveOption('student');
        $limit = max(1, min(10000, (int) $this->option('limit')));
        $connection = config('database.default'); $database = config("database.connections.{$connection}.database");
        $this->line("Read-only connection: {$connection} / {$database}");

        try {
            $preview = $builder->build($examId, $classId, $sessionId, $sectionId, $departmentId, $studentId, $limit);
        } catch (\Throwable $exception) {
            Log::error('Centralized placement preview aborted.', [
                'exam_id' => $examId, 'class_id' => $classId, 'session_id' => $sessionId,
                'section_id' => $sectionId, 'student_id' => $studentId,
                'exception' => get_class($exception),
            ]);
            $this->error('Centralized placement preview aborted; no ranking was produced and no records were modified.');
            $this->table(['Checked', 'Existing', 'GPA diff', 'Status diff', 'Rank diff', 'New ineligible', 'New eligible', 'Incomplete', 'Duplicate placements', 'Errors'], [[0,0,0,0,0,0,0,0,0,1]]);
            return self::FAILURE;
        }

        $rows = collect($preview['rows']);
        if (!$this->option('all')) $rows = $rows->filter(fn ($row) => $row['gpaChanged'] || $row['statusChanged'] || $row['rankChanged'] || $row['reasons'] !== []);
        if ($rows->isNotEmpty()) {
            $this->table(['Student', 'L GPA', 'New GPA', 'L Status', 'New Status', 'Subjects L/N', 'Total legacy/compulsory', 'Rank old/new', 'Reasons'],
                $rows->map(fn ($row) => [
                    $row['studentId'], $row['legacyGpa'] ?? '-', $row['centralizedGpa'] ?? '-', $row['legacyStatus'], $row['centralizedStatus'],
                    $row['legacySubjectCount'].'/'.$row['centralizedCompulsorySubjectCount'],
                    $row['legacyTotalMarks'].'/'.$row['compulsoryOnlyTotal'],
                    ($row['existingRank'] ?? '-').'/'.($row['previewRank'] ?? '-'), implode(',', $row['reasons']),
                ])->all());
        }
        $summary = $preview['summary'];
        $this->newLine();
        $this->table(['Checked', 'Existing', 'GPA diff', 'Status diff', 'Rank diff', 'New ineligible', 'New eligible', 'Incomplete', 'Duplicate placements', 'Errors'], [[
            $summary['studentsChecked'], $summary['existingPlacementsFound'], $summary['gpaDifferences'], $summary['statusDifferences'],
            $summary['rankDifferences'], $summary['newlyIneligible'], $summary['newlyEligible'], $summary['incompleteStudents'],
            $summary['duplicatePlacementRows'], $summary['calculationErrors'],
        ]]);
        return self::SUCCESS;
    }

    private function positiveOption(string $name): ?int
    {
        $value = $this->option($name);
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}

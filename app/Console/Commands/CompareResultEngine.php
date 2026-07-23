<?php

namespace App\Console\Commands;

use App\Http\Controllers\MarksheetController;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\ReligiousSubjectDefault;
use App\Models\Subject;
use App\Services\ResultCalculation\BoardResultCalculator;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

final class CompareResultEngine extends Command
{
    protected $signature = 'result-engine:compare
        {--exam= : Required exam ID}
        {--class= : Optional class ID}
        {--session= : Optional session ID}
        {--student= : Optional student database ID}
        {--limit=100 : Maximum students to inspect}';

    protected $description = 'Read-only comparison of legacy tabulation and the shadow Board result engine.';

    public function handle(BoardResultCalculator $calculator, MarksheetController $legacyController): int
    {
        if (!config('result_engine.shadow_mode', false)) {
            $this->error('Shadow mode is disabled. Set RESULT_ENGINE_SHADOW_MODE=true explicitly.');
            return self::FAILURE;
        }
        $examId = filter_var($this->option('exam'), FILTER_VALIDATE_INT);
        if (!$examId || $examId < 1) {
            $this->error('--exam is required and must be a positive integer.');
            return self::INVALID;
        }
        $limit = max(1, min(10000, (int) $this->option('limit')));
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        if (!$connection || !$database) {
            $this->error('Selected database connection is not fully configured.');
            return self::FAILURE;
        }
        $this->line("Read-only connection: {$connection} / {$database}");

        $base = Marksheet::query()->where('examId', $examId)
            ->when($this->option('class'), fn ($q) => $q->where('classId', (int) $this->option('class')))
            ->when($this->option('session'), fn ($q) => $q->where('sessionId', (int) $this->option('session')))
            ->when($this->option('student'), fn ($q) => $q->where('studentId', (int) $this->option('student')));
        $contexts = (clone $base)->select('classId', 'sessionId', 'groupId')->distinct()->get();
        if ($contexts->isEmpty()) {
            $this->warn('No marks found for the selected scope.');
            return self::SUCCESS;
        }

        $checked = $gpaDiffs = $statusDiffs = $optionalDiffs = $missingDiffs = $errors = 0;
        $rows = [];
        foreach ($contexts as $context) {
            if ($checked >= $limit) break;
            try {
                $request = Request::create('/marksheet/all', 'GET', [
                    'examId' => $examId, 'classId' => $context->classId,
                    'sessionId' => $context->sessionId, 'sectionId' => $context->groupId,
                ]);
                $legacyData = $legacyController->allMarksheet($request)->getData();
                $legacyRows = collect(array_merge($legacyData['passResults'], $legacyData['failResults'], $legacyData['incompleteResults']))
                    ->keyBy(fn ($row) => (int) $row['student']->id);
                $marks = (clone $base)->where('classId', $context->classId)->where('sessionId', $context->sessionId)
                    ->when($context->groupId, fn ($q) => $q->where('groupId', $context->groupId))->get();
                $studentIds = $marks->pluck('studentId')->unique()->take($limit - $checked);
                $students = newAdmission::whereIn('id', $studentIds)->get()->keyBy('id');
                $subjects = Subject::whereIn('id', $marks->pluck('subjectId')->unique())->get();

                foreach ($studentIds as $studentId) {
                    $student = $students->get((int) $studentId); $legacy = $legacyRows->get((int) $studentId);
                    if (!$student || !$legacy) continue;
                    $studentSubjects = $this->subjectsForStudent($subjects, $student);
                    $result = $calculator->calculate($student, (object) ['passingSystem' => optional($legacyData['exam'])->passingSystem], $marks->where('studentId', $studentId), $studentSubjects);
                    $legacyGpa = is_numeric($legacy['finalGpa']) ? (float) $legacy['finalGpa'] : null;
                    $legacyStatus = !empty($legacy['isIncomplete']) ? 'Incomplete' : (!empty($legacy['isFail']) ? 'Fail' : 'Pass');
                    $legacyBonus = $this->legacyOptionalBonus($legacy, $student);
                    $legacyCount = (int) ($legacy['markedSubjectsCount'] ?? count($legacy['subjects'] ?? []));
                    $legacyFailed = collect($legacy['subjects'] ?? [])->where('grade', 'F')->count();
                    $legacyMissing = !empty($legacy['isIncomplete']) ? 1 : 0;

                    $gpaDifferent = $legacyGpa !== $result->gpa;
                    $statusDifferent = $legacyStatus !== $result->status;
                    $optionalDifferent = abs($legacyBonus - $result->optionalBonus) > 0.0001;
                    $missingDifferent = $legacyMissing !== count($result->missingCompulsorySubjects);
                    $failedDifferent = $legacyFailed !== count($result->failedCompulsorySubjects);
                    $countDifferent = $legacyCount !== $result->compulsorySubjectCount;
                    if ($gpaDifferent || $statusDifferent || $optionalDifferent || $missingDifferent || $failedDifferent || $countDifferent) {
                        $rows[] = [$student->id, $legacyGpa ?? '-', $result->gpa ?? '-', $legacyStatus, $result->status, "{$legacyBonus}/{$result->optionalBonus}", "{$legacyCount}/{$result->compulsorySubjectCount}", "{$legacyFailed}/".count($result->failedCompulsorySubjects), "{$legacyMissing}/".count($result->missingCompulsorySubjects)];
                    }
                    $checked++; $gpaDiffs += (int) $gpaDifferent; $statusDiffs += (int) $statusDifferent;
                    $optionalDiffs += (int) $optionalDifferent; $missingDiffs += (int) $missingDifferent;
                    if ($checked >= $limit) break;
                }
            } catch (Throwable $e) {
                $errors++; $this->warn('Context comparison error: '.$e->getMessage());
            }
        }
        if ($rows !== []) $this->table(['Student', 'Legacy GPA', 'New GPA', 'Legacy', 'New', 'Bonus L/N', 'Subjects L/N', 'Failed L/N', 'Missing L/N'], $rows);
        $this->newLine();
        $this->table(['Checked', 'GPA differences', 'Status differences', 'Optional differences', 'Missing differences', 'Errors'], [[$checked, $gpaDiffs, $statusDiffs, $optionalDiffs, $missingDiffs, $errors]]);
        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function subjectsForStudent(Collection $subjects, newAdmission $student): Collection
    {
        $religiousId = (int) ($student->religiousSubjectId ?: ReligiousSubjectDefault::where('classId', $student->className)->value('subjectId'));
        return $subjects->filter(function ($subject) use ($student, $religiousId) {
            if ($subject->isReligious && $religiousId > 0) return (int) $subject->id === $religiousId;
            if (strcasecmp((string) $subject->subjectType, 'Optional') === 0) return (int) $subject->id === (int) $student->fourthSubjectId;
            return true;
        })->values();
    }

    private function legacyOptionalBonus(array $legacy, newAdmission $student): float
    {
        foreach ($legacy['subjects'] ?? [] as $subject) {
            if (($subject['type'] ?? '') !== 'Optional') continue;
            if (!in_array((int) $student->fourthSubjectId, array_map('intval', $subject['sourceIds'] ?? [(int) ($subject['id'] ?? 0)]), true)) continue;
            $gp = is_numeric($subject['gradePoint'] ?? null) ? (float) $subject['gradePoint'] : 0.0;
            return max($gp - 2.0, 0.0);
        }
        return 0.0;
    }
}

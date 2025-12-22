<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Marksheet;
use App\Models\Placement;
use App\Models\newAdmission;

class RecalculatePlacements extends Command
{
    protected $signature = 'placements:recalculate {sessionId} {classId} {examId} {groupId?}';
    protected $description = 'Recalculate GPA-based placements for a given session/class/exam/group.';

    public function handle(): int
    {
        $sessionId = (string) $this->argument('sessionId');
        $classId = (string) $this->argument('classId');
        $examId = (string) $this->argument('examId');
        $groupId = $this->argument('groupId') ? (string) $this->argument('groupId') : null;

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
}

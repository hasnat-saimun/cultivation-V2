<?php

namespace App\Services\ResultCalculation;

class TabulationResultPresenter
{
    public function __construct(private TranscriptResultPresenter $transcriptPresenter) {}

    public function present(array $entries): array
    {
        $rows = [];
        $columnNames = [];
        foreach ($entries as $entry) {
            /** @var StudentResult $result */
            $result = $entry['result'];
            $presented = $this->transcriptPresenter->present($result, $entry['subjects'], $entry['student']->marksheet);
            $subjectRows = [];
            foreach (array_merge($presented['mainRows'], $presented['optionalRows']) as $subjectRow) {
                $columnNames[$subjectRow['name']] = true;
                $subjectRows[] = [
                    'name' => $subjectRow['name'],
                    'type' => $this->subjectType($subjectRow['name'], $presented['optionalRows']),
                    'cq' => $subjectRow['cq'], 'mcq' => $subjectRow['mcq'], 'practical' => $subjectRow['practical'],
                    'total' => $subjectRow['total'], 'grade' => $subjectRow['grade'], 'gradePoint' => $subjectRow['gradePoint'],
                    'status' => $subjectRow['status'], 'componentFailures' => $subjectRow['componentFailures'],
                    'cqGrade' => '-', 'mcqGrade' => '-', 'prGrade' => '-',
                ];
            }
            $rows[] = [
                'student' => $entry['student'], 'subjects' => $subjectRows,
                'totalMarks' => $presented['totalMarks'],
                'finalGpa' => $result->gpa === null ? null : number_format($result->gpa, 2),
                'finalLetter' => $presented['letterGrade'],
                'status' => $result->status,
                'isFail' => $result->status === 'Fail', 'isIncomplete' => $result->status === 'Incomplete',
                'subjectFails' => count($result->failedCompulsorySubjects),
                'subjectMissing' => count($result->missingCompulsorySubjects),
                'optionalBonus' => $result->optionalBonus,
                'failedCompulsorySubjects' => $result->failedCompulsorySubjects,
                'missingCompulsorySubjects' => $result->missingCompulsorySubjects,
                'componentFailures' => $presented['componentFailures'],
                '_studentResult' => $result,
            ];
        }
        $columns = array_map(fn ($name) => (object) ['subjectName' => $name], array_keys($columnNames));
        usort($columns, fn ($a, $b) => strcasecmp($a->subjectName, $b->subjectName));
        return ['rows' => $rows, 'subjects' => collect($columns)];
    }

    public function summarize(array $rows, $subjects): array
    {
        $total = count($rows);
        $counts = ['Pass' => 0, 'Fail' => 0, 'Incomplete' => 0];
        $gpaDistribution = ['5.00' => 0, '4.00-4.99' => 0, '3.50-3.99' => 0, '3.00-3.49' => 0, '2.00-2.99' => 0, '1.00-1.99' => 0, 'Fail' => 0, 'Incomplete' => 0];
        $gradeDistribution = [];
        $failureBuckets = [];
        foreach ($rows as $row) {
            $status = $row['status']; $counts[$status]++;
            if ($status !== 'Pass') $gpaDistribution[$status]++;
            else $gpaDistribution[$this->gpaBucket((float) $row['finalGpa'])]++;
            $gradeDistribution[$row['finalLetter']] = ($gradeDistribution[$row['finalLetter']] ?? 0) + 1;
            $failed = (int) $row['subjectFails'];
            if ($failed > 0) $failureBuckets[$failed] = ($failureBuckets[$failed] ?? 0) + 1;
        }
        ksort($failureBuckets);

        $subjectStats = [];
        foreach ($subjects as $subject) {
            $name = (string) $subject->subjectName; $appeared = $pass = $fail = $missing = 0;
            foreach ($rows as $row) {
                $cell = collect($row['subjects'])->firstWhere('name', $name);
                if (!$cell || ($cell['status'] ?? null) === 'Incomplete') { $missing++; continue; }
                $appeared++;
                if (($cell['status'] ?? null) === 'Fail') $fail++; else $pass++;
            }
            $subjectStats[] = ['subjectName' => $name, 'appeared' => $appeared, 'pass' => $pass, 'fail' => $fail, 'missing' => $missing,
                'passRate' => $appeared ? round($pass / $appeared * 100, 2) : 0.0,
                'failRate' => $appeared ? round($fail / $appeared * 100, 2) : 0.0];
        }
        return [
            'overallSummary' => ['total' => $total, 'present' => $counts['Pass'] + $counts['Fail'], 'absent' => $counts['Incomplete'],
                'pass' => $counts['Pass'], 'fail' => $counts['Fail'], 'incomplete' => $counts['Incomplete'],
                'passPercentage' => $total ? round($counts['Pass'] / $total * 100, 2) : 0.0,
                'failPercentage' => $total ? round($counts['Fail'] / $total * 100, 2) : 0.0,
                'incompletePercentage' => $total ? round($counts['Incomplete'] / $total * 100, 2) : 0.0],
            'subjectStats' => $subjectStats, 'failureBuckets' => $failureBuckets,
            'gpaDistribution' => $gpaDistribution, 'gradeDistribution' => $gradeDistribution,
        ];
    }

    private function subjectType(string $name, array $optionalRows): string
    {
        return collect($optionalRows)->contains(fn ($row) => $row['name'] === $name) ? 'Optional' : 'Main';
    }

    private function gpaBucket(float $gpa): string
    {
        if ($gpa >= 5) return '5.00'; if ($gpa >= 4) return '4.00-4.99'; if ($gpa >= 3.5) return '3.50-3.99';
        if ($gpa >= 3) return '3.00-3.49'; if ($gpa >= 2) return '2.00-2.99'; return '1.00-1.99';
    }
}

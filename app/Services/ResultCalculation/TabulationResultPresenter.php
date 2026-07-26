<?php

namespace App\Services\ResultCalculation;

use App\Models\GradeList;

class TabulationResultPresenter
{
    public function __construct(private TranscriptResultPresenter $transcriptPresenter) {}

    public function present(array $entries): array
    {
        $rows = [];
        $columnDefinitions = [];
        $gradeRows = GradeList::all();
        foreach ($entries as $entry) {
            /** @var StudentResult $result */
            $result = $entry['result'];
            $presented = $this->transcriptPresenter->presentWithGradeRows(
                $result, $entry['subjects'], $entry['student']->marksheet, $gradeRows
            );
            $subjectRows = [];
            foreach (array_merge($presented['mainRows'], $presented['optionalRows']) as $subjectRow) {
                $sourceSubjects = collect($entry['subjects'])->whereIn('id', $subjectRow['sourceIds']);
                $components = [];
                if ($sourceSubjects->contains(fn ($subject) => (float) ($subject->CQ ?? 0) > 0)) $components[] = ['key' => 'cq', 'label' => 'CQ'];
                if ($sourceSubjects->contains(fn ($subject) => (float) ($subject->MCQ ?? 0) > 0)) $components[] = ['key' => 'mcq', 'label' => 'MCQ'];
                if ($sourceSubjects->contains(fn ($subject) => (float) ($subject->Practical ?? 0) > 0)) $components[] = ['key' => 'practical', 'label' => 'PR'];
                if ($components === []) $components[] = ['key' => 'total', 'label' => 'Total'];
                $columnDefinitions[$subjectRow['name']] ??= [
                    'subjectName' => $subjectRow['name'],
                    'paired' => $subjectRow['paired'],
                    'optional' => $subjectRow['isOptional'],
                    'componentColumns' => $components,
                    'componentColumnCount' => count($components),
                ];
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
                'studentIdentity' => [
                    'id' => $entry['student']->id,
                    'roll' => (string) ($entry['student']->rollNumber ?? ''),
                    'name' => trim(($entry['student']->fullName ?? '').' '.($entry['student']->sureName ?? '')),
                ],
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
        $columns = array_map(fn ($definition) => (object) $definition, array_values($columnDefinitions));
        usort($columns, fn ($a, $b) => strcasecmp($a->subjectName, $b->subjectName));
        foreach ($rows as &$row) {
            $row['cells'] = collect($row['subjects'])->keyBy('name')->all();
            $row['subjectsCompact'] = array_values(array_filter($row['subjects'], fn ($subject) => is_numeric($subject['total'])));
        }
        unset($row);
        $sections = ['Pass' => [], 'Fail' => [], 'Incomplete' => []];
        foreach ($rows as $row) $sections[$row['status']][] = $row;
        $failureBuckets = [];
        foreach ($sections['Fail'] as $row) {
            $count = (int) $row['subjectFails'];
            if ($count > 0) $failureBuckets[$count] = ($failureBuckets[$count] ?? 0) + 1;
        }
        ksort($failureBuckets);
        $tabulationPages = $this->pageRowsByStatus($sections, 18);
        $glancePageSize = collect($columns)->sum('componentColumnCount') >= 14 ? 18 : 24;
        $glancePages = $this->pageRows($rows, $glancePageSize);
        return [
            'rows' => $rows,
            'subjects' => collect($columns),
            'sections' => $sections,
            'failureBuckets' => $failureBuckets,
            'tabulationPages' => $tabulationPages,
            'glancePages' => $glancePages,
        ];
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
            'subjectPages' => $this->pageSubjectRows($subjectStats, 22),
            'failureSummaryLine' => $this->failureSummaryLine($failureBuckets),
        ];
    }

    private function pageRowsByStatus(array $sections, int $size): array
    {
        $pages = [];
        foreach (['Pass', 'Fail', 'Incomplete'] as $status) {
            foreach (array_chunk($sections[$status], $size) as $rows) {
                $pages[] = ['status' => $status, 'rows' => $rows];
            }
        }
        return $this->numberPages($pages);
    }

    private function pageRows(array $rows, int $size): array
    {
        return $this->numberPages(array_map(fn ($chunk) => ['rows' => $chunk], array_chunk($rows, $size)));
    }

    private function pageSubjectRows(array $rows, int $size): array
    {
        $pages = array_map(fn ($chunk) => ['subjectRows' => $chunk], array_chunk($rows, $size));
        if ($pages === []) $pages = [['subjectRows' => []]];
        return $this->numberPages($pages);
    }

    private function numberPages(array $pages): array
    {
        $count = count($pages);
        foreach ($pages as $index => &$page) {
            $page['pageNumber'] = $index + 1;
            $page['pageCount'] = $count;
        }
        unset($page);
        return $pages;
    }

    private function failureSummaryLine(array $buckets): string
    {
        if ($buckets === []) return 'No failed-subject bucket found.';
        $parts = [];
        foreach ($buckets as $failed => $students) {
            $parts[] = $failed.' Subject'.($failed === 1 ? '' : 's').'-'.str_pad((string) $students, 2, '0', STR_PAD_LEFT);
        }
        return implode(', ', $parts);
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

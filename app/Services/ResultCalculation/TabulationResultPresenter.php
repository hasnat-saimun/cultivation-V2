<?php

namespace App\Services\ResultCalculation;

use App\Models\Subject;
use App\Models\GradeList;

class TabulationResultPresenter
{
    public function __construct(
        private TranscriptResultPresenter $transcriptPresenter,
        private ResultMeritPositionService $meritPositionService,
    ) {}

    public function present(array $entries): array
    {
        $rows = [];
        $columnDefinitions = [];
        $gradeRows = GradeList::all();
        $meritPositions = $this->meritPositionService->positions($entries);
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
                $columnDefinitions[$subjectRow['cellKey']] ??= [
                    'cellKey' => $subjectRow['cellKey'],
                    'subject_id' => $subjectRow['cellKey'],
                    'display_name' => SubjectHeaderFormatter::shortLabel((string) $subjectRow['name']),
                    'full_name' => SubjectHeaderFormatter::normalizeName((string) $subjectRow['name']),
                    'is_fourth_subject' => $subjectRow['isOptional'],
                    'subjectName' => $subjectRow['name'],
                    'paired' => $subjectRow['paired'],
                    'optional' => $subjectRow['isOptional'],
                    'componentColumns' => $components,
                    'componentColumnCount' => count($components),
                    'sortOrder' => (int) ($subjectRow['sortOrder'] ?? PHP_INT_MAX),
                ];
                $subjectRows[] = [
                    'cellKey' => $subjectRow['cellKey'],
                    'name' => $subjectRow['name'],
                    'type' => $subjectRow['type'],
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
                'classification' => $presented['classification'],
                'reportStatus' => $presented['classification'] === 'Complete'
                    ? $result->status
                    : $presented['classification'],
                'meritPosition' => $meritPositions[(int) $entry['student']->id] ?? null,
                'isFail' => $result->status === 'Fail', 'isIncomplete' => $result->status === 'Incomplete',
                'subjectFails' => count($result->failedCompulsorySubjects),
                'subjectMissing' => count($result->missingCompulsorySubjects),
                'optionalBonus' => $result->optionalBonus,
                'failedCompulsorySubjects' => $result->failedCompulsorySubjects,
                'missingCompulsorySubjects' => $result->missingCompulsorySubjects,
                'missingSubjectNames' => $presented['missingSubjects'],
                'componentFailures' => $presented['componentFailures'],
                '_studentResult' => $result,
            ];
        }
        $columnDefinitions = $this->mergeAssignedFourthSubjectColumns($entries, $columnDefinitions);
        $columns = array_map(fn ($definition) => (object) $definition, array_values($columnDefinitions));
        usort($columns, fn ($a, $b) => $a->sortOrder <=> $b->sortOrder
            ?: strcasecmp($a->subjectName, $b->subjectName));
        foreach ($rows as &$row) {
            $row['cells'] = collect($row['subjects'])->keyBy('cellKey')->all();
            $row['subjectsCompact'] = array_values(array_filter($row['subjects'], fn ($subject) => is_numeric($subject['total'])));
        }
        unset($row);
        $sections = ['Complete' => [], 'Incomplete' => [], 'Absent' => []];
        foreach ($rows as $row) $sections[$row['classification']][] = $row;
        usort($sections['Complete'], fn ($a, $b) =>
            ($a['meritPosition'] ?? PHP_INT_MAX) <=> ($b['meritPosition'] ?? PHP_INT_MAX)
            ?: ((int) $a['studentIdentity']['roll'] <=> (int) $b['studentIdentity']['roll']));
        $failureBuckets = [];
        foreach ($sections['Complete'] as $row) {
            if (!$row['isFail']) continue;
            $count = (int) $row['subjectFails'];
            if ($count > 0) $failureBuckets[$count] = ($failureBuckets[$count] ?? 0) + 1;
        }
        ksort($failureBuckets);
        $tabulationPages = $this->pageRowsByStatus($sections, 18);
        $reportSections = ['Pass' => [], 'Fail' => [], 'Incomplete' => [], 'Absent' => []];
        foreach ($rows as $row) $reportSections[$row['reportStatus']][] = $row;
        $orderedGlanceRows = array_merge(...array_values($reportSections));
        $failedGroups = [];
        foreach ($reportSections['Fail'] as $row) {
            $failedGroups[(int) $row['subjectFails']][] = $row;
        }
        ksort($failedGroups);
        $subjectWisePages = $this->pageSubjectWiseRows($reportSections, $failedGroups, 10);
        $glancePageSize = collect($columns)->sum('componentColumnCount') >= 14 ? 18 : 24;
        $glancePages = $this->pageRows($orderedGlanceRows, $glancePageSize);
        return [
            'rows' => $rows,
            'glanceRows' => $orderedGlanceRows,
            'subjects' => collect($columns),
            'sections' => $sections,
            'reportSections' => $reportSections,
            'failedGroups' => $failedGroups,
            'failureBuckets' => $failureBuckets,
            'tabulationPages' => $tabulationPages,
            'subjectWisePages' => $subjectWisePages,
            'glancePages' => $glancePages,
        ];
    }

    private function mergeAssignedFourthSubjectColumns(array $entries, array $columnDefinitions): array
    {
        $assignedFourthIds = collect($entries)
            ->map(fn ($entry) => is_numeric($entry['student']->fourthSubjectId ?? null) ? (int) $entry['student']->fourthSubjectId : null)
            ->filter(fn ($id) => $id !== null && $id > 0)
            ->unique()
            ->values();

        if ($assignedFourthIds->isEmpty()) {
            return $columnDefinitions;
        }

        $fourthSubjects = collect($entries)
            ->flatMap(fn ($entry) => $entry['subjects'])
            ->filter(fn ($subject) => in_array((int) ($subject->id ?? 0), $assignedFourthIds->all(), true))
            ->keyBy(fn ($subject) => (int) $subject->id);

        $missingIds = $assignedFourthIds->reject(fn ($id) => $fourthSubjects->has((int) $id));
        if ($missingIds->isNotEmpty()) {
            $loaded = Subject::query()->whereIn('id', $missingIds->all())->get();
            foreach ($loaded as $subject) {
                $fourthSubjects->put((int) $subject->id, $subject);
            }
        }

        foreach ($assignedFourthIds as $subjectId) {
            $subject = $fourthSubjects->get((int) $subjectId);
            if (!$subject) {
                continue;
            }

            $cellKey = (string) $subjectId;
            $componentColumns = [];
            if ((float) ($subject->CQ ?? 0) > 0) $componentColumns[] = ['key' => 'cq', 'label' => 'CQ'];
            if ((float) ($subject->MCQ ?? 0) > 0) $componentColumns[] = ['key' => 'mcq', 'label' => 'MCQ'];
            if ((float) ($subject->Practical ?? 0) > 0) $componentColumns[] = ['key' => 'practical', 'label' => 'PR'];
            if ($componentColumns === []) $componentColumns[] = ['key' => 'total', 'label' => 'Total'];

            $columnDefinitions[$cellKey] ??= [
                'cellKey' => $cellKey,
                'subject_id' => $subjectId,
                'display_name' => SubjectHeaderFormatter::shortLabel((string) $subject->subjectName),
                'full_name' => SubjectHeaderFormatter::normalizeName((string) $subject->subjectName),
                'is_fourth_subject' => true,
                'subjectName' => (string) $subject->subjectName,
                'paired' => false,
                'optional' => true,
                'componentColumns' => $componentColumns,
                'componentColumnCount' => count($componentColumns),
                'sortOrder' => 900000 + (int) $subjectId,
            ];
        }

        return $columnDefinitions;
    }

    public function summarize(array $rows, $subjects): array
    {
        $total = count($rows);
        $counts = ['Pass' => 0, 'Fail' => 0, 'Incomplete' => 0, 'Absent' => 0];
        $gpaDistribution = ['5.00' => 0, '4.00-4.99' => 0, '3.50-3.99' => 0, '3.00-3.49' => 0, '2.00-2.99' => 0, '1.00-1.99' => 0, 'Fail' => 0, 'Incomplete' => 0, 'Absent' => 0];
        $gradeDistribution = [];
        $failureBuckets = [];
        foreach ($rows as $row) {
            $classification = $row['classification'] ?? (($row['status'] ?? null) === 'Incomplete' ? 'Incomplete' : 'Complete');
            $status = $classification === 'Complete' ? $row['status'] : $classification;
            $counts[$status]++;
            if ($status !== 'Pass') $gpaDistribution[$status]++;
            else $gpaDistribution[$this->gpaBucket((float) $row['finalGpa'])]++;
            $gradeDistribution[$row['finalLetter']] = ($gradeDistribution[$row['finalLetter']] ?? 0) + 1;
            $failed = (int) $row['subjectFails'];
            if ($status === 'Fail' && $failed > 0) $failureBuckets[$failed] = ($failureBuckets[$failed] ?? 0) + 1;
        }
        ksort($failureBuckets);

        $subjectStats = [];
        foreach ($subjects as $subject) {
            $name = (string) $subject->subjectName; $appeared = $pass = $fail = $missing = 0;
            foreach ($rows as $row) {
                $cell = $row['cells'][$subject->cellKey] ?? null;
                if (!$cell || ($cell['status'] ?? null) === 'Incomplete') { $missing++; continue; }
                $appeared++;
                if (($cell['status'] ?? null) === 'Fail') $fail++; else $pass++;
            }
            $subjectStats[] = ['subjectName' => $name, 'appeared' => $appeared, 'pass' => $pass, 'fail' => $fail, 'missing' => $missing,
                'passRate' => $appeared ? round($pass / $appeared * 100, 2) : 0.0,
                'failRate' => $appeared ? round($fail / $appeared * 100, 2) : 0.0];
        }
        return [
            'overallSummary' => ['total' => $total, 'present' => $counts['Pass'] + $counts['Fail'] + $counts['Incomplete'], 'absent' => $counts['Absent'],
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
        foreach (['Complete', 'Incomplete', 'Absent'] as $status) {
            $chunks = array_chunk($sections[$status], $size);
            if ($chunks === []) $chunks = [[]];
            foreach ($chunks as $rows) {
                $pages[] = ['status' => $status, 'rows' => $rows];
            }
        }
        return $this->numberPages($pages);
    }

    private function pageRows(array $rows, int $size): array
    {
        $pages = [];
        foreach (array_chunk($rows, $size) as $index => $chunk) {
            $pages[] = [
                'rows' => $chunk,
                'slStart' => ($index * $size) + 1,
            ];
        }

        return $this->numberPages($pages);
    }

    private function pageSubjectWiseRows(array $reportSections, array $failedGroups, int $size): array
    {
        $groups = [['title' => 'All Subject Pass', 'rows' => $reportSections['Pass']]];
        foreach ($failedGroups as $failedCount => $rows) {
            $groups[] = [
                'title' => 'Failed in '.$failedCount.' Subject'.($failedCount === 1 ? '' : 's'),
                'rows' => $rows,
            ];
        }
        $groups[] = ['title' => 'Incomplete', 'rows' => $reportSections['Incomplete']];
        $groups[] = ['title' => 'Absent', 'rows' => $reportSections['Absent']];

        $pages = [];
        foreach ($groups as $group) {
            if ($group['rows'] === []) {
                continue;
            }
            $chunks = array_chunk($group['rows'], $size);
            foreach ($chunks as $chunkIndex => $rows) {
                $pages[] = [
                    'title' => $group['title'],
                    'rows' => $rows,
                    'slStart' => ($chunkIndex * $size) + 1,
                ];
            }
        }

        return $this->numberPages($pages);
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

    private function gpaBucket(float $gpa): string
    {
        if ($gpa >= 5) return '5.00'; if ($gpa >= 4) return '4.00-4.99'; if ($gpa >= 3.5) return '3.50-3.99';
        if ($gpa >= 3) return '3.00-3.49'; if ($gpa >= 2) return '2.00-2.99'; return '1.00-1.99';
    }
}

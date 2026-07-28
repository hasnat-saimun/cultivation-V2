<?php

namespace App\Services\ResultCalculation;

final class ResultMeritPositionService
{
    public function __construct(
        private StudentResultClassificationService $classifier,
        private RankingMethodResolver $rankingMethodResolver,
    ) {}

    /**
     * Uses the established placement ranking policy: configured GPA/total ordering,
     * deterministic roll/id ordering, and competition ranking for exact ties.
     *
     * @return array<int,int>
     */
    public function positions(array $entries): array
    {
        $method = $this->rankingMethodResolver->resolve()['method'];
        $eligible = [];

        foreach ($entries as $studentId => $entry) {
            /** @var StudentResult $result */
            $result = $entry['result'];
            $classification = $this->classifier->classify($result, $entry['student']->marksheet);
            if ($classification['classification'] !== 'Complete') {
                continue;
            }

            $total = round((float) collect($result->subjectResults)
                ->filter(fn (SubjectResult $subject) => $subject->isCompulsory)
                ->sum(fn (SubjectResult $subject) => (float) ($subject->obtainedMarks ?? 0)), 2);
            $gpa = (float) ($result->gpa ?? 0.0);
            $eligible[] = [
                'studentId' => (int) $studentId,
                'tuple' => $method === \App\Models\ServerConfig::RANKING_METHOD_TOTAL_MARKS
                    ? [$total, $gpa]
                    : [$gpa, $total],
                'roll' => is_numeric($entry['student']->rollNumber ?? null)
                    ? (int) $entry['student']->rollNumber
                    : PHP_INT_MAX,
            ];
        }

        usort($eligible, function (array $left, array $right) {
            foreach ([0, 1] as $key) {
                if ($left['tuple'][$key] !== $right['tuple'][$key]) {
                    return $right['tuple'][$key] <=> $left['tuple'][$key];
                }
            }
            return $left['roll'] <=> $right['roll'] ?: $left['studentId'] <=> $right['studentId'];
        });

        $positions = [];
        $previous = null;
        $rank = 0;
        foreach ($eligible as $offset => $row) {
            if ($previous === null || $row['tuple'] !== $previous) {
                $rank = $offset + 1;
            }
            $positions[$row['studentId']] = $rank;
            $previous = $row['tuple'];
        }

        return $positions;
    }
}

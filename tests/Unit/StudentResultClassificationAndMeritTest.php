<?php

namespace Tests\Unit;

use App\Models\ServerConfig;
use App\Services\ResultCalculation\RankingMethodResolver;
use App\Services\ResultCalculation\ResultMeritPositionService;
use App\Services\ResultCalculation\StudentResult;
use App\Services\ResultCalculation\StudentResultClassificationService;
use App\Services\ResultCalculation\SubjectResult;
use PHPUnit\Framework\TestCase;

class StudentResultClassificationAndMeritTest extends TestCase
{
    public function test_complete_incomplete_absent_zero_and_optional_only_classification(): void
    {
        $classifier = new StudentResultClassificationService;
        $complete = $this->studentResult(false);
        $incomplete = $this->studentResult(true);

        $this->assertSame('Complete', $classifier->classify($complete, [$this->mark(1, 0)])['classification']);
        $this->assertSame('Incomplete', $classifier->classify($incomplete, [$this->mark(1, 40)])['classification']);
        $this->assertSame('Absent', $classifier->classify($incomplete, [])['classification']);
        $this->assertSame('Absent', $classifier->classify($incomplete, [$this->mark(9, 80)])['classification']);
        $this->assertSame(['9'], $classifier->classify($complete, [$this->mark(1, 80)])['ignoredOptionalMissingSubjectIds']);
    }

    public function test_merit_uses_gpa_total_competition_ties_and_excludes_non_complete(): void
    {
        $classifier = new StudentResultClassificationService;
        $resolver = new class extends RankingMethodResolver {
            public function resolve(): array
            {
                return ['method' => ServerConfig::RANKING_METHOD_GRADING, 'warnings' => []];
            }
        };
        $service = new ResultMeritPositionService($classifier, $resolver);

        $entries = [
            1 => $this->entry(1, 1, 5.0, 80),
            2 => $this->entry(2, 2, 5.0, 70),
            3 => $this->entry(3, 3, 5.0, 70),
            4 => $this->entry(4, 4, null, null, true),
        ];

        $this->assertSame([1 => 1, 2 => 2, 3 => 2], $service->positions($entries));
    }

    private function entry(int $id, int $roll, ?float $gpa, ?float $marks, bool $missing = false): array
    {
        $student = (object) ['id' => $id, 'rollNumber' => $roll];
        $student->marksheet = collect($marks === null ? [] : [$this->mark(1, $marks)]);

        return [
            'student' => $student,
            'subjects' => collect(),
            'result' => $this->studentResult($missing, $gpa, $marks),
        ];
    }

    private function studentResult(bool $missing, ?float $gpa = 5.0, ?float $marks = 80): StudentResult
    {
        $main = new SubjectResult('1', 'Compulsory', $marks, 100, $marks, $missing ? '-' : 'A+', $missing ? 0 : 5, $missing ? 'Incomplete' : 'Pass', false, true, [], $missing, ['1']);
        $optional = new SubjectResult('9', 'Optional', null, 100, null, '-', 0, 'Incomplete', true, false, [], true, ['9']);

        return new StudentResult([$main, $optional], $missing ? 0 : 5, 1, null, 0, [], $missing ? ['1'] : [], $gpa, $missing ? 'Incomplete' : 'Pass');
    }

    private function mark(int $subjectId, float $value): object
    {
        return (object) ['subjectId' => $subjectId, 'subjectMarks' => $value, 'objectMarks' => null, 'practicalMarks' => null];
    }
}

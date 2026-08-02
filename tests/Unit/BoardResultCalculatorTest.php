<?php

namespace Tests\Unit;

use App\Services\ResultCalculation\BoardResultCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BoardResultCalculatorTest extends TestCase
{
    private BoardResultCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new BoardResultCalculator();
    }

    public static function gradeBoundaries(): array
    {
        return [[32, 'F', 0.0], [33, 'D', 1.0], [39, 'D', 1.0], [40, 'C', 2.0], [49, 'C', 2.0], [50, 'B', 3.0], [59, 'B', 3.0], [60, 'A-', 3.5], [69, 'A-', 3.5], [70, 'A', 4.0], [79, 'A', 4.0], [80, 'A+', 5.0]];
    }

    #[DataProvider('gradeBoundaries')]
    public function test_board_grade_boundaries(float $score, string $letter, float $point): void
    {
        $this->assertSame([$letter, $point], $this->calculator->gradeForPercentage($score));
    }

    public function test_normal_pass_and_non_100_normalization(): void
    {
        $result = $this->calculate([$this->subject(1, 'Theory', 50)], [$this->mark(1, 40)]);
        $this->assertSame('Pass', $result->status);
        $this->assertSame(5.0, $result->gpa);
        $this->assertSame('A+', $result->subjectResults[0]->letterGrade);
        $this->assertSame(80.0, $result->subjectResults[0]->percentage);
    }

    public function test_compulsory_f_and_zero_are_failed(): void
    {
        $result = $this->calculate([$this->subject(1, 'Main', 100)], [$this->mark(1, 0)]);
        $this->assertSame('Fail', $result->status);
        $this->assertSame(0.0, $result->gpa);
        $this->assertSame(['1'], $result->failedCompulsorySubjects);
    }

    public static function optionalCases(): array
    {
        return [[40, 2.0, 0.0], [50, 3.0, 1.0], [80, 5.0, 3.0], [0, 0.0, 0.0]];
    }

    #[DataProvider('optionalCases')]
    public function test_assigned_optional_bonus_and_optional_f_do_not_fail(float $mark, float $gp, float $bonus): void
    {
        $result = $this->calculate(
            [$this->subject(1, 'Main', 100), $this->subject(2, 'Optional', 100)],
            [$this->mark(1, 70), $this->mark(2, $mark)],
            fourthId: 2
        );
        $this->assertSame('Pass', $result->status);
        $this->assertSame($gp, $result->optionalSubjectGp);
        $this->assertSame($bonus, $result->optionalBonus);
        $this->assertSame(1, $result->compulsorySubjectCount);
    }

    public function test_optional_is_excluded_from_denominator_and_gpa_is_capped(): void
    {
        $result = $this->calculate(
            [$this->subject(1, 'Main', 100), $this->subject(2, 'Theory', 100), $this->subject(3, 'Optional', 100)],
            [$this->mark(1, 80), $this->mark(2, 80), $this->mark(3, 80)],
            fourthId: 3
        );
        $this->assertSame(2, $result->compulsorySubjectCount);
        $this->assertSame(10.0, $result->compulsoryGpSum);
        $this->assertSame(3.0, $result->optionalBonus);
        $this->assertSame(5.0, $result->gpa);
    }

    public function test_missing_compulsory_is_incomplete_but_zero_is_not_missing(): void
    {
        $missing = $this->calculate([$this->subject(1, 'Main', 100)], []);
        $this->assertSame('Incomplete', $missing->status);
        $this->assertNull($missing->gpa);
        $this->assertSame(['1'], $missing->missingCompulsorySubjects);
        $zero = $this->calculate([$this->subject(1, 'Main', 100)], [$this->mark(1, 0)]);
        $this->assertSame('Fail', $zero->status);
        $this->assertSame([], $zero->missingCompulsorySubjects);
    }

    public function test_only_assigned_fourth_subject_is_used_and_multiple_rows_warn(): void
    {
        $result = $this->calculate(
            [$this->subject(1, 'Main', 100), $this->subject(2, 'Optional', 100), $this->subject(3, 'Optional', 100)],
            [$this->mark(1, 70), $this->mark(2, 80), $this->mark(3, 50)],
            fourthId: 3
        );
        $this->assertSame(3.0, $result->optionalSubjectGp);
        $this->assertSame(1.0, $result->optionalBonus);
        $this->assertCount(2, $result->subjectResults);
        $this->assertTrue(collect($result->warnings)->contains(fn ($w) => str_contains($w, 'Multiple optional')));
    }

    public function test_invalid_fourth_assignment_warns_without_arbitrary_bonus(): void
    {
        $result = $this->calculate([$this->subject(1, 'Main', 100)], [$this->mark(1, 80)], fourthId: 99);
        $this->assertNull($result->optionalSubjectGp);
        $this->assertSame(0.0, $result->optionalBonus);
        $this->assertTrue(collect($result->warnings)->contains(fn ($w) => str_contains($w, 'missing or not Optional')));
    }

    public function test_configured_pair_is_merged_and_unrelated_names_are_not(): void
    {
        $subjects = [
            $this->subject(1, 'Main', 100, alias: 'bangla_1st_paper', name: 'Bangla 1st Paper'),
            $this->subject(2, 'Main', 100, alias: 'bangla_2nd_paper', name: 'Bangla 2nd Paper'),
            $this->subject(3, 'Main', 100, alias: 'science_1st', name: 'Science 1st'),
            $this->subject(4, 'Main', 100, alias: 'science_2nd', name: 'Science 2nd'),
        ];
        $marks = [$this->mark(1, 70), $this->mark(2, 90), $this->mark(3, 80), $this->mark(4, 80)];
        $result = $this->calculate($subjects, $marks);
        $this->assertSame(3, $result->compulsorySubjectCount);
        $this->assertSame(['1', '2'], $result->subjectResults[2]->sourceSubjectIds);
        $this->assertSame('A+', $result->subjectResults[2]->letterGrade);
    }

    public static function componentFailures(): array
    {
        return [['cq', 10, 20, 20], ['mcq', 40, 5, 20], ['practical', 40, 20, 5]];
    }

    public static function componentPassThresholds(): array
    {
        return [
            [25, 7, 8],
            [50, 16, 17],
            [75, 24, 25],
            [100, 32, 33],
        ];
    }

    #[DataProvider('componentPassThresholds')]
    public function test_configured_component_threshold_uses_nearest_default_pass_mark(
        int $fullMarks,
        int $failingMark,
        int $passingMark,
    ): void {
        foreach (['cq', 'mcq', 'practical'] as $component) {
            $full = ['cq' => 100, 'mcq' => 100, 'practical' => 100];
            $marks = ['cq' => 100, 'mcq' => 100, 'practical' => 100];
            $full[$component] = $fullMarks;
            $marks[$component] = $failingMark;

            $failed = $this->calculate(
                [$this->subject(1, 'Main', $full['cq'], $full['mcq'], $full['practical'])],
                [$this->mark(1, $marks['cq'], $marks['mcq'], $marks['practical'])],
                passingSystem: 1,
            );
            $this->assertSame('Fail', $failed->status, "{$component} {$failingMark}/{$fullMarks} must fail");

            $marks[$component] = $passingMark;
            $passed = $this->calculate(
                [$this->subject(1, 'Main', $full['cq'], $full['mcq'], $full['practical'])],
                [$this->mark(1, $marks['cq'], $marks['mcq'], $marks['practical'])],
                passingSystem: 1,
            );
            $this->assertSame('Pass', $passed->status, "{$component} {$passingMark}/{$fullMarks} must pass");
        }
    }

    #[DataProvider('componentFailures')]
    public function test_feature_wise_required_component_failure_fails_subject(string $component, float $cq, float $mcq, float $practical): void
    {
        $subject = $this->subject(1, 'Main', 50, 25, 25);
        $result = $this->calculate([$subject], [$this->mark(1, $cq, $mcq, $practical)], passingSystem: 1);
        $this->assertSame('Fail', $result->status);
        $this->assertContains($component, $result->subjectResults[0]->componentFailures);
    }

    public function test_total_mark_mode_does_not_fail_a_low_component_when_total_passes(): void
    {
        $subject = $this->subject(1, 'Main', 50, 25, 25);
        $result = $this->calculate([$subject], [$this->mark(1, 10, 25, 25)], passingSystem: 2);
        $this->assertSame('Pass', $result->status);
        $this->assertSame('A-', $result->subjectResults[0]->letterGrade);
        $this->assertContains('paper:1:cq', $result->subjectResults[0]->componentFailures);
    }

    public function test_subject_level_api_reuses_normalization_grade_and_component_rules(): void
    {
        $student = ['id'=>1, 'fourthSubjectId'=>null];
        $subject = $this->subject(1, 'Main', 50, 25, 25);
        $mark = $this->mark(1, 40, 20, 20);

        $result = $this->calculator->calculateSubject(
            $student, ['id'=>1, 'passingSystem'=>1], $mark, $subject
        );

        $this->assertSame(80.0, $result->obtainedMarks);
        $this->assertSame('A+', $result->letterGrade);
        $this->assertSame(5.0, $result->gradePoint);
        $this->assertSame('Pass', $result->status);
    }

    public function test_subject_level_api_returns_incomplete_when_required_component_is_missing(): void
    {
        $result = $this->calculator->calculateSubject(
            ['id'=>1], ['id'=>1, 'passingSystem'=>1],
            $this->mark(1, 40, null, 20),
            $this->subject(1, 'Main', 50, 25, 25)
        );

        $this->assertTrue($result->missing);
        $this->assertSame('Incomplete', $result->status);
        $this->assertSame('-', $result->letterGrade);
        $this->assertNull($result->obtainedMarks);
    }

    public function test_scope_profile_ignores_component_when_blank_for_everyone(): void
    {
        $result = $this->calculate(
            [$this->subject(1, 'Main', 50, 50)],
            [$this->mark(1, 40, null)],
            null,
            2,
            ['1' => ['cq' => true, 'mcq' => false, 'practical' => false]],
        );

        $this->assertSame('Pass', $result->status);
        $this->assertSame([], $result->missingCompulsorySubjects);
    }

    public function test_scope_profile_marks_student_incomplete_when_required_component_is_missing(): void
    {
        $result = $this->calculate(
            [$this->subject(1, 'Main', 50, 50)],
            [$this->mark(1, 40, null)],
            null,
            2,
            ['1' => ['cq' => true, 'mcq' => true, 'practical' => false]],
        );

        $this->assertSame('Incomplete', $result->status);
        $this->assertSame(['1'], $result->missingCompulsorySubjects);
    }

    public function test_scope_profile_does_not_suppress_assigned_optional_subject_without_marks(): void
    {
        $result = $this->calculate(
            [$this->subject(1, 'Main', 100), $this->subject(2, 'Optional', 100)],
            [$this->mark(1, 70)],
            2,
            2,
            [
                '1' => ['cq' => true, 'mcq' => false, 'practical' => false],
                '2' => ['cq' => false, 'mcq' => false, 'practical' => false],
            ],
        );

        $optional = collect($result->subjectResults)->firstWhere('subjectId', '2');
        $this->assertNotNull($optional);
        $this->assertTrue($optional->isOptional);
        $this->assertTrue($optional->missing);
        $this->assertSame('Incomplete', $optional->status);
        $this->assertSame('Pass', $result->status);
        $this->assertSame([], $result->missingCompulsorySubjects);
        $this->assertSame(0.0, $result->optionalBonus);
    }

    private function calculate(array $subjects, array $marks, ?int $fourthId = null, int $passingSystem = 2, array $componentRequirementProfile = [])
    {
        return $this->calculator->calculate(
            ['id' => 1, 'fourthSubjectId' => $fourthId],
            ['id' => 1, 'passingSystem' => $passingSystem],
            $marks,
            $subjects,
            $componentRequirementProfile,
        );
    }

    private function subject(int $id, string $type, float $cq, float $mcq = 0, float $practical = 0, ?string $alias = null, ?string $name = null): array
    {
        return ['id' => $id, 'subjectName' => $name ?? "Subject {$id}", 'alias' => $alias ?? "subject_{$id}", 'subjectType' => $type, 'CQ' => $cq, 'MCQ' => $mcq, 'Practical' => $practical];
    }

    private function mark(int $subjectId, ?float $cq, ?float $mcq = null, ?float $practical = null): array
    {
        return ['id' => $subjectId, 'subjectId' => $subjectId, 'subjectMarks' => $cq, 'objectMarks' => $mcq, 'practicalMarks' => $practical];
    }
}

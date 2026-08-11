<?php

namespace Tests\Feature;

use App\Models\GradeList;
use App\Services\ResultCalculation\GradeScaleOrderingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranscriptPresentationFinalCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_subject_rows_highlight_only_authoritative_fail_status(): void
    {
        $rows = [
            $this->row('normal-fail', 'Normal Failed', 'Fail', 'F', '0.00'),
            $this->row('pass', 'Passed Subject', 'Pass', 'A+', '5.00'),
            $this->row('pair-fail', 'Bangla', 'Fail', 'F', '0.00', '(10.5 + 12.25) = 22.75'),
            $this->row('pair-pass', 'English', 'Pass', 'A', '4.00', '(35.5 + 37.5) = 73'),
            $this->row('fourth-fail', 'Higher Math', 'Fail', 'F', '0.00', 23.25),
            $this->row('incomplete', 'Missing Subject', 'Incomplete', '-', '-', '-'),
        ];

        $html = view('result.partials.transcript-subject-rows', [
            'rows' => $rows,
            'emptyMessage' => 'No subjects',
        ])->render();

        foreach (['normal-fail', 'pair-fail', 'fourth-fail'] as $id) {
            preg_match('/data-subject-id="'.$id.'".*?<\/tr>/s', $html, $match);
            $this->assertSame(2, substr_count($match[0] ?? '', 'failed-grade-cell'));
        }
        foreach (['pass', 'pair-pass', 'incomplete'] as $id) {
            preg_match('/data-subject-id="'.$id.'".*?<\/tr>/s', $html, $match);
            $this->assertStringNotContainsString('failed-grade-cell', $match[0] ?? '');
        }
        $this->assertStringContainsString('23.25', $html);
    }

    public function test_failure_style_remains_identifiable_without_color(): void
    {
        $css = view('result.partials.transcript-failure-styles')->render();

        $this->assertStringContainsString('.failed-grade-cell', $css);
        $this->assertStringContainsString('border: 3px double #000', $css);
        $this->assertStringContainsString('font-weight: 900', $css);
        $this->assertStringContainsString('text-decoration-style: double', $css);
        $this->assertStringContainsString('-webkit-print-color-adjust: exact', $css);
        $this->assertStringContainsString('print-color-adjust: exact', $css);
    }

    public function test_unordered_configured_grades_are_numeric_descending_everywhere(): void
    {
        foreach ([3.50, 5.00, 1.00, 4.00, 0.00, 2.00] as $index => $point) {
            $grade = new GradeList();
            $grade->minMark = (string) ($index * 10);
            $grade->maxMark = (string) ($index * 10 + 9);
            $grade->gradeName = 'G'.str_replace('.', '_', (string) $point);
            $grade->gradePoint = number_format($point, 2, '.', '');
            $grade->save();
        }

        $ordering = app(GradeScaleOrderingService::class);
        $this->assertSame(
            ['5.00', '4.00', '3.50', '2.00', '1.00', '0.00'],
            $ordering->all()->map(fn ($grade) => number_format((float) $grade->gradePoint, 2))->all()
        );

        $legend = $ordering->legend();
        $html = view('result.partials.grading-table', ['gradeLegend' => $legend])->render();
        $positions = array_map(fn ($point) => strpos($html, '>'.$point.'<'), ['5.00', '4.00', '3.50', '2.00', '1.00', '0.00']);
        $this->assertSame($positions, collect($positions)->sort()->values()->all());

        $unordered = [];
        foreach (array_reverse($legend) as $row) $unordered[$row['grade']] = 1;
        $this->assertSame(array_column($legend, 'grade'), array_keys($ordering->sortDistribution($unordered)));
    }

    private function row(string $id, string $name, string $status, string $grade, string $point, string|float $total = 20): array
    {
        return [
            'id' => $id, 'name' => $name, 'status' => $status,
            'cq' => 10, 'mcq' => 5, 'practical' => 5,
            'total' => $total, 'grade' => $grade, 'gradePoint' => $point,
        ];
    }
}

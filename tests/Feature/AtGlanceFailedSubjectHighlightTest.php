<?php

namespace Tests\Feature;

use Tests\TestCase;

class AtGlanceFailedSubjectHighlightTest extends TestCase
{
    public function test_only_authoritative_failed_compulsory_subject_cells_are_highlighted(): void
    {
        $subjects = collect([
            $this->subject('pair:bangla', true, false, [['key' => 'cq', 'label' => 'CQ'], ['key' => 'mcq', 'label' => 'MCQ']]),
            $this->subject('science', false, false, [['key' => 'total', 'label' => 'Total']]),
            $this->subject('optional', false, true, [['key' => 'total', 'label' => 'Total']]),
            $this->subject('missing', false, false, [['key' => 'total', 'label' => 'Total']]),
            $this->subject('absent', false, false, [['key' => 'total', 'label' => 'Total']]),
            $this->subject('unassigned', false, false, [['key' => 'total', 'label' => 'Total']]),
        ]);

        $row = [
            'student' => (object) ['stdId' => 'S-1'],
            'studentIdentity' => ['roll' => '1', 'id' => 1, 'name' => 'Student One'],
            'cells' => [
                'pair:bangla' => ['cq' => '22.50', 'mcq' => '7.50', 'status' => 'Fail'],
                'science' => ['total' => '55', 'status' => 'Pass'],
                'optional' => ['total' => '20', 'status' => 'Fail'],
                'missing' => ['total' => '-', 'status' => 'Incomplete'],
                'absent' => ['total' => 'Absent', 'status' => 'Absent'],
            ],
            'meritPosition' => null,
            'totalMarks' => '77.50',
            'finalGpa' => null,
            'classification' => 'Incomplete',
            'finalLetter' => 'Incomplete',
            'subjectFails' => 1,
            'reportStatus' => 'Incomplete',
        ];

        $html = view('result.partials.glance-table', [
            'subjects' => $subjects,
            'tableRows' => [$row],
        ])->render();

        $this->assertSame(2, substr_count($html, 'failed-subject-cell'));
        $this->assertMatchesRegularExpression('/failed-subject-cell[^>]*>22\.50</', $html);
        $this->assertMatchesRegularExpression('/failed-subject-cell[^>]*>7\.50</', $html);
        foreach (['55', '20', 'Absent', '-'] as $value) {
            $this->assertDoesNotMatchRegularExpression('/failed-subject-cell[^>]*>'.preg_quote($value, '/').'</', $html);
        }
    }

    public function test_at_a_glance_screen_and_print_styles_share_the_semantic_failure_class(): void
    {
        $view = file_get_contents(resource_path('views/result/atGlanceResult.blade.php'));

        $this->assertStringContainsString('.glance-table td.failed-subject-cell', $view);
        $this->assertStringContainsString('print-color-adjust: exact', $view);
        $this->assertStringContainsString('-webkit-print-color-adjust: exact', $view);
        $this->assertStringContainsString('border: 2px solid #7f1d1d !important', $view);
    }

    private function subject(string $key, bool $paired, bool $optional, array $components): object
    {
        return (object) [
            'cellKey' => $key,
            'subjectName' => $key,
            'display_name' => $key,
            'paired' => $paired,
            'optional' => $optional,
            'is_fourth_subject' => $optional,
            'componentColumns' => $components,
            'componentColumnCount' => count($components),
        ];
    }
}

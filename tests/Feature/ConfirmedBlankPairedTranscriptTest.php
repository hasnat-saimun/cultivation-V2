<?php

namespace Tests\Feature;

use App\Models\MarksScopeState;
use App\Models\Subject;
use App\Services\ResultCalculation\BulkTranscriptResultBuilder;
use App\Services\ResultMarksConfirmationService;
use App\Services\ResultMarksDraftService;
use App\Services\ResultMarksReopenService;
use App\Services\ResultPublishService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class ConfirmedBlankPairedTranscriptTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInGeneralAdmin();
    }

    public function test_confirmed_blank_override_is_effective_zero_in_paired_transcript_and_html(): void
    {
        [$data, $actor, $paper1, $paper2] = $this->pairedScope();
        $this->savePaper($data, $actor, $paper1, 47, null);
        $this->savePaper($data, $actor, $paper2, 35, 20);
        $this->confirmPaper($data, $actor, $paper1, true);
        $this->confirmPaper($data, $actor, $paper2, false);

        $transcript = $this->transcript($data);
        $row = $transcript['result']['mainRows'][0];

        $this->assertSame('(47 + 35) = 82', $row['cq']);
        $this->assertSame('(0 + 20) = 20', $row['mcq']);
        $this->assertSame(102.0, $row['total']);
        $this->assertSame('A-', $row['grade']);
        $this->assertSame('3.50', $row['gradePoint']);
        $this->assertSame('Pass', $transcript['result']['status']);
        $this->assertNull($data['student']->marksheet->firstWhere('subjectId', $paper1->id)->objectMarks);

        $html = view('result.bulk-transcript-pdf', [
            'transcripts' => [$transcript],
            'bulkView' => [
                'title' => 'Academic Transcript', 'examName' => $data['exam']->examName,
                'institute' => ['name' => 'Test', 'address' => '', 'mobile' => '', 'email' => '', 'logoUrl' => null],
                'principalSignatureUrl' => null, 'gradeLegend' => [],
            ],
        ])->render();
        $this->assertStringContainsString('(0 + 20) = 20', $html);
        $this->assertStringContainsString('102', $html);
        $this->assertStringContainsString('3.50', $html);

        app(ResultPublishService::class)->publish([
            'sessionId' => $data['session']->id, 'classId' => $data['class']->id,
            'groupId' => $data['section']->id, 'examId' => $data['exam']->id,
        ], $actor);
        $data['student']->refresh();
        $published = $this->transcript($data);
        $this->assertSame(102.0, $published['result']['mainRows'][0]['total']);
        $this->assertSame('3.50', $published['result']['mainRows'][0]['gradePoint']);
    }

    public function test_real_single_transcript_route_uses_confirmed_blank_evidence(): void
    {
        [$data, $actor, $paper1, $paper2] = $this->pairedScope();
        DB::table('new_admissions')->where('id', $data['student']->id)->update([
            'id' => 376,
            'stdId' => '26000376',
        ]);
        $data['student'] = \App\Models\newAdmission::findOrFail(376);
        $data['students'] = collect([$data['student']]);
        $this->savePaper($data, $actor, $paper1, 47, null);
        $this->savePaper($data, $actor, $paper2, 35, 20);
        $this->confirmPaper($data, $actor, $paper1, true);
        $this->confirmPaper($data, $actor, $paper2, false);

        $response = $this->withSession(['cultivationAdmin' => $actor->id])
            ->get(route('marksheetGenerate', [
                'stdId' => '26000376',
                'studentId' => $data['student']->id,
                'examId' => $data['exam']->id,
            ]));

        $response->assertOk()
            ->assertSee('(47 + 35) = 82', false)
            ->assertSee('(0 + 20) = 20', false)
            ->assertSee('102', false)
            ->assertSee('A-', false)
            ->assertSee('3.50', false)
            ->assertDontSee('Incomplete: missing marks for Bangla', false);
    }

    public function test_draft_scope_does_not_enforce_blank_but_confirmed_scope_does(): void
    {
        [$data, $actor, $paper1, $paper2] = $this->pairedScope();
        $this->savePaper($data, $actor, $paper1, 47, null);
        $this->savePaper($data, $actor, $paper2, 35, 20);

        $this->assertSame('Pass', $this->transcript($data)['result']['status']);

        MarksScopeState::where('subjectId', (string) $paper1->id)
            ->update(['status' => MarksScopeState::STATUS_CONFIRMED]);
        $this->confirmPaper($data, $actor, $paper2, false);

        $this->assertSame('Incomplete', $this->transcript($data)['result']['status']);
    }

    public function test_paired_component_keeps_one_sided_numeric_value_instead_of_dash(): void
    {
        [$data, $actor, $paper1, $paper2] = $this->pairedScope();
        $paper2->update(['MCQ' => 0]);
        $this->savePaper($data, $actor, $paper1, 33, 17, null);
        $this->savePaper($data, $actor, $paper2, 26, null, null);
        $this->confirmPaper($data, $actor, $paper1, false);
        $this->confirmPaper($data, $actor, $paper2, false);

        $row = $this->transcript($data)['result']['mainRows'][0];
        $this->assertSame('(33 + 26) = 59', $row['cq']);
        $this->assertSame(17.0, $row['mcq']);
        $this->assertSame('-', $row['practical']);
        $this->assertSame(76.0, $row['total']);
    }

    public function test_real_single_transcript_route_shows_one_sided_numeric_for_paired_component(): void
    {
        [$data, $actor, $paper1, $paper2] = $this->pairedScope();
        $paper2->update(['MCQ' => 0]);
        DB::table('new_admissions')->where('id', $data['student']->id)->update([
            'id' => 376,
            'stdId' => '26000376',
        ]);
        $data['student'] = \App\Models\newAdmission::findOrFail(376);
        $data['students'] = collect([$data['student']]);

        $this->savePaper($data, $actor, $paper1, 33, 17, null);
        $this->savePaper($data, $actor, $paper2, 26, null, null);
        $this->confirmPaper($data, $actor, $paper1, false);
        $this->confirmPaper($data, $actor, $paper2, false);

        $response = $this->withSession(['cultivationAdmin' => $actor->id])
            ->get(route('marksheetGenerate', [
                'stdId' => '26000376',
                'studentId' => $data['student']->id,
                'examId' => $data['exam']->id,
            ]));

        $response->assertOk()
            ->assertSee('(33 + 26) = 59', false)
            ->assertSee('<td>17</td>', false)
            ->assertSee('<td>-</td>', false)
            ->assertSee('<td>76</td>', false);
    }

    public function test_disabled_components_numeric_zero_both_blank_and_reopen_semantics(): void
    {
        [$data, $actor, $paper1, $paper2] = $this->pairedScope();
        $paper1->update(['Practical' => 25]);
        $paper2->update(['Practical' => 25]);
        $this->savePaper($data, $actor, $paper1, 47, null, null);
        $this->savePaper($data, $actor, $paper2, 35, null, 0);
        $this->confirmPaper($data, $actor, $paper1, true);
        $this->confirmPaper($data, $actor, $paper2, true);

        $row = $this->transcript($data)['result']['mainRows'][0];
        $this->assertSame('(0 + 0) = 0', $row['mcq']);
        $this->assertSame('(0 + 0) = 0', $row['practical']);

        app(ResultMarksReopenService::class)->reopen(
            $this->scope($data, $paper1) + ['scope_revision' => 2, 'reason' => 'Correction required'],
            $actor,
        );
        $this->assertSame('Pass', $this->transcript($data)['result']['status']);
    }

    private function pairedScope(): array
    {
        $data = $this->lifecycleScope();
        $data['subject']->update([
            'subjectName' => 'Bangla 1st Paper', 'alias' => 'bangla_1st_paper',
            'subjectType' => 'Main', 'CQ' => 50, 'MCQ' => 25, 'Practical' => 0,
        ]);
        $paper2 = Subject::create([
            'subjectName' => 'Bangla 2nd Paper', 'alias' => 'bangla_2nd_paper',
            'subjectType' => 'Main', 'CQ' => 50, 'MCQ' => 25, 'Practical' => 0,
        ]);
        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $data['session']->id,
            'class_id' => (string) $data['class']->id,
            'section_id' => (string) $data['section']->id,
            'department_id' => null,
            'subject_id' => $paper2->id,
            'mapping_type' => 'main',
            'sort_order' => 2,
            'is_active' => 1,
            'source' => 'test',
            'normalized_section_scope' => 'section:'.$data['section']->id,
            'normalized_department_scope' => 'all',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $data['student'] = $data['students']->first();
        return [$data, $this->lifecycleActor(), $data['subject'], $paper2];
    }

    private function savePaper(array $data, $actor, Subject $subject, mixed $cq, mixed $mcq, mixed $practical = ''): void
    {
        app(ResultMarksDraftService::class)->save($this->scope($data, $subject) + [
            'studentId' => [$data['student']->id], 'cqMarks' => [$cq],
            'mcqMarks' => [$mcq], 'practical' => [$practical],
            'gender' => 'all', 'scope_revision' => 1,
        ], $actor);
    }

    private function confirmPaper(array $data, $actor, Subject $subject, bool $override): void
    {
        app(ResultMarksConfirmationService::class)->confirm($this->scope($data, $subject) + [
            'scope_revision' => 2, 'confirm_blank_marks' => $override ? 1 : 0,
        ], $actor);
    }

    private function scope(array $data, Subject $subject): array
    {
        return [
            'sessionId' => $data['session']->id, 'classId' => $data['class']->id,
            'groupId' => $data['section']->id, 'examId' => $data['exam']->id,
            'subjectId' => $subject->id,
        ];
    }

    private function transcript(array $data): array
    {
        $data['student']->load(['marksheet' => fn ($query) => $query
            ->where('examId', $data['exam']->id)->orderBy('subjectId')]);
        return app(BulkTranscriptResultBuilder::class)->build(
            [$data['student']], $data['exam']
        )[0];
    }
}

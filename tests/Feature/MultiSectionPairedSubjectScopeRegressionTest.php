<?php

namespace Tests\Feature;

use App\Http\Controllers\MarksheetController;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\ResultPublish;
use App\Models\Subject;
use App\Models\classManage;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Services\ResultCalculation\BulkTranscriptResultBuilder;
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use App\Services\ResultCalculation\TabulationResultPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiSectionPairedSubjectScopeRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_paired_component_profiles_are_isolated_across_a_b_c_and_super_reports(): void
    {
        $session = new sessionManage(); $session->session = '2026'; $session->save();
        $class = new classManage(); $class->className = 'Six'; $class->save();
        $exam = new Exam(); $exam->examName = 'Annual'; $exam->passingSystem = 0; $exam->save();
        $paper1 = $this->subject('Bangla 1st Paper', 'bangla_1st_paper', 50, 25);
        $paper2 = $this->subject('Bangla 2nd Paper', 'bangla_2nd_paper', 50, 0);

        $students = collect();
        foreach (['A', 'B', 'C', 'Super'] as $index => $name) {
            $section = new sectionManage(); $section->section = $name; $section->save();
            foreach ([$paper1, $paper2] as $order => $subject) {
                DB::table('curriculum_subject_mappings')->insert([
                    'session_id' => (string) $session->id,
                    'class_id' => (string) $class->id,
                    'section_id' => (string) $section->id,
                    'department_id' => null,
                    'subject_id' => $subject->id,
                    'mapping_type' => 'main',
                    'sort_order' => $order + 1,
                    'is_active' => 1,
                    'source' => 'test-fixture',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $student = new newAdmission([
                'stdId' => (string) (7100 + $index),
                'fullName' => $name.' Student',
                'sessName' => (string) $session->id,
                'className' => (string) $class->id,
                'sectionName' => (string) $section->id,
                'rollNumber' => '1',
            ]);
            $student->save();
            $this->mark($student, $session, $class, $section, $exam, $paper1, 40, 20);
            // A has evidence in a component disabled for paper 2. It must not make
            // that component required for B/C/Super.
            $this->mark($student, $session, $class, $section, $exam, $paper2, 35, $name === 'A' ? 5 : null);
            $students->put($name, $student);
        }

        $batch = app(ResultCalculationBatchBuilder::class)->build(
            $exam->id, $class->id, $session->id
        );
        foreach ($students as $name => $student) {
            $entry = $batch['entries'][$student->id];
            $pair = collect($entry['result']->subjectResults)->firstWhere('subjectId', 'pair:bangla');
            $this->assertSame('Pass', $entry['result']->status, $name);
            $this->assertFalse($pair->missing, $name);
            $this->assertSame([$paper1->id, $paper2->id], $entry['subjects']->pluck('id')->all(), $name);
        }

        $bulkStudents = newAdmission::whereKey($students->pluck('id'))
            ->with(['marksheet' => fn ($query) => $query->where('examId', $exam->id)])
            ->get();
        $bulk = app(BulkTranscriptResultBuilder::class)->build($bulkStudents, $exam);
        $this->assertSame(['Pass', 'Pass', 'Pass', 'Pass'], collect($bulk)->pluck('result.status')->all());

        $presented = app(TabulationResultPresenter::class)->present($batch['entries']);
        $this->assertCount(4, $presented['sections']['Complete']);
        $this->assertCount(0, $presented['sections']['Incomplete']);
        $this->assertNotEmpty($presented['subjectWisePages']);

        $single = app(MarksheetController::class)->generateMarksheet(Request::create(
            '/marksheet/generate',
            'GET',
            ['studentId' => $students['B']->id, 'examId' => $exam->id],
        ));
        $singleHtml = $single->render();
        $this->assertStringContainsString('Bangla', $singleHtml);
        $this->assertStringNotContainsString('Incomplete: missing marks for Bangla', $singleHtml);

        $before = collect($batch['entries'])->map(fn ($entry) => $entry['result']->toArray())->all();
        foreach ($students as $student) {
            ResultPublish::create([
                'examId' => $exam->id,
                'classId' => $class->id,
                'sessionId' => $session->id,
                'groupId' => $student->sectionName,
            ]);
        }
        $published = app(ResultCalculationBatchBuilder::class)->build(
            $exam->id, $class->id, $session->id
        );
        $after = collect($published['entries'])->map(fn ($entry) => $entry['result']->toArray())->all();
        $this->assertSame($before, $after);
    }

    private function subject(string $name, string $alias, float $cq, float $mcq): Subject
    {
        return Subject::create([
            'subjectName' => $name,
            'alias' => $alias,
            'subjectType' => 'Main',
            'assign_class' => '0',
            'CQ' => $cq,
            'MCQ' => $mcq,
            'Practical' => 0,
        ]);
    }

    private function mark(
        newAdmission $student,
        sessionManage $session,
        classManage $class,
        sectionManage $section,
        Exam $exam,
        Subject $subject,
        float $cq,
        ?float $mcq,
    ): void {
        Marksheet::create([
            'studentId' => $student->id,
            'classId' => $class->id,
            'sessionId' => $session->id,
            'groupId' => $section->id,
            'examId' => $exam->id,
            'subjectId' => $subject->id,
            'subjectMarks' => $cq,
            'objectMarks' => $mcq,
            'practicalMarks' => null,
            'totalMarks' => $cq + ($mcq ?? 0),
        ]);
    }
}

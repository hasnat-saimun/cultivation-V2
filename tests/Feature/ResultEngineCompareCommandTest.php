<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultEngineCompareCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_disabled_by_default(): void
    {
        config(['result_engine.shadow_mode' => false]);
        $this->artisan('result-engine:compare', ['--exam' => 1])
            ->expectsOutputToContain('Shadow mode is disabled')
            ->assertFailed();
    }

    public function test_command_compares_read_only_and_reports_differences(): void
    {
        config(['result_engine.shadow_mode' => true]);
        $session = new sessionManage(); $session->session = '2026'; $session->save();
        $class = new classManage(); $class->className = 'Class 10'; $class->save();
        $section = new sectionManage(); $section->section = 'A'; $section->save();
        $exam = new Exam(); $exam->examName = 'Annual'; $exam->passingSystem = 2; $exam->save();
        $main = new Subject(); $main->subjectName = 'Main'; $main->alias = 'main'; $main->subjectType = 'Main'; $main->CQ = 100; $main->save();
        $optional = new Subject(); $optional->subjectName = 'Optional'; $optional->alias = 'optional'; $optional->subjectType = 'Optional'; $optional->CQ = 100; $optional->save();
        $student = new newAdmission(); $student->stdId = 900001; $student->fullName = 'Shadow'; $student->sessName = $session->id; $student->className = $class->id; $student->sectionName = $section->id; $student->fourthSubjectId = $optional->id; $student->save();
        foreach ([[$main, 80, 5, 'A+'], [$optional, 80, 5, 'A+']] as [$subject, $mark, $gp, $grade]) {
            Marksheet::create(['studentId' => $student->id, 'classId' => $class->id, 'sessionId' => $session->id, 'groupId' => $section->id, 'examId' => $exam->id, 'subjectId' => $subject->id, 'subjectMarks' => $mark, 'totalMarks' => $mark, 'gradePoint' => $gp, 'laterGrade' => $grade]);
        }
        $before = Marksheet::count();
        $this->artisan('result-engine:compare', ['--exam' => $exam->id, '--student' => $student->id, '--limit' => 1])
            ->expectsOutputToContain('Read-only connection: mysql / cultivation_test')
            ->assertSuccessful();
        $this->assertSame($before, Marksheet::count());
        $this->assertDatabaseCount('exam_placements', 0);
        $this->assertDatabaseCount('result_archives', 0);
    }
}

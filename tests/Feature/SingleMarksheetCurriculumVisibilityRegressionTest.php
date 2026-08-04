<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\Department;
use App\Models\Exam;
use App\Models\newAdmission;
use App\Models\sessionManage;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SingleMarksheetCurriculumVisibilityRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInGeneralAdmin();
    }

    public function test_mapped_main_subject_remains_visible_even_when_marks_are_missing(): void
    {
        $this->withoutMiddleware();

        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 10';
        $class->save();
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $department = new Department();
        $department->departmentName = 'Science';
        $department->save();

        $exam = new Exam();
        $exam->examName = 'Annual';
        $exam->passingSystem = 2;
        $exam->save();

        $bangla = Subject::create(['subjectName' => 'Bangla 1st Paper', 'subjectType' => 'Main', 'CQ' => 100]);
        $religion = Subject::create(['subjectName' => 'Islam and moral education-111', 'subjectType' => 'Main', 'isReligious' => true, 'CQ' => 100]);
        $higherMath = Subject::create(['subjectName' => 'Higher Math-126', 'subjectType' => 'Optional', 'CQ' => 100]);

        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $sectionId,
            'department_id' => (string) $department->id,
            'subject_id' => $bangla->id,
            'mapping_type' => 'main',
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = new newAdmission();
        $student->stdId = '101';
        $student->fullName = 'Student';
        $student->sureName = 'One';
        $student->sessName = $session->id;
        $student->className = $class->id;
        $student->sectionName = $sectionId;
        $student->departmentName = $department->id;
        $student->religiousSubjectId = $religion->id;
        $student->fourthSubjectId = $higherMath->id;
        $student->rollNumber = '1';
        $student->save();

        $response = $this->get(route('marksheetGenerate', [
            'studentId' => $student->id,
            'examId' => $exam->id,
        ]));

        $response->assertOk();
        $response->assertSee('Bangla 1st Paper');

        $transcript = $response->viewData('transcriptResult');
        $mainNames = collect($transcript['mainRows'])->pluck('name')->all();

        $this->assertContains('Bangla 1st Paper', $mainNames);
        $this->assertSame('Incomplete', $transcript['status']);
        $this->assertTrue((bool) ($transcript['curriculumStatus']['configured'] ?? false));
        $this->assertContains('Bangla 1st Paper', $transcript['missingSubjects']);
    }

    public function test_department_specific_subject_from_other_department_does_not_appear_on_marksheet(): void
    {
        $this->withoutMiddleware();

        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 10';
        $class->save();
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $science = new Department();
        $science->departmentName = 'Science';
        $science->save();
        $commerce = new Department();
        $commerce->departmentName = 'Commerce';
        $commerce->save();

        $exam = new Exam();
        $exam->examName = 'Annual';
        $exam->passingSystem = 2;
        $exam->save();

        $scienceSubject = Subject::create(['subjectName' => 'Physics', 'subjectType' => 'Main', 'CQ' => 100]);
        $commerceOnly = Subject::create(['subjectName' => 'Accounting', 'subjectType' => 'Main', 'CQ' => 100]);
        $religion = Subject::create(['subjectName' => 'Islam and moral education-111', 'subjectType' => 'Main', 'isReligious' => true, 'CQ' => 100]);
        $optional = Subject::create(['subjectName' => 'Higher Math-126', 'subjectType' => 'Optional', 'CQ' => 100]);

        DB::table('curriculum_subject_mappings')->insert([
            [
                'session_id' => (string) $session->id,
                'class_id' => (string) $class->id,
                'section_id' => (string) $sectionId,
                'department_id' => (string) $science->id,
                'subject_id' => $scienceSubject->id,
                'mapping_type' => 'main',
                'sort_order' => 1,
                'is_active' => 1,
                'source' => 'test-fixture',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'session_id' => (string) $session->id,
                'class_id' => (string) $class->id,
                'section_id' => (string) $sectionId,
                'department_id' => (string) $commerce->id,
                'subject_id' => $commerceOnly->id,
                'mapping_type' => 'main',
                'sort_order' => 1,
                'is_active' => 1,
                'source' => 'test-fixture',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $student = new newAdmission();
        $student->stdId = '102';
        $student->fullName = 'Student';
        $student->sureName = 'Two';
        $student->sessName = $session->id;
        $student->className = $class->id;
        $student->sectionName = $sectionId;
        $student->departmentName = $science->id;
        $student->religiousSubjectId = $religion->id;
        $student->fourthSubjectId = $optional->id;
        $student->rollNumber = '2';
        $student->save();

        $response = $this->get(route('marksheetGenerate', [
            'studentId' => $student->id,
            'examId' => $exam->id,
        ]));

        $response->assertOk();
        $transcript = $response->viewData('transcriptResult');
        $mainNames = collect($transcript['mainRows'])->pluck('name')->all();

        $this->assertContains('Physics', $mainNames);
        $this->assertNotContains('Accounting', $mainNames);
        $this->assertContains('Physics', $transcript['missingSubjects']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\Department;
use App\Models\Exam;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SingleMarksheetNoMetadataFailClosedRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_marksheet_fail_closed_when_no_curriculum_or_marks_metadata_exists(): void
    {
        $this->withoutMiddleware();

        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 10';
        $class->save();

        $section = new sectionManage();
        $section->section = 'Super';
        $section->save();

        $science = new Department();
        $science->departmentName = 'Science';
        $science->save();

        $exam = new Exam();
        $exam->examName = 'Annual 2026';
        $exam->passingSystem = 2;
        $exam->save();

        $religion = $this->subject('Islam and moral education-111', 'Main', '0', true);
        $higherMath = $this->subject('Higher Math-126', 'Optional', '0');

        foreach ([
            ['Bangla 1st Paper', 'Main'],
            ['Physics-136', 'Main'],
            ['Chemistry-137', 'Main'],
            ['Biology-138', 'Main'],
            ['Accounting-146', 'Main'],
            ['Finance and Banking-152', 'Main'],
            ['Business Entrepreneurship-143', 'Main'],
            ['History of Bangladesh and World Civilization-153', 'Main'],
            ['Civics and Citizenship-140', 'Main'],
            ['Geography and Environment-110', 'Main'],
        ] as [$name, $type]) {
            $this->subject($name, $type, '0');
        }

        $student = new newAdmission();
        $student->stdId = '26000001';
        $student->fullName = 'No Metadata Student';
        $student->sureName = 'Science';
        $student->sessName = $session->id;
        $student->className = $class->id;
        $student->sectionName = $section->id;
        $student->departmentName = $science->id;
        $student->religiousSubjectId = $religion->id;
        $student->fourthSubjectId = $higherMath->id;
        $student->rollNumber = '01';
        $student->save();

        $this->assertSame(0, DB::table('curriculum_subject_mappings')->count());
        $this->assertSame(0, DB::table('marksheets')->count());

        $response = $this->get(route('marksheetGenerate', [
            'studentId' => $student->id,
            'examId' => $exam->id,
        ]));

        $response->assertOk()->assertViewIs('result.marksheetGenerate');

        $transcript = $response->viewData('transcriptResult');
        $mainNames = collect($transcript['mainRows'])->pluck('name')->all();
        $optionalNames = collect($transcript['optionalRows'])->pluck('name')->all();

        // Structured fail-closed: no inferred curriculum rows from global assign_class=0 subjects.
        $this->assertSame('Incomplete', $transcript['status']);
        $this->assertSame(['Islam and moral education-111'], $mainNames);
        $this->assertSame(['Higher Math-126'], $optionalNames);
        $this->assertFalse((bool) ($transcript['curriculumStatus']['configured'] ?? true));

        foreach ([
            'Bangla 1st Paper',
            'Physics-136',
            'Chemistry-137',
            'Biology-138',
            'Accounting-146',
            'Finance and Banking-152',
            'Business Entrepreneurship-143',
            'History of Bangladesh and World Civilization-153',
            'Civics and Citizenship-140',
            'Geography and Environment-110',
        ] as $unexpected) {
            $this->assertNotContains($unexpected, $mainNames);
            $this->assertNotContains($unexpected, $optionalNames);
            $response->assertDontSee($unexpected);
        }

        // Fourth subject must remain optional-only.
        $this->assertNotContains('Higher Math-126', $mainNames);
        $this->assertContains('Higher Math-126', $optionalNames);
    }

    private function subject(string $name, string $type, string $assignClass, bool $isReligious = false): Subject
    {
        return Subject::create([
            'subjectName' => $name,
            'subjectType' => $type,
            'assign_class' => $assignClass,
            'isReligious' => $isReligious,
            'CQ' => 100,
        ]);
    }
}

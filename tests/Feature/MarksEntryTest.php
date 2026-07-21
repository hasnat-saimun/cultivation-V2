<?php

namespace Tests\Feature;

use App\Http\Controllers\MarksheetController;
use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Tests\TestCase;

class MarksEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_optional_group_is_hidden_initially_and_gender_defaults_to_all(): void
    {
        $this->createSession();
        $this->createClass('Class 8');
        $this->createClass('Class 9');
        $this->createSection('A');
        $this->createDepartment('Science');
        $this->createExam('Half Yearly');

        $response = app(MarksheetController::class)->addMarks();

        $this->assertInstanceOf(View::class, $response);
        $html = $response->render();

        $this->assertMatchesRegularExpression('/<div class="col-12 form-group\\s+d-none" id="optional_group_wrapper">/', $html);
        $this->assertStringContainsString('<option value="all" selected>All</option>', $html);
    }

    public function test_class_nine_and_ten_are_marked_as_group_classes(): void
    {
        $this->createSession();
        $classNine = $this->createClass('Class IX');
        $classTen = $this->createClass('10');
        $classEight = $this->createClass('Class 8');

        $response = app(MarksheetController::class)->addMarks();

        $this->assertInstanceOf(View::class, $response);
        $groupMap = $response->getData()['classGroupRequirementMap'];

        $this->assertTrue($groupMap[(string) $classNine->id]);
        $this->assertTrue($groupMap[(string) $classTen->id]);
        $this->assertFalse($groupMap[(string) $classEight->id]);
    }

    public function test_optional_group_is_required_for_group_classes(): void
    {
        $session = $this->createSession();
        $classNine = $this->createClass('Class 9');
        $section = $this->createSection('A');
        $exam = $this->createExam('Annual');
        $subject = $this->createSubject('Bangla');

        $request = Request::create('/marks/add/getData', 'POST', [
            'sessionId' => $session->id,
            'classId' => $classNine->id,
            'groupId' => $section->id,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]);

        try {
            app(MarksheetController::class)->getMarks($request);
            $this->fail('Expected optional group validation to fail for Class 9.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('optionalGroupId', $exception->errors());
        }
    }

    public function test_gender_all_returns_male_and_female_students(): void
    {
        [$session, $class, $section, $dept, $exam, $subject] = $this->createMarksScope('Class 8');
        $male = $this->createStudent($session, $class, $section, $dept, '1', '01');
        $female = $this->createStudent($session, $class, $section, $dept, '2', '02');

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]));

        $studentIds = $response->getData()['studentList']->pluck('id')->all();
        $this->assertSame([$male->id, $female->id], $studentIds);
    }

    public function test_gender_filter_returns_only_male_students(): void
    {
        [$session, $class, $section, $dept, $exam, $subject] = $this->createMarksScope('Class 8');
        $male = $this->createStudent($session, $class, $section, $dept, '1', '01');
        $this->createStudent($session, $class, $section, $dept, '2', '02');

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'gender' => '1',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]));

        $studentIds = $response->getData()['studentList']->pluck('id')->all();
        $this->assertSame([$male->id], $studentIds);
    }

    public function test_invalid_gender_is_rejected(): void
    {
        [$session, $class, $section, $dept, $exam, $subject] = $this->createMarksScope('Class 8');

        $request = $this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => $dept->id,
            'gender' => 'male',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]);

        try {
            app(MarksheetController::class)->getMarks($request);
            $this->fail('Expected invalid gender validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('gender', $exception->errors());
        }
    }

    public function test_stale_optional_group_is_ignored_for_non_group_classes(): void
    {
        [$session, $class, $sectionA, $dept, $exam, $subject] = $this->createMarksScope('Class 8');
        $sectionB = $this->createSection('B');
        $target = $this->createStudent($session, $class, $sectionA, $dept, '1', '01');
        $this->createStudent($session, $class, $sectionB, $dept, '1', '02');

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $sectionA->id,
            'optionalGroupId' => $dept->id,
            'gender' => '1',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]));

        $viewData = $response->getData();
        $this->assertNull($viewData['optionalGroupId']);
        $this->assertSame([$target->id], $viewData['studentList']->pluck('id')->all());
    }

    public function test_teacher_authorization_still_blocks_unassigned_subject(): void
    {
        [$session, $class, $section, $dept, $exam, $subject] = $this->createMarksScope('Class 8');
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'group_id' => null,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $unauthorizedSubject = $this->createSubject('English');
        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $unauthorizedSubject->id,
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('marks/add', $response->getTargetUrl());
    }

    public function test_student_ordering_and_existing_marks_do_not_change_on_load(): void
    {
        [$session, $class, $section, $dept, $exam, $subject] = $this->createMarksScope('Class 8');
        $studentB = $this->createStudent($session, $class, $section, $dept, '1', '10');
        $studentA = $this->createStudent($session, $class, $section, $dept, '2', '02');

        $marksheet = new Marksheet();
        $marksheet->studentId = $studentA->id;
        $marksheet->classId = $class->id;
        $marksheet->sessionId = (string) $session->id;
        $marksheet->groupId = $section->id;
        $marksheet->examId = $exam->id;
        $marksheet->subjectId = $subject->id;
        $marksheet->subjectMarks = 70;
        $marksheet->totalMarks = 70;
        $marksheet->laterGrade = 'A';
        $marksheet->gradePoint = 4;
        $marksheet->save();

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]));

        $studentIds = $response->getData()['studentList']->pluck('id')->all();
        $this->assertSame([$studentA->id, $studentB->id], $studentIds);
        $this->assertDatabaseHas('marksheets', [
            'id' => $marksheet->id,
            'studentId' => $studentA->id,
            'subjectMarks' => 70,
        ]);
    }

    private function marksRequest(array $payload): Request
    {
        return Request::create('/marks/add/getData', 'POST', $payload);
    }

    private function createMarksScope(string $className): array
    {
        $session = $this->createSession();
        $class = $this->createClass($className);
        $section = $this->createSection('A');
        $department = $this->createDepartment('Science');
        $exam = $this->createExam('Annual');
        $subject = $this->createSubject('Bangla');

        return [$session, $class, $section, $department, $exam, $subject];
    }

    private function createSession(string $name = '2026'): sessionManage
    {
        $session = new sessionManage();
        $session->session = $name;
        $session->save();

        return $session;
    }

    private function createClass(string $name): classManage
    {
        $class = new classManage();
        $class->className = $name;
        $class->save();

        return $class;
    }

    private function createSection(string $name): sectionManage
    {
        $section = new sectionManage();
        $section->section = $name;
        $section->save();

        return $section;
    }

    private function createDepartment(string $name): Department
    {
        $department = new Department();
        $department->departmentName = $name;
        $department->save();

        return $department;
    }

    private function createExam(string $name): Exam
    {
        $exam = new Exam();
        $exam->examName = $name;
        $exam->passingSystem = 0;
        $exam->save();

        return $exam;
    }

    private function createSubject(string $name): Subject
    {
        $subject = new Subject();
        $subject->subjectName = $name;
        $subject->subjectType = 'Theory';
        $subject->save();

        return $subject;
    }

    private function createStudent(sessionManage $session, classManage $class, sectionManage $section, Department $department, string $gender, string $roll): newAdmission
    {
        $student = new newAdmission();
        $student->stdId = (string) random_int(1000, 999999);
        $student->fullName = 'Student';
        $student->sureName = $roll;
        $student->gender = $gender;
        $student->sessName = (string) $session->id;
        $student->className = $class->id;
        $student->sectionName = $section->id;
        $student->departmentName = $department->id;
        $student->rollNumber = $roll;
        $student->save();

        return $student;
    }

    private function createAdmin(int $role): CultivationAdmin
    {
        $admin = new CultivationAdmin();
        $admin->adminName = 'Admin '.uniqid();
        $admin->adminUser = 'user_'.uniqid();
        $admin->userType = $role;
        $admin->loginPassword = Hash::make('secret123');
        $admin->adminMobile = '01700000000';
        $admin->adminMail = uniqid('admin_', true).'@example.test';
        $admin->save();

        return $admin;
    }
}

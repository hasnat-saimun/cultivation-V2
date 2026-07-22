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
use App\Services\DepartmentBasedClassDetector;
use App\Services\MarksEntryAuthorizationService;
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

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
    }

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

    public function test_class_nine_and_above_are_marked_as_group_classes(): void
    {
        $this->createSession();
        $classNine = $this->createClass('Class IX');
        $classTen = $this->createClass('10');
        $classEleven = $this->createClass('Class XI');
        $classTwelve = $this->createClass('দ্বাদশ শ্রেণি');
        $classEight = $this->createClass('Class 8');

        $response = app(MarksheetController::class)->addMarks();

        $this->assertInstanceOf(View::class, $response);
        $groupMap = $response->getData()['classGroupRequirementMap'];

        $this->assertTrue($groupMap[(string) $classNine->id]);
        $this->assertTrue($groupMap[(string) $classTen->id]);
        $this->assertTrue($groupMap[(string) $classEleven->id]);
        $this->assertTrue($groupMap[(string) $classTwelve->id]);
        $this->assertFalse($groupMap[(string) $classEight->id]);
    }

    public function test_department_detector_handles_supported_and_unsafe_patterns(): void
    {
        $detector = app(DepartmentBasedClassDetector::class);

        $trueCases = [
            'Class 9', 'Class Nine', 'Nine', 'Class 10', 'Class XI',
            'নবম শ্রেণি', 'দশম শ্রেণি', 'SSC Batch', 'HSC Batch',
        ];
        $falseCases = ['Class 8', 'Class 1', 'Class 19', 'Batch 2019', 'Roll 10'];

        foreach ($trueCases as $case) {
            $this->assertTrue($detector->isDepartmentBasedClass($case), 'Expected true for: '.$case);
        }

        foreach ($falseCases as $case) {
            $this->assertFalse($detector->isDepartmentBasedClass($case), 'Expected false for: '.$case);
        }
    }

    public function test_null_gender_scope_normalizes_to_all(): void
    {
        $service = app(MarksEntryAuthorizationService::class);

        $this->assertSame('all', $service->normalizeGenderScope(null));
    }

    public function test_blank_gender_scope_normalizes_to_all(): void
    {
        $service = app(MarksEntryAuthorizationService::class);

        $this->assertSame('all', $service->normalizeGenderScope(''));
        $this->assertSame('all', $service->normalizeGenderScope('   '));
    }

    public function test_all_departments_loads_students_from_every_department_for_group_classes(): void
    {
        $session = $this->createSession();
        $classNine = $this->createClass('Class 9');
        $section = $this->createSection('A');
        $science = $this->createDepartment('Science');
        $business = $this->createDepartment('Business Studies');
        $exam = $this->createExam('Annual');
        $subject = $this->createSubject('Bangla');

        $scienceStudent = $this->createStudent($session, $classNine, $section, $science, '1', '01');
        $businessStudent = $this->createStudent($session, $classNine, $section, $business, '2', '02');

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $classNine->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]));

        $viewData = $response->getData();
        $this->assertNull($viewData['optionalGroupId']);
        $this->assertSame(
            [$scienceStudent->id, $businessStudent->id],
            $viewData['studentList']->pluck('id')->all()
        );
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

    public function test_malformed_ajax_gender_values_are_rejected(): void
    {
        [$session, $class, $section, $dept, $exam, $subject] = $this->createMarksScope('Class 8');

        $request = $this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => $dept->id,
            'gender' => ['malformed'],
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]);

        try {
            app(MarksheetController::class)->getMarks($request);
            $this->fail('Expected malformed AJAX gender payload validation to fail.');
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

    public function test_legacy_subject_assignments_work_without_composite_rows_and_are_filtered_by_selected_class(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $classA = $this->createClass('Class 8');
        $classB = $this->createClass('Class 9');
        $subjectA = $this->createSubject('Legacy Subject A');
        $subjectB = $this->createSubject('Legacy Subject B');

        DB::table('teacher_subjects')->insert([
            ['teacher_id' => $teacher->id, 'subject_id' => $subjectA->id, 'created_at' => now(), 'updated_at' => now()],
            ['teacher_id' => $teacher->id, 'subject_id' => $subjectB->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('teacher_classes')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $classA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $allowed = $this->postJson(route('api.marks.subjects'), [
            'classId' => $classA->id,
            'sectionId' => null,
            'optionalGroupId' => null,
        ]);

        $allowed->assertOk();
        $allowedIds = collect($allowed->json())->pluck('id')->map(fn ($v) => (int) $v)->sort()->values()->all();
        $this->assertEqualsCanonicalizing([$subjectA->id, $subjectB->id], $allowedIds);

        $denied = $this->postJson(route('api.marks.subjects'), [
            'classId' => $classB->id,
            'sectionId' => null,
            'optionalGroupId' => null,
        ]);

        $denied->assertOk();
        $this->assertSame([], $denied->json());
    }

    public function test_composite_assignments_take_precedence_over_legacy_subjects(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $classA = $this->createClass('Class 8');
        $classB = $this->createClass('Class 9');
        $section = $this->createSection('A');
        $compositeSubject = $this->createSubject('Composite Subject');
        $legacySubject = $this->createSubject('Legacy Subject');

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $teacher->id,
            'subject_id' => $legacySubject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_classes')->insert([
            ['teacher_id' => $teacher->id, 'class_id' => $classA->id, 'created_at' => now(), 'updated_at' => now()],
            ['teacher_id' => $teacher->id, 'class_id' => $classB->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $classA->id,
            'section_id' => $section->id,
            'group_id' => null,
            'subject_id' => $compositeSubject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $forClassA = $this->postJson(route('api.marks.subjects'), [
            'classId' => $classA->id,
            'sectionId' => $section->id,
        ]);
        $forClassA->assertOk();
        $classAIds = collect($forClassA->json())->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->assertSame([$compositeSubject->id], $classAIds);

        $forClassB = $this->postJson(route('api.marks.subjects'), [
            'classId' => $classB->id,
            'sectionId' => $section->id,
        ]);
        $forClassB->assertOk();
        $classBIds = collect($forClassB->json())->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->assertSame([$legacySubject->id], $classBIds);
    }

    public function test_subject_endpoint_accepts_form_camel_case_parameter_names(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $classA = $this->createClass('Class 8');
        $sectionA = $this->createSection('A');
        $subject = $this->createSubject('Contract Subject');

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $classA->id,
            'section_id' => $sectionA->id,
            'group_id' => null,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = $this->postJson(route('api.marks.subjects'), [
            'classId' => $classA->id,
            'sectionId' => $sectionA->id,
            'optionalGroupId' => null,
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $subject->id,
            'subjectName' => $subject->subjectName,
        ]);
    }

    public function test_mixed_teacher_profile_uses_legacy_fallback_for_contexts_without_composite_rows(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $classWithComposite = $this->createClass('Class 8');
        $classLegacyOnly = $this->createClass('Class 9');
        $section = $this->createSection('A');
        $compositeSubject = $this->createSubject('Composite Subject');
        $legacySubject = $this->createSubject('Legacy Subject');

        DB::table('teacher_classes')->insert([
            ['teacher_id' => $teacher->id, 'class_id' => $classWithComposite->id, 'created_at' => now(), 'updated_at' => now()],
            ['teacher_id' => $teacher->id, 'class_id' => $classLegacyOnly->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $teacher->id,
            'subject_id' => $legacySubject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $classWithComposite->id,
            'section_id' => $section->id,
            'group_id' => null,
            'subject_id' => $compositeSubject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $compositeResponse = $this->postJson(route('api.marks.subjects'), [
            'classId' => $classWithComposite->id,
            'sectionId' => $section->id,
        ]);
        $compositeResponse->assertOk();
        $compositeIds = collect($compositeResponse->json())->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->assertSame([$compositeSubject->id], $compositeIds);

        $legacyResponse = $this->postJson(route('api.marks.subjects'), [
            'classId' => $classLegacyOnly->id,
            'sectionId' => $section->id,
        ]);
        $legacyResponse->assertOk();
        $legacyIds = collect($legacyResponse->json())->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->assertSame([$legacySubject->id], $legacyIds);
    }

    public function test_stale_composite_section_is_ignored_and_valid_legacy_subjects_are_returned(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $class = $this->createClass('Class 8');
        $realSection = $this->createSection('A');
        $legacySubject = $this->createSubject('Legacy Subject');
        $staleSubject = $this->createSubject('Stale Composite Subject');

        DB::table('teacher_classes')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $teacher->id,
            'subject_id' => $legacySubject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_sections')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'section_id' => $realSection->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'section_id' => 999999,
            'group_id' => null,
            'subject_id' => $staleSubject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = $this->postJson(route('api.marks.subjects'), [
            'classId' => $class->id,
            'sectionId' => $realSection->id,
        ]);

        $response->assertOk();
        $ids = collect($response->json())->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->assertSame([$legacySubject->id], $ids);
    }

    public function test_classes_endpoint_returns_all_classes_for_admin_and_only_assigned_classes_for_teacher(): void
    {
        $session = $this->createSession();
        $exam = $this->createExam('Annual');
        $classA = $this->createClass('Class 8');
        $classB = $this->createClass('Class 9');

        $admin = $this->createAdmin(CultivationAdmin::ROLE_GENERAL);

        $adminResponse = $this->withSession(['cultivationAdmin' => $admin->id])->postJson(route('api.marks.classes'), [
            'examId' => $exam->id,
            'sessionId' => $session->id,
        ]);
        $adminResponse->assertOk();
        $adminIds = collect($adminResponse->json('classes'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertEqualsCanonicalizing([$classA->id, $classB->id], $adminIds);

        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        DB::table('teacher_classes')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $classA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacherResponse = $this->withSession(['cultivationAdmin' => $teacher->id])->postJson(route('api.marks.classes'), [
            'examId' => $exam->id,
            'sessionId' => $session->id,
        ]);
        $teacherResponse->assertOk();
        $teacherIds = collect($teacherResponse->json('classes'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($classA->id, $teacherIds);
    }

    public function test_sections_endpoint_returns_only_teacher_context_sections(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $class = $this->createClass('Class 8');
        $sectionA = $this->createSection('A');
        $sectionB = $this->createSection('B');
        $session = $this->createSession();
        $subject = $this->createSubject('Bangla');

        DB::table('teacher_classes')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'section_id' => $sectionA->id,
            'group_id' => null,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_sections')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'section_id' => $sectionB->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = $this->postJson(route('api.marks.sections'), [
            'classId' => $class->id,
            'sessionId' => $session->id,
        ]);

        $response->assertOk();
        $ids = collect($response->json('sections'))->pluck('id')->map(fn ($v) => (int) $v)->sort()->values()->all();
        $this->assertEqualsCanonicalizing([$sectionA->id, $sectionB->id], $ids);
    }

    public function test_classes_endpoint_includes_requires_department_flag(): void
    {
        $session = $this->createSession();
        $exam = $this->createExam('Annual');
        $classEight = $this->createClass('Class 8');
        $classNine = $this->createClass('Class 9');

        $admin = $this->createAdmin(CultivationAdmin::ROLE_GENERAL);
        Session::put('cultivationAdmin', $admin->id);

        $response = $this->postJson(route('api.marks.classes'), [
            'examId' => $exam->id,
            'sessionId' => $session->id,
        ]);

        $response->assertOk();
        $classes = collect($response->json('classes'))->keyBy('id');

        $this->assertArrayHasKey('requires_department', $classes[$classEight->id]);
        $this->assertFalse((bool) $classes[$classEight->id]['requires_department']);
        $this->assertTrue((bool) $classes[$classNine->id]['requires_department']);
    }

    public function test_teacher_gender_scope_all_returns_both_male_and_female_students(): void
    {
        [$session, $class, $section, $dept, $exam, $subject] = $this->createMarksScope('Class 9');
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        $male = $this->createStudent($session, $class, $section, $dept, '1', '01');
        $female = $this->createStudent($session, $class, $section, $dept, '2', '02');

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'group_id' => $dept->id,
            'subject_id' => $subject->id,
            'gender_scope' => 'all',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => $dept->id,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]));

        $this->assertSame([$male->id, $female->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_teacher_gender_scope_male_returns_only_male_students(): void
    {
        [$session, $class, $section, $dept, $exam, $subject] = $this->createMarksScope('Class 9');
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        $male = $this->createStudent($session, $class, $section, $dept, '1', '01');
        $this->createStudent($session, $class, $section, $dept, '2', '02');

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'group_id' => $dept->id,
            'subject_id' => $subject->id,
            'gender_scope' => 'male',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => $dept->id,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]));

        $this->assertSame([$male->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_unknown_gender_scope_does_not_grant_all_student_access(): void
    {
        [$session, $class, $section, $dept, $exam, $subject] = $this->createMarksScope('Class 9');
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        $this->createStudent($session, $class, $section, $dept, '1', '01');
        $this->createStudent($session, $class, $section, $dept, '2', '02');

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'group_id' => $dept->id,
            'subject_id' => $subject->id,
            'gender_scope' => 'invalidx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => $dept->id,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('marks/add', $response->getTargetUrl());
    }

    public function test_all_department_for_teacher_aggregates_only_assigned_department_gender_pairs(): void
    {
        $session = $this->createSession();
        $class = $this->createClass('Class 9');
        $section = $this->createSection('A');
        $science = $this->createDepartment('Science');
        $commerce = $this->createDepartment('Commerce');
        $unassigned = $this->createDepartment('Arts');
        $exam = $this->createExam('Annual');
        $subject = $this->createSubject('Bangla');
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        $scienceMale = $this->createStudent($session, $class, $section, $science, '1', '01');
        $this->createStudent($session, $class, $section, $science, '2', '02');
        $commerceFemale = $this->createStudent($session, $class, $section, $commerce, '2', '03');
        $this->createStudent($session, $class, $section, $commerce, '1', '04');
        $this->createStudent($session, $class, $section, $unassigned, '1', '05');

        DB::table('teacher_class_subjects')->insert([
            [
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'section_id' => null,
                'group_id' => $science->id,
                'subject_id' => $subject->id,
                'gender_scope' => 'male',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'section_id' => null,
                'group_id' => $commerce->id,
                'subject_id' => $subject->id,
                'gender_scope' => 'female',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
        ]));

        $this->assertSame(
            [$scienceMale->id, $commerceFemale->id],
            $response->getData()['studentList']->pluck('id')->all()
        );
    }

    public function test_admin_subjects_include_global_assign_class_zero_and_class_specific_subjects(): void
    {
        $admin = $this->createAdmin(CultivationAdmin::ROLE_GENERAL);
        $classA = $this->createClass('Class 5');
        $classB = $this->createClass('Class 6');

        $globalSubject = $this->createSubject('Global Subject');
        $globalSubject->assign_class = '0';
        $globalSubject->save();

        $classASubject = $this->createSubject('Class A Subject');
        $classASubject->assign_class = (string) $classA->id;
        $classASubject->save();

        $otherClassSubject = $this->createSubject('Other Class Subject');
        $otherClassSubject->assign_class = (string) $classB->id;
        $otherClassSubject->save();

        Session::put('cultivationAdmin', $admin->id);

        $responseForClassA = $this->postJson(route('api.marks.subjects'), [
            'classId' => $classA->id,
        ]);
        $responseForClassA->assertOk();

        $idsForClassA = collect($responseForClassA->json())->pluck('id')->map(fn ($v) => (int) $v)->sort()->values()->all();
        $this->assertEqualsCanonicalizing([
            (int) $classASubject->id,
            (int) $globalSubject->id,
        ], $idsForClassA);

        $responseForClassB = $this->postJson(route('api.marks.subjects'), [
            'classId' => $classB->id,
        ]);
        $responseForClassB->assertOk();

        $idsForClassB = collect($responseForClassB->json())->pluck('id')->map(fn ($v) => (int) $v)->sort()->values()->all();
        $this->assertEqualsCanonicalizing([
            (int) $globalSubject->id,
            (int) $otherClassSubject->id,
        ], $idsForClassB);
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

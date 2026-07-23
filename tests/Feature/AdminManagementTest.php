<?php

namespace Tests\Feature;

use App\Http\Controllers\CultivationController;
use App\Http\Middleware\Roles;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\sessionManage;
use App\Models\sectionManage;
use App\Models\Subject;
use App\Models\TeacherClassSubject;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_create_form_shows_subjects_even_if_assigned_to_other_admins(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $teacherOne = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);
        $assignedSubject = $this->createSubject('Assigned Subject');
        $freeSubject = $this->createSubject('Free Subject');

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $teacherOne->id,
            'subject_id' => $assignedSubject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $generalAdmin->id);
        $response = app(CultivationController::class)->userType();

        $this->assertInstanceOf(View::class, $response);
        $subjectIds = collect($response->getData()['subjectList'])->pluck('id')->all();

        $this->assertContains($assignedSubject->id, $subjectIds);
        $this->assertContains($freeSubject->id, $subjectIds);
    }

    public function test_create_form_excludes_attendance_classes_claimed_without_section_and_keeps_unassigned_visible(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $teacherOne = $this->createAdmin([
            'userType' => CultivationAdmin::ROLE_TEACHER,
            'primary_class_id' => null,
            'primary_section_id' => null,
        ]);
        $takenClass = $this->createClass('Taken Class');
        $freeClass = $this->createClass('Free Class');

        $teacherOne->primary_class_id = $takenClass->id;
        $teacherOne->primary_section_id = null;
        $teacherOne->save();

        Session::put('cultivationAdmin', $generalAdmin->id);
        $response = app(CultivationController::class)->userType();

        $this->assertInstanceOf(View::class, $response);
        $classIds = collect($response->getData()['attendanceClassList'])->pluck('id')->all();

        $this->assertNotContains($takenClass->id, $classIds);
        $this->assertContains($freeClass->id, $classIds);
    }

    public function test_edit_form_keeps_subjects_visible_across_teachers(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $targetTeacher = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);
        $otherTeacher = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);
        $ownSubject = $this->createSubject('Own Subject');
        $otherSubject = $this->createSubject('Other Subject');
        $ownClass = $this->createClass('Own Attendance Class');
        $otherClass = $this->createClass('Other Attendance Class');

        DB::table('teacher_subjects')->insert([
            [
                'teacher_id' => $targetTeacher->id,
                'subject_id' => $ownSubject->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'teacher_id' => $otherTeacher->id,
                'subject_id' => $otherSubject->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $targetTeacher->primary_class_id = $ownClass->id;
        $targetTeacher->save();
        $otherTeacher->primary_class_id = $otherClass->id;
        $otherTeacher->save();

        Session::put('cultivationAdmin', $generalAdmin->id);
        $response = app(CultivationController::class)->editUser($targetTeacher->id);

        $this->assertInstanceOf(View::class, $response);
        $subjectIds = collect($response->getData()['subjectList'])->pluck('id')->all();
        $classIds = collect($response->getData()['attendanceClassList'])->pluck('id')->all();

        $this->assertContains($ownSubject->id, $subjectIds);
        $this->assertContains($otherSubject->id, $subjectIds);
        $this->assertContains($ownClass->id, $classIds);
        $this->assertNotContains($otherClass->id, $classIds);
    }

    public function test_subject_assignment_not_globally_blocked_by_legacy_teacher_subjects_table(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $teacherOne = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);
        $subject = $this->createSubject('Locked Subject');
        $class = $this->createClass('Class 1');

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $teacherOne->id,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/save/admin', 'POST', $this->teacherPayload([
                'userName' => 'new-teacher',
                'userMail' => 'new-teacher@example.test',
                'className' => [$class->id],
                'section' => ['none'],
                'subject' => [$subject->id],
            ]));

        app(CultivationController::class)->saveUser($request);

        $this->assertDatabaseHas('cultivation_admins', ['adminUser' => 'new-teacher']);
    }

    public function test_manipulated_request_cannot_assign_attendance_class_taken_by_another_admin(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $teacherOne = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);
        $subject = $this->createSubject('Available Subject');
        $class = $this->createClass('Attendance Class');

        $teacherOne->primary_class_id = $class->id;
        $teacherOne->primary_section_id = null;
        $teacherOne->save();

        $countBefore = CultivationAdmin::count();

        $request = Request::create('/save/admin', 'POST', $this->teacherPayload([
                'userName' => 'new-attendance-teacher',
                'userMail' => 'attendance@example.test',
                'primaryClass' => $class->id,
                'primarySection' => '',
                'className' => [$class->id],
                'section' => ['none'],
                'subject' => [$subject->id],
            ]));

        try {
            app(CultivationController::class)->saveUser($request);
            $this->fail('Expected duplicate attendance assignment validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('primaryClass', $exception->errors());
        }

        $this->assertSame($countBefore, CultivationAdmin::count());
        $this->assertDatabaseMissing('cultivation_admins', ['adminUser' => 'new-attendance-teacher']);
    }

    public function test_assignment_with_legacy_teacher_subject_link_still_allows_teacher_creation(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $teacherOne = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);
        $subject = $this->createSubject('Subject Rollback');
        $class = $this->createClass('Rollback Class');

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $teacherOne->id,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/save/admin', 'POST', $this->teacherPayload([
                'userName' => 'rollback-teacher',
                'userMail' => 'rollback@example.test',
                'className' => [$class->id],
                'section' => ['none'],
                'subject' => [$subject->id],
            ]));

        app(CultivationController::class)->saveUser($request);

        $this->assertDatabaseHas('cultivation_admins', ['adminUser' => 'rollback-teacher']);
    }

    public function test_admin_list_loads_with_datatable_config_and_actions(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $listedAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER, 'adminName' => 'Listed Admin']);
        $subject = $this->createSubject('List Subject');
        $class = $this->createClass('List Class');

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $listedAdmin->id,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $listedAdmin->primary_class_id = $class->id;
        $listedAdmin->save();

        Session::put('cultivationAdmin', $generalAdmin->id);
        $response = app(CultivationController::class)->userRegList();

        $this->assertInstanceOf(View::class, $response);
        $html = $response->render();
        $this->assertStringContainsString('registeredUsersTable', $html);
        $this->assertStringContainsString('DataTable({', $html);
        $this->assertStringContainsString('lengthMenu: [10, 25, 50, 100]', $html);
        $this->assertStringContainsString("order: [[1, 'asc']]", $html);
        $this->assertStringContainsString('/admin/edit/'.$listedAdmin->id, $html);
        $this->assertStringContainsString('/admin/delete/'.$listedAdmin->id, $html);
        $this->assertStringContainsString('List Subject', $html);
        $this->assertStringContainsString('List Class', $html);
    }

    public function test_admin_list_shows_type_of_user_column_and_labels(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $teacherAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER, 'adminName' => 'Teacher Listed']);
        $cashAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_CASH, 'adminName' => 'Cash Listed']);
        $unknownAdmin = $this->createAdmin(['userType' => null, 'adminName' => 'Unknown Listed']);

        Session::put('cultivationAdmin', $generalAdmin->id);
        $response = app(CultivationController::class)->userRegList();

        $this->assertInstanceOf(View::class, $response);
        $html = $response->render();
        $this->assertStringContainsString('Type of User', $html);
        $this->assertStringContainsString('Teacher Admin', $html);
        $this->assertStringContainsString('Cash Admin', $html);
        $this->assertStringContainsString('General Admin', $html);
        $this->assertStringContainsString('Unknown', $html);
        $this->assertStringContainsString('columns: [0,1,2,3,4,5]', $html);
        $this->assertStringContainsString('{ targets: 6, orderable: false, searchable: false }', $html);
    }

    public function test_admin_modern_user_list_shows_type_of_user_labels_from_model_accessor(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER, 'adminName' => 'Modern Teacher']);
        $this->createAdmin(['userType' => CultivationAdmin::ROLE_CASH, 'adminName' => 'Modern Cash']);
        $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL, 'adminName' => 'Modern General']);
        $this->createAdmin(['userType' => null, 'adminName' => 'Modern Unknown']);

        $response = $this
            ->withSession(['cultivationAdmin' => $generalAdmin->id])
            ->get(route('adminModernUsersIndex'));

        $response->assertOk();
        $response->assertSee('Type of User');
        $response->assertSee('Teacher Admin');
        $response->assertSee('Cash Admin');
        $response->assertSee('General Admin');
        $response->assertSee('Unknown');
        $response->assertSee('Search:');
    }

    public function test_general_admin_can_delete_admin(): void
    {
        $generalAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_GENERAL]);
        $listedAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);

        $response = app(CultivationController::class)->deleteUser($listedAdmin->id);

        $this->assertTrue(method_exists($response, 'isRedirect') ? $response->isRedirect() : true);
        $this->assertDatabaseMissing('cultivation_admins', ['id' => $listedAdmin->id]);
    }

    public function test_unauthorized_admins_cannot_manage_admin_module(): void
    {
        $teacherAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);
        $cashAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_CASH]);
        $listedAdmin = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);

        $middleware = app(Roles::class);

        Session::put('cultivationAdmin', $teacherAdmin->id);
        try {
            $middleware->handle(Request::create('/admin/creation', 'GET'), fn () => response('ok'), 3);
            $this->fail('Teacher admin should not pass the roles middleware.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        Session::put('cultivationAdmin', $cashAdmin->id);
        try {
            $middleware->handle(Request::create('/admin/list', 'GET'), fn () => response('ok'), 3);
            $this->fail('Cash admin should not pass the roles middleware.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        Session::put('cultivationAdmin', $listedAdmin->id);
        try {
            $middleware->handle(Request::create('/admin/edit/'.$listedAdmin->id, 'GET'), fn () => response('ok'), 3);
            $this->fail('Teacher admin should not be able to reassign admins.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_repeated_unchanged_teacher_update_does_not_duplicate_teacher_class_subject_rows(): void
    {
        $teacher = $this->createAdmin(['userType' => CultivationAdmin::ROLE_TEACHER]);
        $class = $this->createClass('Idempotent Class');
        $section = $this->createSection('A');
        $subject = $this->createSubject('Idempotent Subject');

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacher->primary_class_id = $class->id;
        $teacher->primary_section_id = $section->id;
        $teacher->save();

        $payload = [
            'userId' => $teacher->id,
            'adminName' => $teacher->adminName,
            'userName' => $teacher->adminUser,
            'userMobile' => $teacher->adminMobile,
            'userMail' => $teacher->adminMail,
            'userType' => CultivationAdmin::ROLE_TEACHER,
            'pass' => '',
            'primaryClass' => $class->id,
            'primarySection' => $section->id,
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [''],
            'genderScope' => ['all'],
            'subject' => [$subject->id],
        ];

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));

        $firstCount = DB::table('teacher_class_subjects')
            ->where('teacher_id', $teacher->id)
            ->count();

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));

        $secondCount = DB::table('teacher_class_subjects')
            ->where('teacher_id', $teacher->id)
            ->count();

        $uniqueCount = DB::table('teacher_class_subjects')
            ->where('teacher_id', $teacher->id)
            ->selectRaw('COUNT(DISTINCT CONCAT(class_id, ":", COALESCE(section_id, "n"), ":", COALESCE(group_id, "n"), ":", COALESCE(subject_id, "n"), ":", COALESCE(gender_scope, "all"))) as total')
            ->value('total');

        $this->assertSame(1, $firstCount);
        $this->assertSame($firstCount, $secondCount);
        $this->assertSame($secondCount, (int) $uniqueCount);
    }

    public function test_new_teacher_assignment_defaults_gender_scope_to_all(): void
    {
        $class = $this->createClass('Scope Class');
        $section = $this->createSection('A');
        $subject = $this->createSubject('Scope Subject');

        $request = Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'scope-default-teacher',
            'userMail' => 'scope-default-teacher@example.test',
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [''],
            'subject' => [$subject->id],
        ]));

        app(CultivationController::class)->saveUser($request);

        $teacherId = CultivationAdmin::where('adminUser', 'scope-default-teacher')->value('id');
        $this->assertNotNull($teacherId);
        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'gender_scope' => 'all',
        ]);
    }

    public function test_teacher_assignment_gender_scope_stores_male_and_female_values(): void
    {
        $class = $this->createClass('Gender Scope Class');
        $section = $this->createSection('B');
        $subjectMale = $this->createSubject('Male Scope Subject');
        $subjectFemale = $this->createSubject('Female Scope Subject');

        $request = Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'scope-gender-teacher',
            'userMail' => 'scope-gender-teacher@example.test',
            'className' => [$class->id, $class->id],
            'section' => [$section->id, $section->id],
            'optionalGroup' => ['', ''],
            'genderScope' => ['male', 'female'],
            'subject' => [$subjectMale->id, $subjectFemale->id],
        ]));

        app(CultivationController::class)->saveUser($request);

        $teacherId = CultivationAdmin::where('adminUser', 'scope-gender-teacher')->value('id');
        $this->assertNotNull($teacherId);

        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'subject_id' => $subjectMale->id,
            'gender_scope' => 'male',
        ]);
        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'subject_id' => $subjectFemale->id,
            'gender_scope' => 'female',
        ]);
    }

    public function test_gender_scope_label_accessor_defaults_unknown_values_to_all(): void
    {
        $row = new TeacherClassSubject();
        $row->gender_scope = null;
        $this->assertSame('All', $row->gender_scope_label);

        $row->gender_scope = 'male';
        $this->assertSame('Male', $row->gender_scope_label);

        $row->gender_scope = 'female';
        $this->assertSame('Female', $row->gender_scope_label);
    }

    private function teacherPayload(array $overrides = []): array
    {
        return array_merge([
            'adminName' => 'New Teacher Admin',
            'userName' => 'teacher-admin',
            'userMobile' => '01700000000',
            'userMail' => 'teacher-admin@example.test',
            'userType' => CultivationAdmin::ROLE_TEACHER,
            'pass' => 'secret123',
            'confirmPass' => 'secret123',
            'assignmentSessionId' => $this->ensureSession()->id,
            'primaryClass' => '',
            'primarySection' => '',
            'className' => [],
            'section' => [],
            'optionalGroup' => [],
            'genderScope' => [],
            'subject' => [],
        ], $overrides);
    }

    private function ensureSession(): sessionManage
    {
        $session = sessionManage::query()->first();
        if ($session) {
            return $session;
        }

        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        return $session;
    }

    private function createAdmin(array $attributes = []): CultivationAdmin
    {
        $admin = new CultivationAdmin();
        $admin->adminName = $attributes['adminName'] ?? 'Admin '.uniqid();
        $admin->adminUser = $attributes['adminUser'] ?? 'user_'.uniqid();
        $admin->userType = array_key_exists('userType', $attributes)
            ? $attributes['userType']
            : CultivationAdmin::ROLE_GENERAL;
        $admin->loginPassword = Hash::make('secret123');
        $admin->adminMobile = $attributes['adminMobile'] ?? '017'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $admin->adminMail = $attributes['adminMail'] ?? uniqid('admin_', true).'@example.test';
        $admin->primary_class_id = $attributes['primary_class_id'] ?? null;
        $admin->primary_section_id = $attributes['primary_section_id'] ?? null;
        $admin->save();

        return $admin;
    }

    private function createSubject(string $name): Subject
    {
        $subject = new Subject();
        $subject->subjectName = $name;
        $subject->subjectType = 'Theory';
        $subject->save();

        return $subject;
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
}
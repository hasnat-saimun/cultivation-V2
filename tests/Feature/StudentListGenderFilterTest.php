<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentListGenderFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_ui_contains_gender_dropdown(): void
    {
        $admin = $this->createAdmin();
        $this->seedReferenceData();

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('adminModernStudentsIndex'));

        $response->assertOk();
        $response->assertSee('name="gender"', false);
        $response->assertSee('Gender');
        $response->assertSee('Male');
        $response->assertSee('Female');
        $response->assertSee('Others');
    }

    public function test_male_filter_returns_only_male_students(): void
    {
        [$admin, $scope] = $this->studentScope();
        $male = $this->createStudent('Male Student', '1', $scope);
        $this->createStudent('Female Student', '2', $scope);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('studentList', ['gender' => '1']));

        $response->assertOk();
        $response->assertSee($male->student_name);
        $response->assertDontSee('Female Student');
    }

    public function test_female_filter_returns_only_female_students(): void
    {
        [$admin, $scope] = $this->studentScope();
        $this->createStudent('Male Student', '1', $scope);
        $female = $this->createStudent('Female Student', '2', $scope);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('studentList', ['gender' => '2']));

        $response->assertOk();
        $response->assertSee($female->student_name);
        $response->assertDontSee('Male Student');
    }

    public function test_other_filter_returns_only_other_students(): void
    {
        [$admin, $scope] = $this->studentScope();
        $this->createStudent('Male Student', '1', $scope);
        $this->createStudent('Female Student', '2', $scope);
        $other = $this->createStudent('Other Student', '3', $scope);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('studentList', ['gender' => '3']));

        $response->assertOk();
        $response->assertSee($other->student_name);
        $response->assertDontSee('Male Student');
        $response->assertDontSee('Female Student');
    }

    public function test_gender_combines_correctly_with_class_filter(): void
    {
        [$admin, $scope] = $this->studentScope();
        $otherClass = $this->createClass('Class 9');

        $target = $this->createStudent('Class Match Male', '1', $scope);
        $this->createStudent('Same Class Female', '2', $scope);
        $this->createStudent('Other Class Male', '1', array_merge($scope, ['class' => $otherClass]));

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('studentList', ['gender' => '1', 'classId' => $scope['class']->id]));

        $response->assertOk();
        $response->assertSee($target->student_name);
        $response->assertDontSee('Same Class Female');
        $response->assertDontSee('Other Class Male');
    }

    public function test_gender_combines_correctly_with_section_filter(): void
    {
        [$admin, $scope] = $this->studentScope();
        $otherSection = $this->createSection('B');

        $target = $this->createStudent('Section Match Female', '2', $scope);
        $this->createStudent('Same Section Male', '1', $scope);
        $this->createStudent('Other Section Female', '2', array_merge($scope, ['section' => $otherSection]));

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('studentList', ['gender' => '2', 'sectionId' => $scope['section']->id]));

        $response->assertOk();
        $response->assertSee($target->student_name);
        $response->assertDontSee('Same Section Male');
        $response->assertDontSee('Other Section Female');
    }

    public function test_gender_combines_correctly_with_keyword_search(): void
    {
        [$admin, $scope] = $this->studentScope();
        $target = $this->createStudent('Amina Search', '2', $scope, '2001');
        $this->createStudent('Amin Search', '1', $scope, '2002');
        $this->createStudent('Other Name', '2', $scope, '2003');

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('studentList', ['gender' => '2', 'search' => 'Amina']));

        $response->assertOk();
        $response->assertSee($target->student_name);
        $response->assertDontSee('Amin Search');
        $response->assertDontSee('Other Name');
    }

    public function test_selected_gender_is_preserved_in_rendered_filter_and_export_links(): void
    {
        [$admin, $scope] = $this->studentScope();
        $this->createStudent('Preserved Female', '2', $scope);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('adminModernStudentsIndex', ['gender' => '2']));

        $response->assertOk();
        $response->assertSee('option value="2" selected', false);
        $response->assertSee('gender=2', false);
    }

    public function test_reset_clears_gender_filter(): void
    {
        $admin = $this->createAdmin();
        $this->seedReferenceData();

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('adminModernStudentsIndex', ['gender' => '1']));

        $response->assertOk();
        $response->assertSee('href="'.route('adminModernStudentsIndex').'"', false);
        $response->assertDontSee(route('adminModernStudentsIndex', ['gender' => '1']), false);
    }

    public function test_invalid_gender_parameter_is_handled_safely(): void
    {
        [$admin, $scope] = $this->studentScope();
        $male = $this->createStudent('Visible Male', '1', $scope);
        $female = $this->createStudent('Visible Female', '2', $scope);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('studentList', ['gender' => 'invalid']));

        $response->assertOk();
        $response->assertSee($male->student_name);
        $response->assertSee($female->student_name);
        $response->assertDontSee('option value="invalid" selected', false);
    }

    private function studentScope(): array
    {
        $admin = $this->createAdmin();
        $scope = $this->seedReferenceData();

        return [$admin, $scope];
    }

    private function seedReferenceData(): array
    {
        return [
            'session' => $this->createSession('2026'),
            'class' => $this->createClass('Class 8'),
            'section' => $this->createSection('A'),
            'department' => $this->createDepartment('Science'),
        ];
    }

    private function createAdmin(): CultivationAdmin
    {
        $admin = new CultivationAdmin();
        $admin->adminName = 'Student List Admin';
        $admin->adminUser = 'student_list_admin_'.uniqid();
        $admin->userType = CultivationAdmin::ROLE_GENERAL;
        $admin->loginPassword = Hash::make('secret123');
        $admin->adminMobile = '017'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $admin->adminMail = uniqid('student_list_admin_', true).'@example.test';
        $admin->save();

        return $admin;
    }

    private function createSession(string $name): sessionManage
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

    private function createStudent(string $name, string $gender, array $scope, string $studentId = null): newAdmission
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [$name];

        return newAdmission::create([
            'stdId' => $studentId ?? (string) random_int(1000, 9999),
            'fullName' => $parts[0] ?? $name,
            'sureName' => $parts[1] ?? 'Student',
            'father' => 'Father',
            'mother' => 'Mother',
            'gender' => $gender,
            'phone' => '017'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'address' => 'Test Address',
            'sessName' => $scope['session']->id,
            'className' => $scope['class']->id,
            'departmentName' => $scope['department']->id,
            'sectionName' => $scope['section']->id,
            'rollNumber' => (string) random_int(1, 99),
        ]);
    }
}
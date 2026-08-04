<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentFiltersAndIdCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_and_bulk_update_share_all_filters_and_bulk_receives_the_same_population(): void
    {
        [$admin, $scope] = $this->scope();
        $target = $this->student($scope, ['stdId' => 88001, 'fullName' => 'Shared', 'sureName' => 'Target', 'gender' => '2']);
        $this->student($scope, ['stdId' => 88002, 'fullName' => 'Excluded', 'sureName' => 'Male', 'gender' => '1']);

        $filters = ['sessionId' => $scope['session']->id, 'classId' => $scope['class']->id, 'sectionId' => $scope['section']->id, 'departmentId' => $scope['department']->id, 'gender' => '2', 'search' => 'Shared'];
        $list = $this->withSession(['cultivationAdmin' => $admin->id])->get(route('studentList', $filters));
        $bulk = $this->withSession(['cultivationAdmin' => $admin->id])->get(route('studentBulkUpdate', $filters));

        $list->assertOk()->assertSee($target->student_name)->assertDontSee('Excluded Male');
        $bulk->assertOk()->assertSee('value="Shared"', false)->assertSee('value="Target"', false)->assertDontSee('value="Excluded"', false);
        $list->assertSee('student/bulk-update?classId='.$scope['class']->id.'&amp;sessionId='.$scope['session']->id, false);
        $bulk->assertSee('student/list?sessionId='.$scope['session']->id, false);
        $list->assertViewHasAll(['filterOptions', 'filters', 'filterAction', 'filterResetUrl']);
        $bulk->assertViewHasAll(['filterOptions', 'filters', 'filterAction', 'filterResetUrl']);
        $this->assertInstanceOf(LengthAwarePaginator::class, $list->viewData('studentData'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $bulk->viewData('students'));
        $this->assertSame(route('studentList'), $list->viewData('filterAction'));
        $this->assertSame(route('studentBulkUpdate'), $bulk->viewData('filterAction'));
        foreach (['sessionId', 'classId', 'sectionId', 'departmentId', 'gender', 'search'] as $field) {
            $list->assertSee('name="'.$field.'"', false);
            $bulk->assertSee('name="'.$field.'"', false);
        }
    }

    public function test_shared_filter_partial_has_a_safe_display_only_default(): void
    {
        $html = view('cultivation.partials.student-filters')->render();

        $this->assertStringContainsString('<form method="GET"', $html);
        $this->assertStringContainsString('name="gender"', $html);
        $this->assertStringContainsString('name="search"', $html);
    }

    public function test_list_pagination_preserves_the_complete_filter_query(): void
    {
        [$admin, $scope] = $this->scope();
        foreach (range(1, 51) as $roll) $this->student($scope, ['stdId' => 89000 + $roll, 'rollNumber' => $roll, 'gender' => '2']);
        $filters = ['sessionId' => $scope['session']->id, 'classId' => $scope['class']->id, 'sectionId' => $scope['section']->id, 'departmentId' => $scope['department']->id, 'gender' => '2'];

        $response = $this->withSession(['cultivationAdmin' => $admin->id])->get(route('studentList', $filters));
        $response->assertOk()->assertSee('page=2', false)->assertSee('gender=2', false)->assertSee('sessionId='.$scope['session']->id, false);
        $paginator = $response->viewData('studentData');
        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertCount(50, $paginator->items());
        $pageOneIds = $paginator->getCollection()->pluck('id')->all();

        $pageTwo = $this->withSession(['cultivationAdmin' => $admin->id])->get(route('studentList', array_merge($filters, ['page' => 2])));
        $pageTwo->assertOk()->assertSee('gender=2', false)->assertSee('sessionId='.$scope['session']->id, false);
        $secondPaginator = $pageTwo->viewData('studentData');
        $this->assertInstanceOf(LengthAwarePaginator::class, $secondPaginator);
        $this->assertCount(1, $secondPaginator->items());
        $pageTwoIds = $secondPaginator->getCollection()->pluck('id')->all();
        $this->assertSame([], array_values(array_intersect($pageOneIds, $pageTwoIds)));
        $this->assertCount(51, array_unique(array_merge($pageOneIds, $pageTwoIds)));
    }

    public function test_single_id_card_and_pdf_use_one_complete_secure_payload(): void
    {
        [$admin, $scope] = $this->scope();
        $student = $this->student($scope, ['stdId' => 89991, 'fullName' => 'A Very Long Student Name That Must Wrap', 'sureName' => 'Safely', 'rollNumber' => 12, 'phone' => '01711111111', 'gurdianName' => 'Guardian Person', 'gurdianMobile' => '01822222222', 'relationGurdian' => 'Father']);

        $print = $this->withSession(['cultivationAdmin' => $admin->id])->get(route('stdIdCard', $student->id));
        $print->assertOk()->assertSee('A Very Long Student Name That Must Wrap Safely')->assertSee('89991')->assertSee('Guardian Person')->assertSee('01822222222')->assertSee($scope['session']->session)->assertSee('data:image/', false);
        $this->assertSame(1, substr_count($print->getContent(), 'aria-label="Student ID card front"'));

        $pdf = $this->withSession(['cultivationAdmin' => $admin->id])->get(route('stdIdCard.pdf', $student->id));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->withSession(['cultivationAdmin' => $admin->id])->get(route('stdIdCard', 999999))->assertNotFound();
        $this->flushSession();
        $this->get(route('stdIdCard', $student->id))->assertRedirect(route('adminLogin'));
    }

    private function scope(): array
    {
        $admin = new CultivationAdmin();
        $admin->adminName = 'Release Admin'; $admin->adminUser = 'release_'.uniqid(); $admin->userType = 3;
        $admin->loginPassword = Hash::make('secret'); $admin->adminMobile = '01700000001'; $admin->adminMail = uniqid().'@test.local'; $admin->save();
        $session = new sessionManage(); $session->session = '2026'; $session->save();
        $class = new classManage(); $class->className = 'Ten'; $class->save();
        $section = new sectionManage(); $section->section = 'A'; $section->save();
        $department = new Department(); $department->departmentName = 'Science'; $department->save();
        return [$admin, compact('session', 'class', 'section', 'department')];
    }

    private function student(array $scope, array $overrides): newAdmission
    {
        return newAdmission::create(array_merge(['stdId' => random_int(10000, 99999), 'fullName' => 'Student', 'sureName' => 'Name', 'father' => 'Father', 'mother' => 'Mother', 'gender' => '1', 'phone' => '01700000000', 'sessName' => $scope['session']->id, 'className' => $scope['class']->id, 'sectionName' => $scope['section']->id, 'departmentName' => $scope['department']->id, 'rollNumber' => '1'], $overrides));
    }
}

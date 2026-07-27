<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\CurriculumSubjectMapping;
use App\Models\Department;
use App\Models\newAdmission;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\CurriculumSubjectMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CurriculumMappingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_curriculum_mapping_for_scope(): void
    {
        $this->withoutMiddleware();

        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 10';
        $class->save();
        $section = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $department = new Department();
        $department->departmentName = 'Science';
        $department->save();

        $bangla = Subject::create(['subjectName' => 'Bangla', 'subjectType' => 'Main', 'CQ' => 100]);
        $physics = Subject::create(['subjectName' => 'Physics', 'subjectType' => 'Main', 'CQ' => 100]);

        $response = $this->post(route('saveResultCurriculumMapping'), [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $section,
            'departmentId' => $department->id,
            'subjectIds' => [$bangla->id, $physics->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('curriculum_subject_mappings', [
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $section,
            'department_id' => (string) $department->id,
            'subject_id' => $bangla->id,
            'mapping_type' => 'main',
        ]);

        $this->assertDatabaseHas('curriculum_subject_mappings', [
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $section,
            'department_id' => (string) $department->id,
            'subject_id' => $physics->id,
            'mapping_type' => 'main',
        ]);
    }

    public function test_non_department_class_forces_department_scope_to_all(): void
    {
        $this->withoutMiddleware();

        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 8';
        $class->save();
        $section = DB::table('section_manages')->insertGetId(['section' => 'B', 'created_at' => now(), 'updated_at' => now()]);
        $department = new Department();
        $department->departmentName = 'Science';
        $department->save();

        $english = Subject::create(['subjectName' => 'English', 'subjectType' => 'Main', 'CQ' => 100]);

        $response = $this->post(route('saveResultCurriculumMapping'), [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $section,
            'departmentId' => $department->id,
            'subjectIds' => [$english->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('curriculum_subject_mappings', [
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $section,
            'department_id' => null,
            'subject_id' => $english->id,
        ]);
    }

    public function test_copy_preview_and_copy_routes_work_for_curriculum_scope(): void
    {
        $this->withoutMiddleware();

        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 10';
        $class->save();
        $sourceSection = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $targetSection = DB::table('section_manages')->insertGetId(['section' => 'B', 'created_at' => now(), 'updated_at' => now()]);
        $department = new Department();
        $department->departmentName = 'Science';
        $department->save();

        $bangla = Subject::create(['subjectName' => 'Bangla', 'subjectType' => 'Main', 'CQ' => 100]);
        $physics = Subject::create(['subjectName' => 'Physics', 'subjectType' => 'Main', 'CQ' => 100]);

        DB::table('curriculum_subject_mappings')->insert([
            [
                'session_id' => (string) $session->id,
                'class_id' => (string) $class->id,
                'section_id' => (string) $sourceSection,
                'department_id' => (string) $department->id,
                'subject_id' => $bangla->id,
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
                'section_id' => (string) $sourceSection,
                'department_id' => (string) $department->id,
                'subject_id' => $physics->id,
                'mapping_type' => 'main',
                'sort_order' => 2,
                'is_active' => 1,
                'source' => 'test-fixture',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $preview = $this->postJson(route('previewResultCurriculumMappingCopy'), [
            'sourceSessionId' => $session->id,
            'sourceClassId' => $class->id,
            'sourceSectionId' => $sourceSection,
            'sourceDepartmentId' => $department->id,
            'targetSessionId' => $session->id,
            'targetClassId' => $class->id,
            'targetSectionId' => $targetSection,
            'targetDepartmentId' => $department->id,
        ]);

        $preview->assertOk()->assertJsonPath('count', 2);

        $copy = $this->post(route('copyResultCurriculumMapping'), [
            'sourceSessionId' => $session->id,
            'sourceClassId' => $class->id,
            'sourceSectionId' => $sourceSection,
            'sourceDepartmentId' => $department->id,
            'targetSessionId' => $session->id,
            'targetClassId' => $class->id,
            'targetSectionId' => $targetSection,
            'targetDepartmentId' => $department->id,
        ]);

        $copy->assertRedirect();

        $repeatCopy = $this->post(route('copyResultCurriculumMapping'), [
            'sourceSessionId' => $session->id,
            'sourceClassId' => $class->id,
            'sourceSectionId' => $sourceSection,
            'sourceDepartmentId' => $department->id,
            'targetSessionId' => $session->id,
            'targetClassId' => $class->id,
            'targetSectionId' => $targetSection,
            'targetDepartmentId' => $department->id,
        ]);

        $repeatCopy->assertRedirect();

        $this->assertDatabaseHas('curriculum_subject_mappings', [
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $targetSection,
            'department_id' => (string) $department->id,
            'subject_id' => $bangla->id,
            'mapping_type' => 'main',
        ]);

        $this->assertDatabaseHas('curriculum_subject_mappings', [
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $targetSection,
            'department_id' => (string) $department->id,
            'subject_id' => $physics->id,
            'mapping_type' => 'main',
        ]);

        $this->assertSame(2, CurriculumSubjectMapping::query()
            ->where('session_id', (string) $session->id)
            ->where('class_id', (string) $class->id)
            ->where('section_id', (string) $targetSection)
            ->where('department_id', (string) $department->id)
            ->where('mapping_type', 'main')
            ->count());
    }

    public function test_copy_form_renders_target_dropdowns_and_lookup_endpoints(): void
    {
        $this->withoutMiddleware();

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 10');
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $department = $this->makeDepartment('Science');

        $response = $this->get(route('resultCurriculumMappingManage', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $sectionId,
            'departmentId' => $department->id,
            'mappingType' => CurriculumSubjectMapping::TYPE_MAIN,
        ]));

        $response->assertOk();
        $response->assertSee('name="targetSessionId"', false);
        $response->assertSee('name="targetClassId"', false);
        $response->assertSee('name="targetSectionId"', false);
        $response->assertSee('name="targetDepartmentId"', false);

        $classes = $this->postJson(route('api.resultCurriculumMapping.classes'), [
            'sessionId' => $session->id,
        ]);
        $classes->assertOk()->assertJsonStructure(['classes' => [['id', 'name', 'requires_department']]]);

        $sections = $this->postJson(route('api.resultCurriculumMapping.sections'), [
            'sessionId' => $session->id,
            'classId' => $class->id,
        ]);
        $sections->assertOk()->assertJsonStructure(['sections' => [['id', 'name']]]);

        $departments = $this->postJson(route('api.resultCurriculumMapping.departments'), [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $sectionId,
        ]);
        $departments->assertOk()->assertJsonStructure(['departments' => [['id', 'name']]]);
    }

    public function test_section_all_uses_explicit_ui_state_contract(): void
    {
        $this->withoutMiddleware();

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 9');
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $department = $this->makeDepartment('Science');

        $response = $this->get(route('resultCurriculumMappingManage', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $sectionId,
            'departmentId' => $department->id,
            'mappingType' => CurriculumSubjectMapping::TYPE_MAIN,
        ]));

        $response->assertOk();
        $response->assertSee('value=""', false);
        $response->assertSee('All', false);
        $response->assertSee('const ALL_SECTION_VALUE = \'\';', false);
        $response->assertSee('function hasValidSectionScope(sectionValue)', false);
        $response->assertSee('syncDepartmentState(restoreSelection);', false);
        $response->assertSee('bindChange(sectionSelect, function () {', false);
        $response->assertSee('syncDepartmentState(false);', false);
    }

    public function test_class_nine_all_sections_supports_science_business_and_humanities_department_scopes(): void
    {
        $this->withoutMiddleware();

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 9');
        $science = $this->makeDepartment('Science');
        $business = $this->makeDepartment('Business');
        $humanities = $this->makeDepartment('Humanities');
        $subject = Subject::create(['subjectName' => 'Bangla', 'subjectType' => 'Main', 'CQ' => 100]);

        foreach ([$science, $business, $humanities] as $department) {
            $response = $this->post(route('saveResultCurriculumMapping'), [
                'sessionId' => $session->id,
                'classId' => $class->id,
                'sectionId' => null,
                'departmentId' => $department->id,
                'subjectIds' => [$subject->id],
            ]);

            $response->assertRedirect();
        }

        $this->assertDatabaseHas('curriculum_subject_mappings', [
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => null,
            'department_id' => (string) $science->id,
            'subject_id' => $subject->id,
        ]);

        $this->assertDatabaseHas('curriculum_subject_mappings', [
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => null,
            'department_id' => (string) $business->id,
            'subject_id' => $subject->id,
        ]);

        $this->assertDatabaseHas('curriculum_subject_mappings', [
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => null,
            'department_id' => (string) $humanities->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_reload_preserves_section_all_and_department_selection(): void
    {
        $this->withoutMiddleware();

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 9');
        $department = $this->makeDepartment('Science');
        $subject = Subject::create(['subjectName' => 'Bangla', 'subjectType' => 'Main', 'CQ' => 100]);

        CurriculumSubjectMapping::query()->create([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => null,
            'department_id' => (string) $department->id,
            'subject_id' => (int) $subject->id,
            'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
        ]);

        $response = $this->get(route('resultCurriculumMappingManage', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'departmentId' => $department->id,
            'mappingType' => CurriculumSubjectMapping::TYPE_MAIN,
        ]));

        $response->assertOk();
        $response->assertSee('Section:</strong> All Sections', false);
        $response->assertSee('Department:</strong> Science', false);
        $response->assertSee('name="departmentId"', false);
    }

    public function test_copy_mapping_rejects_identical_source_and_target_scope(): void
    {
        $this->withoutMiddleware();

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 10');
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $department = $this->makeDepartment('Science');

        $subject = Subject::create(['subjectName' => 'Bangla', 'subjectType' => 'Main', 'CQ' => 100]);

        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $sectionId,
            'department_id' => (string) $department->id,
            'subject_id' => $subject->id,
            'mapping_type' => 'main',
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('copyResultCurriculumMapping'), [
            'sourceSessionId' => $session->id,
            'sourceClassId' => $class->id,
            'sourceSectionId' => $sectionId,
            'sourceDepartmentId' => $department->id,
            'targetSessionId' => $session->id,
            'targetClassId' => $class->id,
            'targetSectionId' => $sectionId,
            'targetDepartmentId' => $department->id,
        ]);

        $response->assertSessionHasErrors([
            'targetSessionId' => 'Source and Target mapping cannot be identical.',
        ]);
    }

    public function test_existing_mappings_are_preloaded_with_active_and_inactive_state(): void
    {
        $this->withoutMiddleware();

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 10');
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $department = $this->makeDepartment('Science');

        $active = Subject::create(['subjectName' => 'Bangla', 'subjectType' => 'Main', 'CQ' => 100]);
        $inactive = Subject::create(['subjectName' => 'Biology', 'subjectType' => 'Main', 'CQ' => 100]);

        CurriculumSubjectMapping::query()->create([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $sectionId,
            'department_id' => (string) $department->id,
            'subject_id' => (int) $active->id,
            'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
        ]);
        CurriculumSubjectMapping::query()->create([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $sectionId,
            'department_id' => (string) $department->id,
            'subject_id' => (int) $inactive->id,
            'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
            'sort_order' => 2,
            'is_active' => 0,
            'source' => 'test-fixture',
        ]);

        $response = $this->get(route('resultCurriculumMappingManage', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $sectionId,
            'departmentId' => $department->id,
            'mappingType' => CurriculumSubjectMapping::TYPE_MAIN,
        ]));

        $response->assertOk();
        $response->assertSee('Mapped, Active');
        $response->assertSee('Mapped, Inactive');
        $response->assertSee('data-subject-id="'.$active->id.'"', false);
        $response->assertSee('data-subject-id="'.$inactive->id.'"', false);
        $response->assertSee('Mapped subjects:</strong> 2', false);
        $response->assertSee('Active subjects:</strong> 1', false);
        $response->assertSee('name="sortOrders['.$active->id.']" value="1"', false);
        $response->assertSee('name="sortOrders['.$inactive->id.']" value="2"', false);
    }

    public function test_idempotent_resubmission_keeps_single_row_and_reports_unchanged(): void
    {
        $this->withoutMiddleware();

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 8');
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'B', 'created_at' => now(), 'updated_at' => now()]);
        $subject = Subject::create(['subjectName' => 'English', 'subjectType' => 'Main', 'CQ' => 100]);

        $payload = [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $sectionId,
            'departmentId' => null,
            'mappingType' => CurriculumSubjectMapping::TYPE_MAIN,
            'subjectIds' => [$subject->id],
        ];

        $first = $this->post(route('saveResultCurriculumMapping'), $payload);
        $first->assertRedirect();

        $second = $this->post(route('saveResultCurriculumMapping'), $payload);
        $second->assertRedirect();
        $second->assertSessionHas('success', fn ($message) => is_string($message) && str_contains($message, 'already mapped'));

        $this->assertSame(1, CurriculumSubjectMapping::query()
            ->where('session_id', (string) $session->id)
            ->where('class_id', (string) $class->id)
            ->where('section_id', (string) $sectionId)
            ->whereNull('department_id')
            ->where('subject_id', (int) $subject->id)
            ->count());
    }

    public function test_exact_scope_isolation_and_common_scope_rules_are_enforced(): void
    {
        $session = $this->makeSession('2026');
        $otherSession = $this->makeSession('2027');
        $class = $this->makeClass('Class 10');
        $otherClass = $this->makeClass('Class 9');
        $sectionA = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $sectionB = DB::table('section_manages')->insertGetId(['section' => 'B', 'created_at' => now(), 'updated_at' => now()]);
        $science = $this->makeDepartment('Science');
        $commerce = $this->makeDepartment('Commerce');

        $commonSubject = Subject::create(['subjectName' => 'Bangla', 'subjectType' => 'Main', 'CQ' => 100]);
        $scienceOnly = Subject::create(['subjectName' => 'Physics', 'subjectType' => 'Main', 'CQ' => 100]);
        $otherClassSubject = Subject::create(['subjectName' => 'History', 'subjectType' => 'Main', 'CQ' => 100]);
        $otherSessionSubject = Subject::create(['subjectName' => 'Civics', 'subjectType' => 'Main', 'CQ' => 100]);

        CurriculumSubjectMapping::query()->create([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => null,
            'department_id' => null,
            'subject_id' => (int) $commonSubject->id,
            'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
        ]);
        CurriculumSubjectMapping::query()->create([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => null,
            'department_id' => (string) $science->id,
            'subject_id' => (int) $scienceOnly->id,
            'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
            'sort_order' => 2,
            'is_active' => 1,
            'source' => 'test-fixture',
        ]);
        CurriculumSubjectMapping::query()->create([
            'session_id' => (string) $session->id,
            'class_id' => (string) $otherClass->id,
            'section_id' => null,
            'department_id' => null,
            'subject_id' => (int) $otherClassSubject->id,
            'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
        ]);
        CurriculumSubjectMapping::query()->create([
            'session_id' => (string) $otherSession->id,
            'class_id' => (string) $class->id,
            'section_id' => null,
            'department_id' => null,
            'subject_id' => (int) $otherSessionSubject->id,
            'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
        ]);

        $scienceStudent = newAdmission::create([
            'stdId' => '10001',
            'fullName' => 'Science Student',
            'sessName' => $session->id,
            'className' => $class->id,
            'sectionName' => $sectionA,
            'departmentName' => $science->id,
            'rollNumber' => '01',
        ]);
        $commerceStudent = newAdmission::create([
            'stdId' => '10002',
            'fullName' => 'Commerce Student',
            'sessName' => $session->id,
            'className' => $class->id,
            'sectionName' => $sectionB,
            'departmentName' => $commerce->id,
            'rollNumber' => '02',
        ]);

        $service = app(CurriculumSubjectMappingService::class);
        $scienceIds = $service->mappedMainSubjectsForStudent($scienceStudent, true)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $commerceIds = $service->mappedMainSubjectsForStudent($commerceStudent, true)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains((int) $commonSubject->id, $scienceIds);
        $this->assertContains((int) $commonSubject->id, $commerceIds);
        $this->assertContains((int) $scienceOnly->id, $scienceIds);
        $this->assertNotContains((int) $scienceOnly->id, $commerceIds);
        $this->assertNotContains((int) $otherClassSubject->id, $scienceIds);
        $this->assertNotContains((int) $otherSessionSubject->id, $scienceIds);
    }

    public function test_inactive_mapping_is_not_selected_and_reselecting_reactivates_it(): void
    {
        $this->withoutMiddleware();

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 8');
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'C', 'created_at' => now(), 'updated_at' => now()]);
        $subject = Subject::create(['subjectName' => 'Mathematics', 'subjectType' => 'Main', 'CQ' => 100]);

        CurriculumSubjectMapping::query()->create([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $sectionId,
            'department_id' => null,
            'subject_id' => (int) $subject->id,
            'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
            'sort_order' => 1,
            'is_active' => 0,
            'source' => 'test-fixture',
        ]);

        $response = $this->post(route('saveResultCurriculumMapping'), [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $sectionId,
            'departmentId' => null,
            'mappingType' => CurriculumSubjectMapping::TYPE_MAIN,
            'subjectIds' => [$subject->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('curriculum_subject_mappings', [
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $sectionId,
            'department_id' => null,
            'subject_id' => (int) $subject->id,
            'is_active' => 1,
        ]);
    }

    public function test_explicit_sort_order_is_saved_and_reloads_in_same_order_inputs(): void
    {
        $this->withoutMiddleware();

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 10');
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $department = $this->makeDepartment('Science');

        $bangla = Subject::create(['subjectName' => 'Bangla 1st Paper', 'subjectType' => 'Main', 'CQ' => 100]);
        $english = Subject::create(['subjectName' => 'English 1st Paper', 'subjectType' => 'Main', 'CQ' => 100]);
        $math = Subject::create(['subjectName' => 'Mathematics', 'subjectType' => 'Main', 'CQ' => 100]);

        $response = $this->post(route('saveResultCurriculumMapping'), [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $sectionId,
            'departmentId' => $department->id,
            'mappingType' => CurriculumSubjectMapping::TYPE_MAIN,
            'subjectIds' => [$math->id, $bangla->id, $english->id],
            'sortOrders' => [
                $math->id => 50,
                $bangla->id => 10,
                $english->id => 30,
            ],
        ]);

        $response->assertRedirect();

        $rows = CurriculumSubjectMapping::query()
            ->where('session_id', (string) $session->id)
            ->where('class_id', (string) $class->id)
            ->where('section_id', (string) $sectionId)
            ->where('department_id', (string) $department->id)
            ->where('mapping_type', CurriculumSubjectMapping::TYPE_MAIN)
            ->orderBy('sort_order')
            ->get(['subject_id', 'sort_order']);

        $this->assertSame([$bangla->id, $english->id, $math->id], $rows->pluck('subject_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([10, 30, 50], $rows->pluck('sort_order')->map(fn ($order) => (int) $order)->all());

        $load = $this->get(route('resultCurriculumMappingManage', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $sectionId,
            'departmentId' => $department->id,
            'mappingType' => CurriculumSubjectMapping::TYPE_MAIN,
        ]));

        $load->assertOk();
        $load->assertSee('name="sortOrders['.$bangla->id.']" value="10"', false);
        $load->assertSee('name="sortOrders['.$english->id.']" value="30"', false);
        $load->assertSee('name="sortOrders['.$math->id.']" value="50"', false);
    }

    public function test_duplicate_key_is_converted_to_friendly_validation_style_message(): void
    {
        $this->withoutMiddleware();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Duplicate key translation depends on MySQL unique-key error code 1062.');
        }

        $session = $this->makeSession('2026');
        $class = $this->makeClass('Class 8');
        $sectionId = DB::table('section_manages')->insertGetId(['section' => 'D', 'created_at' => now(), 'updated_at' => now()]);
        $subject = Subject::create(['subjectName' => 'ICT', 'subjectType' => 'Main', 'CQ' => 100]);

        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $sectionId,
            'department_id' => null,
            'subject_id' => (int) $subject->id,
            'mapping_type' => 'legacy',
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            DB::table('curriculum_subject_mappings')->insert([
                'session_id' => (string) $session->id,
                'class_id' => (string) $class->id,
                'section_id' => (string) $sectionId,
                'department_id' => null,
                'subject_id' => (int) $subject->id,
                'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
                'sort_order' => 2,
                'is_active' => 1,
                'source' => 'test-fixture',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->markTestSkipped('Current test DB does not enforce duplicate key conflict for this contract.');
        } catch (\Illuminate\Database\QueryException $ignored) {
            // Duplicate contract enforced in this database; continue.
        }

        $response = $this->post(route('saveResultCurriculumMapping'), [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $sectionId,
            'departmentId' => null,
            'mappingType' => CurriculumSubjectMapping::TYPE_MAIN,
            'subjectIds' => [$subject->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn ($message) => is_string($message) && str_contains($message, 'already mapped'));
    }

    private function makeSession(string $name): sessionManage
    {
        $session = new sessionManage();
        $session->session = $name;
        $session->save();

        return $session;
    }

    private function makeClass(string $name): classManage
    {
        $class = new classManage();
        $class->className = $name;
        $class->save();

        return $class;
    }

    private function makeDepartment(string $name): Department
    {
        $department = new Department();
        $department->departmentName = $name;
        $department->save();

        return $department;
    }
}

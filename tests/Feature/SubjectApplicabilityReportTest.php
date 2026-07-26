<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\Department;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\AcademicSubjectApplicabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubjectApplicabilityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_student_receives_common_and_own_department_subjects_only(): void
    {
        $scope = $this->scope('Class 9');
        $science = $this->department('Science');
        $humanities = $this->department('Humanities');
        $common = $this->subject('Common Bangla', $scope['class']->id);
        $physics = $this->subject('Physics', $scope['class']->id);
        $geography = $this->subject('Geography', $scope['class']->id);
        $this->mapping($scope, null, $common, 1);
        $this->mapping($scope, $science->id, $physics, 2);
        $this->mapping($scope, $humanities->id, $geography, 2);

        $student = $this->student($scope, $science->id);
        $ids = app(AcademicSubjectApplicabilityService::class)->subjectsForStudent($student)->pluck('id')->all();

        $this->assertSame([$common->id, $physics->id], $ids);
        $this->assertNotContains($geography->id, $ids);
    }

    public function test_non_group_class_ignores_department_mapping_and_other_session_or_class_subjects(): void
    {
        $scope = $this->scope('Class 8');
        $science = $this->department('Science');
        $common = $this->subject('Class Eight Common', $scope['class']->id);
        $invalidGroup = $this->subject('Invalid Group Subject', $scope['class']->id);
        $otherClass = $this->subject('Other Class Subject', $scope['class']->id + 100);
        $this->mapping($scope, null, $common, 1);
        $this->mapping($scope, $science->id, $invalidGroup, 2);
        $otherSession = $this->scope('Class 8');
        $this->mapping($otherSession, null, $otherClass, 1);

        $ids = app(AcademicSubjectApplicabilityService::class)
            ->subjectsForStudent($this->student($scope, null))->pluck('id')->all();

        $this->assertSame([$common->id], $ids);
        $this->assertNotContains($invalidGroup->id, $ids);
        $this->assertNotContains($otherClass->id, $ids);
    }

    public function test_religion_and_fourth_subjects_are_student_specific_without_name_guessing(): void
    {
        $scope = $this->scope('Class 9');
        $science = $this->department('Science');
        $common = $this->subject('Common', $scope['class']->id);
        $islam = $this->subject('Religion A', $scope['class']->id, 'Main', true);
        $hindu = $this->subject('Religion B', $scope['class']->id, 'Main', true);
        $optionalA = $this->subject('Optional A', $scope['class']->id, 'Optional');
        $optionalB = $this->subject('Optional B', $scope['class']->id, 'Optional');
        $this->mapping($scope, null, $common, 1);

        $first = $this->student($scope, $science->id, $islam->id, $optionalA->id);
        $second = $this->student($scope, $science->id, $hindu->id, $optionalB->id);
        $sets = app(AcademicSubjectApplicabilityService::class)->subjectsForStudents([$first, $second]);

        $this->assertSame([$common->id, $islam->id, $optionalA->id], $sets[$first->id]->pluck('id')->all());
        $this->assertSame([$common->id, $hindu->id, $optionalB->id], $sets[$second->id]->pluck('id')->all());
    }

    public function test_batched_applicability_query_count_is_constant_as_student_count_grows(): void
    {
        $scope = $this->scope('Class 9');
        $science = $this->department('Science');
        $subject = $this->subject('Bounded Common', $scope['class']->id);
        $this->mapping($scope, null, $subject, 1);
        $students = collect(range(1, 30))->map(fn () => $this->student($scope, $science->id));

        DB::flushQueryLog();
        DB::enableQueryLog();
        $sets = app(AcademicSubjectApplicabilityService::class)->subjectsForStudents($students);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(30, $sets);
        $this->assertLessThanOrEqual(8, $count);
    }

    public function test_mixed_scope_per_subject_precedence_merges_section_common_and_class_department_rows(): void
    {
        $scope = $this->scope('Class 9');
        $science = $this->department('Science');
        $business = $this->department('Business');
        $humanities = $this->department('Humanities');

        $bangla = $this->subject('Bangla Common', $scope['class']->id);
        $math = $this->subject('Mathematics Common', $scope['class']->id);
        $physics = $this->subject('Physics', $scope['class']->id);
        $chemistry = $this->subject('Chemistry', $scope['class']->id);
        $biology = $this->subject('Biology', $scope['class']->id);
        $legacyHistoryCommon = $this->subject('Legacy History Common', $scope['class']->id);
        $accounting = $this->subject('Accounting', $scope['class']->id);
        $history = $this->subject('History', $scope['class']->id);

        // Class-wide common baseline.
        $this->mappingWithScope($scope, null, null, $bangla, 100);
        $this->mappingWithScope($scope, null, null, $math, 200);
        $this->mappingWithScope($scope, null, null, $legacyHistoryCommon, 5);

        // Section-specific common rows must win for duplicate subjects.
        $this->mappingWithScope($scope, (int) $scope['section']->id, null, $bangla, 10);
        $this->mappingWithScope($scope, (int) $scope['section']->id, null, $math, 20);

        // Science is class-wide + exact department and must remain included.
        $this->mappingWithScope($scope, null, (int) $science->id, $physics, 30);
        $this->mappingWithScope($scope, null, (int) $science->id, $chemistry, 40);
        $this->mappingWithScope($scope, null, (int) $science->id, $biology, 50);

        // Other departments must never leak.
        $this->mappingWithScope($scope, null, (int) $business->id, $accounting, 60);
        $this->mappingWithScope($scope, null, (int) $humanities->id, $history, 70);

        $student = $this->student($scope, (int) $science->id);
        $subjects = app(AcademicSubjectApplicabilityService::class)->subjectsForStudent($student);
        $ids = $subjects->pluck('id')->all();

        $this->assertSame([
            $bangla->id,
            $math->id,
            $physics->id,
            $chemistry->id,
            $biology->id,
        ], $ids);

        $this->assertNotContains($accounting->id, $ids);
        $this->assertNotContains($history->id, $ids);
        $this->assertNotContains($legacyHistoryCommon->id, $ids);

        $this->assertSame(10, (int) $subjects->firstWhere('id', $bangla->id)?->applicability_order);
        $this->assertSame(20, (int) $subjects->firstWhere('id', $math->id)?->applicability_order);
        $this->assertSame(30, (int) $subjects->firstWhere('id', $physics->id)?->applicability_order);
    }

    public function test_class_wide_common_is_used_when_exact_section_common_is_absent(): void
    {
        $scope = $this->scope('Class 9');
        $science = $this->department('Science');

        $ict = $this->subject('ICT Common', $scope['class']->id);
        $physics = $this->subject('Physics', $scope['class']->id);

        // No exact-section common row for ICT, so class-wide common must be selected.
        $this->mappingWithScope($scope, null, null, $ict, 25, true);
        // Class-wide exact department remains valid for group-specific subjects.
        $this->mappingWithScope($scope, null, (int) $science->id, $physics, 30, true);

        $student = $this->student($scope, (int) $science->id);
        $subjects = app(AcademicSubjectApplicabilityService::class)->subjectsForStudent($student);

        $this->assertSame([$ict->id, $physics->id], $subjects->pluck('id')->all());
        $this->assertSame(25, (int) $subjects->firstWhere('id', $ict->id)?->applicability_order);
        $this->assertSame(30, (int) $subjects->firstWhere('id', $physics->id)?->applicability_order);
    }

    public function test_inactive_higher_specificity_does_not_suppress_active_lower_scope(): void
    {
        $scope = $this->scope('Class 9');
        $science = $this->department('Science');
        $physics = $this->subject('Physics', $scope['class']->id);

        // Higher specificity exists but is inactive.
        $this->mappingWithScope($scope, (int) $scope['section']->id, (int) $science->id, $physics, 10, false);
        // Active lower-precedence fallback must be selected.
        $this->mappingWithScope($scope, null, (int) $science->id, $physics, 30, true);

        $student = $this->student($scope, (int) $science->id);
        $subjects = app(AcademicSubjectApplicabilityService::class)->subjectsForStudent($student);

        $this->assertSame([$physics->id], $subjects->pluck('id')->all());
        $this->assertSame(30, (int) $subjects->first()?->applicability_order);
    }

    private function scope(string $className): array
    {
        $session = new sessionManage(); $session->session = 'S-'.uniqid(); $session->save();
        $class = new classManage(); $class->className = $className; $class->save();
        $section = new sectionManage(); $section->section = 'A'; $section->save();
        return compact('session', 'class', 'section');
    }

    private function department(string $name): Department
    {
        $department = new Department(); $department->departmentName = $name; $department->save();
        return $department;
    }

    private function subject(string $name, int $classId, string $type = 'Main', bool $religious = false): Subject
    {
        return Subject::create([
            'subjectName' => $name, 'subjectType' => $type, 'assign_class' => (string) $classId,
            'isReligious' => $religious, 'CQ' => 100,
        ]);
    }

    private function mapping(array $scope, ?int $departmentId, Subject $subject, int $order): void
    {
        $this->mappingWithScope($scope, (int) $scope['section']->id, $departmentId, $subject, $order, true);
    }

    private function mappingWithScope(
        array $scope,
        ?int $sectionId,
        ?int $departmentId,
        Subject $subject,
        int $order,
        bool $isActive = true
    ): void {
        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $scope['session']->id,
            'class_id' => (string) $scope['class']->id,
            'section_id' => $sectionId === null ? null : (string) $sectionId,
            'department_id' => $departmentId === null ? null : (string) $departmentId,
            'subject_id' => $subject->id,
            'mapping_type' => 'main',
            'sort_order' => $order,
            'is_active' => $isActive ? 1 : 0,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function student(
        array $scope,
        ?int $departmentId,
        ?int $religiousSubjectId = null,
        ?int $fourthSubjectId = null
    ): newAdmission {
        $student = new newAdmission();
        $student->stdId = random_int(100000, 99999999);
        $student->fullName = 'Applicability Student';
        $student->sessName = $scope['session']->id;
        $student->className = $scope['class']->id;
        $student->sectionName = $scope['section']->id;
        $student->departmentName = $departmentId;
        $student->religiousSubjectId = $religiousSubjectId;
        $student->fourthSubjectId = $fourthSubjectId;
        $student->save();
        $student->setRelation('marksheet', collect());
        return $student;
    }
}

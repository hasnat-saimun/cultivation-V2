<?php

namespace Tests\Feature;

use App\Http\Controllers\CultivationController;
use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\sessionManage;
use App\Models\sectionManage;
use App\Models\Subject;
use App\Models\TeacherClassSubject;
use App\Services\TeacherSubjectAssignmentAvailabilityService;
use App\Services\MarksEntryAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_assignment_defaults_gender_scope_to_all(): void
    {
        $class = $this->createClass('TA Class');
        $section = $this->createSection('A');
        $subject = $this->createSubject('TA Subject');

        $payload = $this->teacherPayload([
            'userName' => 'ta-default-user',
            'userMail' => 'ta-default-user@example.test',
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [''],
            'subject' => [$subject->id],
        ]);

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));

        $teacherId = CultivationAdmin::where('adminUser', 'ta-default-user')->value('id');
        $this->assertNotNull($teacherId);

        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'subject_id' => $subject->id,
            'gender_scope' => 'all',
        ]);
    }

    public function test_teacher_save_rejects_empty_assignment_rows(): void
    {
        try {
            app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
                'userName' => 'ta-empty-assignment',
                'userMail' => 'ta-empty-assignment@example.test',
                'className' => [],
                'section' => [],
                'optionalGroup' => [],
                'departmentScope' => [],
                'genderScope' => [],
                'subject' => [],
            ])));
            $this->fail('Expected empty teacher assignment payload to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subject', $exception->errors());
        }
    }

    public function test_assignment_subject_validation_uses_assignment_class_not_primary_class(): void
    {
        $assignmentClass = $this->createClass('Class 10');
        $primaryClass = $this->createClass('Class 7');
        $section = $this->createSection('A');
        $subject = $this->createSubject('Agricultural Studies 134');
        $subject->assign_class = (string) $assignmentClass->id;
        $subject->save();

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'ta-primary-isolation',
            'userMail' => 'ta-primary-isolation@example.test',
            'primaryClass' => $primaryClass->id,
            'primarySection' => $section->id,
            'className' => [$assignmentClass->id],
            'section' => [$section->id],
            'optionalGroup' => [''],
            'departmentScope' => ['all'],
            'subject' => [$subject->id],
        ])));

        $teacherId = CultivationAdmin::where('adminUser', 'ta-primary-isolation')->value('id');
        $this->assertNotNull($teacherId);
        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'class_id' => $assignmentClass->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_assignment_availability_excludes_subjects_outside_selected_class(): void
    {
        $classTen = $this->createClass('Class 10');
        $classSeven = $this->createClass('Class 7');
        $section = $this->createSection('Availability A');
        $session = $this->ensureSession();

        $validSubject = $this->createSubject('Class 10 Subject');
        $validSubject->assign_class = (string) $classTen->id;
        $validSubject->save();

        $invalidSubject = $this->createSubject('Class 7 Subject');
        $invalidSubject->assign_class = (string) $classSeven->id;
        $invalidSubject->save();

        $response = app(CultivationController::class)->assignmentAvailability(Request::create('/api/teacher/assignment-availability', 'POST', [
            'sessionId' => $session->id,
            'classId' => $classTen->id,
            'sectionId' => $section->id,
            'optionalGroupId' => '',
            'departmentScope' => 'all',
            'genderScope' => 'all',
        ]));

        $subjects = collect($response->getData(true)['subjects'] ?? []);
        $this->assertTrue($subjects->contains('id', $validSubject->id));
        $this->assertFalse($subjects->contains('id', $invalidSubject->id));
    }

    public function test_assignment_availability_uses_curriculum_mapping_when_assign_class_is_empty(): void
    {
        $session = $this->ensureSession();
        $class = $this->createClass('Class 10');
        $section = $this->createSection('A');
        $science = $this->createDepartment('Science');
        $business = $this->createDepartment('Business Studies');

        $common = $this->createSubject('Mapped Common');
        $common->assign_class = '';
        $common->save();

        $scienceOnly = $this->createSubject('Mapped Science');
        $scienceOnly->assign_class = '';
        $scienceOnly->save();

        $businessOnly = $this->createSubject('Mapped Business');
        $businessOnly->assign_class = '';
        $businessOnly->save();

        $this->insertMapping($session->id, $class->id, $section->id, null, $common->id, 1);
        $this->insertMapping($session->id, $class->id, $section->id, $science->id, $scienceOnly->id, 2);
        $this->insertMapping($session->id, $class->id, $section->id, $business->id, $businessOnly->id, 3);

        $availability = app(CultivationController::class)->assignmentAvailability(Request::create('/api/teacher/assignment-availability', 'POST', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $section->id,
            'optionalGroupId' => $science->id,
            'departmentScope' => 'specific',
            'genderScope' => 'all',
        ]));

        $subjects = collect($availability->getData(true)['subjects'] ?? []);
        $this->assertTrue($subjects->contains('id', $common->id));
        $this->assertTrue($subjects->contains('id', $scienceOnly->id));
        $this->assertFalse($subjects->contains('id', $businessOnly->id));

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'ta-mapped-empty-assign-class',
            'userMail' => 'ta-mapped-empty-assign-class@example.test',
            'assignmentSessionId' => $session->id,
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [$science->id],
            'departmentScope' => ['specific'],
            'genderScope' => ['all'],
            'subject' => [$scienceOnly->id],
        ])));

        $teacherId = CultivationAdmin::where('adminUser', 'ta-mapped-empty-assign-class')->value('id');
        $this->assertNotNull($teacherId);
        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'group_id' => $science->id,
            'subject_id' => $scienceOnly->id,
        ]);
    }

    public function test_assignment_save_rejects_cross_department_subject_when_unmapped_for_selected_scope(): void
    {
        $session = $this->ensureSession();
        $class = $this->createClass('Class 10');
        $section = $this->createSection('B');
        $science = $this->createDepartment('Science');
        $business = $this->createDepartment('Business Studies');

        $scienceSubject = $this->createSubject('Scope Science');
        $scienceSubject->assign_class = '';
        $scienceSubject->save();

        $businessSubject = $this->createSubject('Scope Business');
        $businessSubject->assign_class = '';
        $businessSubject->save();

        $this->insertMapping($session->id, $class->id, $section->id, $science->id, $scienceSubject->id, 1);
        $this->insertMapping($session->id, $class->id, $section->id, $business->id, $businessSubject->id, 2);

        try {
            app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
                'userName' => 'ta-cross-department-reject',
                'userMail' => 'ta-cross-department-reject@example.test',
                'assignmentSessionId' => $session->id,
                'className' => [$class->id],
                'section' => [$section->id],
                'optionalGroup' => [$science->id],
                'departmentScope' => ['specific'],
                'genderScope' => ['all'],
                'subject' => [$businessSubject->id],
            ])));
            $this->fail('Expected cross-department mapped subject to be rejected for selected scope.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subject', $exception->errors());
        }
    }

    public function test_gender_scope_male_and_female_are_stored(): void
    {
        $class = $this->createClass('TA Gender Class');
        $section = $this->createSection('B');
        $maleSubject = $this->createSubject('TA Male Subject');
        $femaleSubject = $this->createSubject('TA Female Subject');

        $payload = $this->teacherPayload([
            'userName' => 'ta-gender-user',
            'userMail' => 'ta-gender-user@example.test',
            'className' => [$class->id, $class->id],
            'section' => [$section->id, $section->id],
            'optionalGroup' => ['', ''],
            'genderScope' => ['male', 'female'],
            'subject' => [$maleSubject->id, $femaleSubject->id],
        ]);

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));

        $teacherId = CultivationAdmin::where('adminUser', 'ta-gender-user')->value('id');
        $this->assertNotNull($teacherId);

        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'subject_id' => $maleSubject->id,
            'gender_scope' => 'male',
        ]);
        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'subject_id' => $femaleSubject->id,
            'gender_scope' => 'female',
        ]);
    }

    public function test_blank_or_null_gender_scope_label_falls_back_to_all(): void
    {
        $row = new TeacherClassSubject();

        $row->gender_scope = null;
        $this->assertSame('All', $row->gender_scope_label);

        $row->gender_scope = '';
        $this->assertSame('All', $row->gender_scope_label);
    }

    public function test_existing_male_allows_only_female_for_same_context(): void
    {
        $session = $this->ensureSession();
        $class = $this->createClass('TA Context Class');
        $section = $this->createSection('C');
        $subject = $this->createSubject('TA Context Subject');

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'ta-male-user',
            'userMail' => 'ta-male-user@example.test',
            'assignmentSessionId' => $session->id,
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [''],
            'genderScope' => ['male'],
            'subject' => [$subject->id],
        ])));

        try {
            app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
                'userName' => 'ta-male-dup',
                'userMail' => 'ta-male-dup@example.test',
                'assignmentSessionId' => $session->id,
                'className' => [$class->id],
                'section' => [$section->id],
                'optionalGroup' => [''],
                'genderScope' => ['male'],
                'subject' => [$subject->id],
            ])));

            $this->fail('Expected male overlap to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('genderScope', $exception->errors());
        }

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'ta-female-ok',
            'userMail' => 'ta-female-ok@example.test',
            'assignmentSessionId' => $session->id,
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [''],
            'genderScope' => ['female'],
            'subject' => [$subject->id],
        ])));

        $this->assertDatabaseHas('cultivation_admins', ['adminUser' => 'ta-female-ok']);
    }

    public function test_same_gender_scope_can_be_assigned_in_different_session(): void
    {
        $sessionOne = $this->createSession('2026');
        $sessionTwo = $this->createSession('2027');
        $class = $this->createClass('TA Session Class');
        $section = $this->createSection('D');
        $subject = $this->createSubject('TA Session Subject');

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'ta-s1',
            'userMail' => 'ta-s1@example.test',
            'assignmentSessionId' => $sessionOne->id,
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [''],
            'genderScope' => ['male'],
            'subject' => [$subject->id],
        ])));

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'ta-s2',
            'userMail' => 'ta-s2@example.test',
            'assignmentSessionId' => $sessionTwo->id,
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [''],
            'genderScope' => ['male'],
            'subject' => [$subject->id],
        ])));

        $this->assertDatabaseHas('cultivation_admins', ['adminUser' => 'ta-s2']);
    }

    public function test_group_enabled_class_requires_valid_group_and_non_group_class_rejects_one(): void
    {
        $section = $this->createSection('Group Rule Section');
        $subject = $this->createSubject('Group Rule Subject');
        $department = $this->createDepartment('Science');

        foreach ([
            [$this->createClass('Class 10'), '', null, 'departmentScope'],
            [$this->createClass('Class 8'), $department->id, 'specific', 'departmentScope'],
        ] as [$class, $group, $scope, $errorKey]) {
            try {
                app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
                    'userName' => 'group-rule-'.$class->id,
                    'userMail' => 'group-rule-'.$class->id.'@example.test',
                    'className' => [$class->id],
                    'section' => [$section->id],
                    'optionalGroup' => [$group],
                    'departmentScope' => [$scope],
                    'subject' => [$subject->id],
                ])));
                $this->fail('Expected invalid group applicability to be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($errorKey, $exception->errors());
            }
        }

        $groupClass = classManage::where('className', 'Class 10')->firstOrFail();
        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'group-rule-valid',
            'userMail' => 'group-rule-valid@example.test',
            'className' => [$groupClass->id],
            'section' => [$section->id],
            'optionalGroup' => [$department->id],
            'departmentScope' => ['specific'],
            'subject' => [$subject->id],
        ])));

        $this->assertDatabaseHas('teacher_class_subjects', [
            'class_id' => $groupClass->id,
            'group_id' => $department->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_all_departments_is_explicitly_stored_as_null_and_authorizes_each_concrete_department_only_in_exact_session(): void
    {
        $session = $this->createSession('All Departments 2026');
        $otherSession = $this->createSession('All Departments 2027');
        $class = $this->createClass('Class 9');
        $section = $this->createSection('All Departments A');
        $subject = $this->createSubject('All Departments Bangla');
        $science = $this->createDepartment('Science');
        $business = $this->createDepartment('Business Studies');

        $teacher = $this->saveAssignment('all-departments-teacher', compact('session', 'class', 'section', 'subject'), 'all');
        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacher->id, 'session_id' => $session->id, 'class_id' => $class->id,
            'section_id' => $section->id, 'group_id' => null, 'subject_id' => $subject->id,
        ]);

        $authorization = app(MarksEntryAuthorizationService::class);
        $this->assertTrue($authorization->canEnterMarksFor($teacher, $class->id, $subject->id, $section->id, $science->id, $session->id));
        $this->assertTrue($authorization->canEnterMarksFor($teacher, $class->id, $subject->id, $section->id, $business->id, $session->id));
        $this->assertFalse($authorization->canEnterMarksFor($teacher, $class->id, $subject->id, $section->id, $science->id, $otherSession->id));
        $this->assertFalse($authorization->canEnterMarksFor($teacher, $class->id, $subject->id, $section->id, 999999, $session->id));
    }

    public function test_all_departments_and_concrete_department_collisions_intersect_with_gender_coverage(): void
    {
        $scope = [
            'session' => $this->createSession('Coverage 2026'),
            'class' => $this->createClass('Class 10'),
            'section' => $this->createSection('Coverage A'),
            'subject' => $this->createSubject('Coverage Bangla'),
        ];
        $science = $this->createDepartment('Science');
        $this->saveAssignment('all-male', $scope, 'male');

        try {
            $this->saveAssignment('science-male', array_merge($scope, ['department' => $science]), 'male');
            $this->fail('All Departments male coverage must block concrete male coverage.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('genderScope', $exception->errors());
        }

        $this->saveAssignment('science-female', array_merge($scope, ['department' => $science]), 'female');
        $this->assertDatabaseCount('teacher_class_subjects', 2);
    }

    public function test_different_concrete_departments_coexist_but_block_new_all_departments(): void
    {
        $scope = [
            'session' => $this->createSession('Concrete 2026'),
            'class' => $this->createClass('Class Nine'),
            'section' => $this->createSection('Concrete A'),
            'subject' => $this->createSubject('Concrete Bangla'),
        ];
        $science = $this->createDepartment('Science');
        $business = $this->createDepartment('Business Studies');
        $this->saveAssignment('science-only', array_merge($scope, ['department' => $science]), 'male');
        $this->saveAssignment('business-only', array_merge($scope, ['department' => $business]), 'male');

        try {
            $this->saveAssignment('all-after-concrete', $scope, 'male');
            $this->fail('Concrete coverage must block overlapping All Departments coverage.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('genderScope', $exception->errors());
        }
        $this->assertDatabaseCount('teacher_class_subjects', 2);
    }

    public function test_collision_matrix_rejects_all_overlaps_through_real_save_path(): void
    {
        $session = $this->createSession('Matrix S1');
        $class = $this->createClass('Matrix C1');
        $section = $this->createSection('Matrix A');
        $subject = $this->createSubject('Mathematics');
        $scope = compact('session', 'class', 'section', 'subject');

        $this->saveAssignment('matrix-all', $scope, 'all');

        foreach (['all', 'male', 'female'] as $gender) {
            try {
                $this->saveAssignment('blocked-'.$gender, $scope, $gender);
                $this->fail("Expected {$gender} coverage to conflict with All.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('genderScope', $exception->errors());
            }
        }

        $availability = app(TeacherSubjectAssignmentAvailabilityService::class);
        foreach (['all', 'male', 'female'] as $gender) {
            $this->assertFalse($availability->canAssignGender($this->context($scope), $gender));
            $this->assertFalse($availability->subjectsWithAvailability(
                $this->context($scope),
                $gender
            )->contains('id', $subject->id));
        }
        $this->assertDatabaseCount('teacher_class_subjects', 1);
    }

    public function test_male_and_female_coverage_are_exclusive_but_complementary(): void
    {
        $scope = [
            'session' => $this->createSession('Split S1'),
            'class' => $this->createClass('Split C1'),
            'section' => $this->createSection('Split A'),
            'subject' => $this->createSubject('Split Mathematics'),
        ];
        $this->saveAssignment('split-male', $scope, 'male');

        $availability = app(TeacherSubjectAssignmentAvailabilityService::class);
        $this->assertSame(['female'], $availability->availableGenderScopes($this->context($scope)));
        $this->assertFalse($availability->canAssignGender($this->context($scope), 'all'));
        $this->assertFalse($availability->canAssignGender($this->context($scope), 'male'));
        $this->assertTrue($availability->canAssignGender($this->context($scope), 'female'));

        $this->saveAssignment('split-female', $scope, 'female');
        $this->assertSame([], $availability->availableGenderScopes($this->context($scope)));

        foreach (['all', 'male', 'female'] as $gender) {
            try {
                $this->saveAssignment('split-blocked-'.$gender, $scope, $gender);
                $this->fail("Expected full split coverage to reject {$gender}.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('genderScope', $exception->errors());
            }
        }
        $this->assertDatabaseCount('teacher_class_subjects', 2);
    }

    public function test_same_teacher_can_hold_complementary_gender_rows_and_endpoint_filters_subjects(): void
    {
        $scope = [
            'session' => $this->createSession('Same Teacher S1'),
            'class' => $this->createClass('Same Teacher C1'),
            'section' => $this->createSection('Same Teacher A'),
            'subject' => $this->createSubject('Same Teacher Mathematics'),
        ];
        $teacher = $this->saveAssignment('same-teacher', $scope, 'male');
        $payload = $this->teacherPayload([
            'userId' => $teacher->id,
            'adminName' => $teacher->adminName,
            'userName' => $teacher->adminUser,
            'userMobile' => $teacher->adminMobile,
            'userMail' => $teacher->adminMail,
            'pass' => '',
            'confirmPass' => '',
            'assignmentSessionId' => $scope['session']->id,
            'className' => [$scope['class']->id, $scope['class']->id],
            'section' => [$scope['section']->id, $scope['section']->id],
            'optionalGroup' => ['', ''],
            'departmentScope' => ['not_applicable', 'not_applicable'],
            'genderScope' => ['male', 'female'],
            'subject' => [$scope['subject']->id, $scope['subject']->id],
        ]);
        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));

        $this->assertDatabaseCount('teacher_class_subjects', 2);
        foreach (['all', 'male', 'female'] as $gender) {
            $response = app(CultivationController::class)->assignmentAvailability(
                Request::create('/api/teacher/assignment-availability', 'POST', [
                    'sessionId' => $scope['session']->id,
                    'classId' => $scope['class']->id,
                    'sectionId' => $scope['section']->id,
                    'optionalGroupId' => '',
                    'departmentScope' => 'not_applicable',
                    'genderScope' => $gender,
                ])
            );
            $this->assertFalse(collect($response->getData(true)['subjects'])->contains('id', $scope['subject']->id));
        }
    }

    public function test_forged_unknown_section_cannot_bypass_server_validation(): void
    {
        $scope = [
            'session' => $this->createSession('Forged S1'),
            'class' => $this->createClass('Forged C1'),
            'section' => $this->createSection('Forged A'),
            'subject' => $this->createSubject('Forged Mathematics'),
        ];

        try {
            $payload = $this->teacherPayload([
                'userName' => 'forged-section',
                'userMail' => 'forged-section@example.test',
                'assignmentSessionId' => $scope['session']->id,
                'className' => [$scope['class']->id],
                'section' => [999999],
                'optionalGroup' => [''],
                'genderScope' => ['all'],
                'subject' => [$scope['subject']->id],
            ]);
            app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));
            $this->fail('Expected forged section to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('section.0', $exception->errors());
        }
        $this->assertDatabaseCount('teacher_class_subjects', 0);
    }

    public function test_scope_dimensions_and_nullable_department_are_isolated_null_safely(): void
    {
        $scope = [
            'session' => $this->createSession('Dimension S1'),
            'class' => $this->createClass('Dimension C1'),
            'section' => $this->createSection('Dimension A'),
            'subject' => $this->createSubject('Dimension Mathematics'),
            'department' => null,
        ];
        $this->saveAssignment('dimension-base', $scope, 'male');
        $availability = app(TeacherSubjectAssignmentAvailabilityService::class);

        $variants = [
            array_merge($scope, ['session' => $this->createSession('Dimension S2')]),
            array_merge($scope, ['class' => $this->createClass('Dimension C2')]),
            array_merge($scope, ['section' => $this->createSection('Dimension B')]),
            array_merge($scope, ['subject' => $this->createSubject('Dimension Physics')]),
            array_merge($scope, ['department' => $this->createDepartment('Science')]),
        ];

        foreach (array_slice($variants, 0, 4) as $variant) {
            $this->assertTrue($availability->canAssignGender($this->context($variant), 'male'));
        }
        $this->assertFalse($availability->canAssignGender($this->context($variants[4]), 'male'));
        $this->assertFalse($availability->canAssignGender($this->context($scope), 'male'));
        $this->assertTrue($availability->canAssignGender($this->context($scope), 'female'));
    }

    public function test_edit_can_replace_own_coverage_but_cannot_replace_into_another_coverage(): void
    {
        $scope = [
            'session' => $this->createSession('Edit S1'),
            'class' => $this->createClass('Edit C1'),
            'section' => $this->createSection('Edit A'),
            'subject' => $this->createSubject('Edit Mathematics'),
        ];
        $teacher = $this->saveAssignment('edit-owner', $scope, 'male');

        $this->saveAssignment('edit-owner', $scope, 'all', $teacher);
        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacher->id,
            'gender_scope' => 'all',
        ]);

        $this->saveAssignment('edit-owner', $scope, 'male', $teacher);
        $this->saveAssignment('edit-female-owner', $scope, 'female');

        try {
            $this->saveAssignment('edit-owner', $scope, 'all', $teacher);
            $this->fail('Expected All to conflict with another teacher female coverage.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('genderScope', $exception->errors());
        }
        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacher->id,
            'gender_scope' => 'male',
        ]);
    }

    public function test_sequential_competing_requests_cannot_create_duplicate_or_overlapping_coverage(): void
    {
        $scope = [
            'session' => $this->createSession('Race S1'),
            'class' => $this->createClass('Race C1'),
            'section' => $this->createSection('Race A'),
            'subject' => $this->createSubject('Race Mathematics'),
        ];
        $this->saveAssignment('race-winner', $scope, 'all');

        foreach (['all', 'male'] as $gender) {
            try {
                $this->saveAssignment('race-loser-'.$gender, $scope, $gender);
                $this->fail('Expected competing coverage to be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('genderScope', $exception->errors());
            }
        }
        $this->assertDatabaseCount('teacher_class_subjects', 1);
    }

    public function test_exact_section_common_rows_do_not_suppress_class_wide_common_subjects(): void
    {
        $session = $this->ensureSession();
        $class = $this->createClass('Class 10');
        $section = $this->createSection('A');
        $science = $this->createDepartment('Science');

        $exactSectionCommon = $this->createSubject('Exact Section Common');
        $classWideCommon = $this->createSubject('Class Wide Common');

        $this->insertMapping($session->id, $class->id, $section->id, null, $exactSectionCommon->id, 1);
        $this->insertMapping($session->id, $class->id, null, null, $classWideCommon->id, 2);

        $response = app(CultivationController::class)->assignmentAvailability(Request::create('/api/teacher/assignment-availability', 'POST', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $section->id,
            'optionalGroupId' => $science->id,
            'departmentScope' => 'specific',
            'genderScope' => 'all',
        ]));

        $subjects = collect($response->getData(true)['subjects'] ?? []);
        $this->assertTrue($subjects->contains('id', $exactSectionCommon->id));
        $this->assertTrue($subjects->contains('id', $classWideCommon->id));
    }

    public function test_optional_and_religious_candidates_are_available_as_supplemental_subjects(): void
    {
        $session = $this->ensureSession();
        $class = $this->createClass('Class 10');
        $section = $this->createSection('B');
        $science = $this->createDepartment('Science');

        $mappedMain = $this->createSubject('Mapped Main');
        $religious = $this->createSubject('Supplemental Religious');
        $religious->isReligious = 1;
        $religious->assign_class = (string) $class->id;
        $religious->save();

        $optional = $this->createSubject('Supplemental Optional');
        $optional->subjectType = 'Optional';
        $optional->assign_class = (string) $class->id;
        $optional->save();

        $this->insertMapping($session->id, $class->id, $section->id, null, $mappedMain->id, 1);

        $response = app(CultivationController::class)->assignmentAvailability(Request::create('/api/teacher/assignment-availability', 'POST', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $section->id,
            'optionalGroupId' => $science->id,
            'departmentScope' => 'specific',
            'genderScope' => 'all',
        ]));

        $subjects = collect($response->getData(true)['subjects'] ?? []);
        $this->assertTrue($subjects->contains('id', $mappedMain->id));
        $this->assertTrue($subjects->contains('id', $religious->id));
        $this->assertTrue($subjects->contains('id', $optional->id));
    }

    public function test_assignment_availability_and_save_validation_share_the_same_catalog_set(): void
    {
        $session = $this->ensureSession();
        $class = $this->createClass('Class 10');
        $section = $this->createSection('C');
        $science = $this->createDepartment('Science');
        $business = $this->createDepartment('Business Studies');

        $common = $this->createSubject('Aligned Common');
        $scienceOnly = $this->createSubject('Aligned Science');
        $businessOnly = $this->createSubject('Aligned Business');

        $this->insertMapping($session->id, $class->id, $section->id, null, $common->id, 1);
        $this->insertMapping($session->id, $class->id, null, $science->id, $scienceOnly->id, 2);
        $this->insertMapping($session->id, $class->id, null, $business->id, $businessOnly->id, 3);

        $availability = app(CultivationController::class)->assignmentAvailability(Request::create('/api/teacher/assignment-availability', 'POST', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $section->id,
            'optionalGroupId' => $science->id,
            'departmentScope' => 'specific',
            'genderScope' => 'all',
        ]));
        $availableIds = collect($availability->getData(true)['subjects'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
            'userName' => 'catalog-align-pass',
            'userMail' => 'catalog-align-pass@example.test',
            'assignmentSessionId' => $session->id,
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [$science->id],
            'departmentScope' => ['specific'],
            'genderScope' => ['all'],
            'subject' => [$scienceOnly->id],
        ])));

        $this->assertContains($scienceOnly->id, $availableIds);

        try {
            app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->teacherPayload([
                'userName' => 'catalog-align-reject',
                'userMail' => 'catalog-align-reject@example.test',
                'assignmentSessionId' => $session->id,
                'className' => [$class->id],
                'section' => [$section->id],
                'optionalGroup' => [$science->id],
                'departmentScope' => ['specific'],
                'genderScope' => ['all'],
                'subject' => [$businessOnly->id],
            ])));
            $this->fail('Expected unavailable cross-department subject to be rejected by save-time validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subject', $exception->errors());
        }
    }

    public function test_create_and_edit_forms_use_the_same_assignment_availability_endpoint(): void
    {
        $createView = file_get_contents(resource_path('views/admin-modern/users/create.blade.php'));
        $editView = file_get_contents(resource_path('views/admin-modern/users/edit.blade.php'));

        $this->assertIsString($createView);
        $this->assertIsString($editView);
        $this->assertStringContainsString("route('api.teacher.assignment-availability')", $createView);
        $this->assertStringContainsString("route('api.teacher.assignment-availability')", $editView);
    }

    private function teacherPayload(array $overrides = []): array
    {
        return array_merge([
            'adminName' => 'Teacher Assignment User',
            'userName' => 'teacher-assignment-user',
            'userMobile' => '01700000000',
            'userMail' => 'teacher-assignment-user@example.test',
            'userType' => CultivationAdmin::ROLE_TEACHER,
            'pass' => 'secret123',
            'confirmPass' => 'secret123',
            'assignmentSessionId' => $this->ensureSession()->id,
            'primaryClass' => '',
            'primarySection' => '',
            'className' => [],
            'section' => [],
            'optionalGroup' => [],
            'departmentScope' => [],
            'genderScope' => [],
            'subject' => [],
        ], $overrides);
    }

    private function saveAssignment(
        string $username,
        array $scope,
        string $gender,
        ?CultivationAdmin $teacher = null
    ): CultivationAdmin {
        $payload = $this->teacherPayload([
            'userId' => $teacher?->id,
            'adminName' => $teacher?->adminName ?? 'Assignment '.$username,
            'userName' => $teacher?->adminUser ?? $username,
            'userMobile' => $teacher?->adminMobile ?? '017'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'userMail' => $teacher?->adminMail ?? $username.'@example.test',
            'pass' => $teacher ? '' : 'secret123',
            'confirmPass' => $teacher ? '' : 'secret123',
            'assignmentSessionId' => $scope['session']->id,
            'className' => [$scope['class']->id],
            'section' => [$scope['section']->id],
            'optionalGroup' => [$scope['department']->id ?? ''],
            'departmentScope' => [isset($scope['department'])
                ? 'specific'
                : (app(\App\Services\DepartmentBasedClassDetector::class)->isDepartmentBasedClass($scope['class']->className)
                    ? 'all'
                    : 'not_applicable')],
            'genderScope' => [$gender],
            'subject' => [$scope['subject']->id],
        ]);
        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));

        return $teacher ?? CultivationAdmin::where('adminUser', $username)->firstOrFail();
    }

    private function context(array $scope): array
    {
        return [
            'session_id' => $scope['session']->id,
            'class_id' => $scope['class']->id,
            'section_id' => $scope['section']->id,
            'group_id' => $scope['department']->id ?? null,
            'subject_id' => $scope['subject']->id,
        ];
    }

    private function ensureSession(): sessionManage
    {
        $session = sessionManage::query()->first();
        if ($session) {
            return $session;
        }

        return $this->createSession('2026');
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

    private function createSubject(string $name): Subject
    {
        $subject = new Subject();
        $subject->subjectName = $name;
        $subject->subjectType = 'Theory';
        $subject->save();

        return $subject;
    }

    private function createDepartment(string $name): Department
    {
        $department = new Department();
        $department->departmentName = $name;
        $department->save();

        return $department;
    }

    private function insertMapping(
        int $sessionId,
        int $classId,
        ?int $sectionId,
        ?int $departmentId,
        int $subjectId,
        int $sortOrder
    ): void {
        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $sessionId,
            'class_id' => (string) $classId,
            'section_id' => $sectionId === null ? null : (string) $sectionId,
            'department_id' => $departmentId === null ? null : (string) $departmentId,
            'subject_id' => $subjectId,
            'mapping_type' => 'main',
            'sort_order' => $sortOrder,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
        $admin->save();

        return $admin;
    }
}

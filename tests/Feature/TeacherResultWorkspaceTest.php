<?php

namespace Tests\Feature;

use App\Models\ClassManage;
use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamRoutine;
use App\Models\ExamRoutineItem;
use App\Models\Marksheet;
use App\Models\MarksScopeState;
use App\Models\NewAdmission;
use App\Models\ResultLifecycleEvent;
use App\Models\ResultPublish;
use App\Models\SectionManage;
use App\Models\SessionManage;
use App\Models\Subject;
use App\Models\TeacherClassSubject;
use App\Services\TeacherResultExamEligibilityService;
use App\Http\Controllers\CultivationController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tests\TestCase;

class TeacherResultWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_admin_assignment_workflow_authorizes_listing_workspace_draft_and_confirm(): void
    {
        $scope = $this->scope();
        $payload = [
            'userId' => $scope['teacher']->id,
            'adminName' => $scope['teacher']->adminName,
            'userName' => $scope['teacher']->adminUser,
            'userMobile' => $scope['teacher']->adminMobile,
            'userMail' => $scope['teacher']->adminMail,
            'userType' => CultivationAdmin::ROLE_TEACHER,
            'pass' => '',
            'assignmentSessionId' => $scope['session']->id,
            'primaryClass' => '',
            'primarySection' => '',
            'className' => [$scope['class']->id],
            'section' => [$scope['section']->id],
            'optionalGroup' => [$scope['department']->id],
            'departmentScope' => ['specific'],
            'genderScope' => ['all'],
            'subject' => [$scope['subject']->id],
        ];
        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));

        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $scope['teacher']->id, 'session_id' => $scope['session']->id,
            'class_id' => $scope['class']->id, 'section_id' => $scope['section']->id,
            'group_id' => $scope['department']->id, 'subject_id' => $scope['subject']->id,
            'gender_scope' => 'all',
        ]);
        $this->actingAs($scope['teacher'], 'teacher')->get(route('teacher.results.index'))
            ->assertOk()->assertSee($scope['subject']->subjectName);
        $this->get(route('teacher.results.workspace', $this->query($scope)))->assertOk();

        $otherScope = $this->scope($scope['teacher'], 'Other Subject', 'Other Class');
        foreach ([
            ['sessionId' => $otherScope['session']->id],
            ['classId' => $otherScope['class']->id],
            ['groupId' => $otherScope['section']->id],
            ['optionalGroupId' => $otherScope['department']->id],
            ['subjectId' => $otherScope['subject']->id],
        ] as $forgery) {
            $this->get(route('teacher.results.workspace', $this->query($scope, $forgery)))
                ->assertRedirect(route('teacher.results.index'));
        }
        $badExam = $this->exam('Incompatible Admin Flow Exam', (string) $otherScope['class']->id);
        $this->get(route('teacher.results.workspace', $this->query($scope, ['examId' => $badExam->id])))
            ->assertRedirect(route('teacher.results.index'));
        $otherTeacher = $this->teacher('Other Admin Assigned Teacher', 'T-other');
        $this->actingAs($otherTeacher, 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope)))
            ->assertRedirect(route('teacher.results.index'));

        $this->actingAs($scope['teacher'], 'teacher');
        $this->post(route('teacher.results.draft'), $this->marksPayload($scope))->assertRedirect();
        $this->post(route('teacher.results.confirm'), $this->query($scope) + ['scope_revision' => 2])
            ->assertRedirect();
        $this->assertDatabaseHas('marks_scope_states', ['status' => 'confirmed']);
    }

    public function test_legacy_admin_assignment_without_session_is_hidden_and_fails_closed(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        TeacherClassSubject::where('teacher_id', $scope['teacher']->id)->update(['session_id' => null]);

        $this->actingAs($scope['teacher'], 'teacher')->get(route('teacher.results.index'))
            ->assertOk()->assertDontSee($scope['subject']->subjectName);
        $this->get(route('teacher.results.workspace', $this->query($scope)))
            ->assertRedirect(route('teacher.results.index'));
    }

    public function test_result_views_show_readable_labels_and_preserve_hidden_numeric_scope_ids(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);

        $index = $this->actingAs($scope['teacher'], 'teacher')->get(route('teacher.results.index'));

        $index->assertOk()
            ->assertSee($scope['session']->session)
            ->assertSee($scope['class']->className)
            ->assertSee($scope['section']->section)
            ->assertSee($scope['department']->departmentName)
            ->assertSee($scope['subject']->subjectName)
            ->assertSee($scope['exam']->examName)
            ->assertSee('name="sessionId" value="'.$scope['session']->id.'"', false)
            ->assertSee('name="classId" value="'.$scope['class']->id.'"', false)
            ->assertSee('name="groupId" value="'.$scope['section']->id.'"', false)
            ->assertSee('name="optionalGroupId" value="'.$scope['department']->id.'"', false)
            ->assertSee('name="subjectId" value="'.$scope['subject']->id.'"', false);

        $workspace = $this->get(route('teacher.results.workspace', $this->query($scope)));

        $workspace->assertOk()
            ->assertSee('Session '.$scope['session']->session)
            ->assertSee('Class '.$scope['class']->className)
            ->assertSee('Section '.$scope['section']->section)
            ->assertSee('Department '.$scope['department']->departmentName)
            ->assertSee($scope['subject']->subjectName)
            ->assertSee($scope['exam']->examName)
            ->assertSee('name="sessionId" value="'.$scope['session']->id.'"', false)
            ->assertSee('name="classId" value="'.$scope['class']->id.'"', false)
            ->assertSee('name="groupId" value="'.$scope['section']->id.'"', false)
            ->assertSee('name="optionalGroupId" value="'.$scope['department']->id.'"', false)
            ->assertSee('name="subjectId" value="'.$scope['subject']->id.'"', false)
            ->assertSee('name="examId" value="'.$scope['exam']->id.'"', false);
    }

    public function test_one_normal_session_aware_assignment_appears_once_even_with_duplicate_rows(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $this->assignment($scope['teacher'], $scope);

        $response = $this->actingAs($scope['teacher'], 'teacher')->get(route('teacher.results.index'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Open Workspace'));
    }

    public function test_null_session_assignment_never_expands_when_sessions_are_added(): void
    {
        $scope = $this->scope();
        $secondSession = $this->academicSession('2027');
        $this->assignment($scope['teacher'], $scope);
        TeacherClassSubject::where('teacher_id', $scope['teacher']->id)->update(['session_id' => null]);

        $response = $this->actingAs($scope['teacher'], 'teacher')->get(route('teacher.results.index'));

        $response->assertOk()
            ->assertDontSee($scope['subject']->subjectName)
            ->assertDontSee($secondSession->session);
        $this->assertSame(0, substr_count($response->getContent(), 'Open Workspace'));
    }

    public function test_teacher_accesses_results_and_merges_matching_male_and_female_assignments(): void
    {
        $scope = $this->scope();
        $other = $this->teacher('Other Teacher', 'T-2');
        $this->assignment($scope['teacher'], $scope, 'male');
        $this->assignment($scope['teacher'], $scope, 'female');
        $otherScope = $this->scope($other, 'Other Subject', 'Other Class');
        $this->assignment($other, $otherScope);

        $response = $this->actingAs($scope['teacher'], 'teacher')->get(route('teacher.results.index'));

        $response->assertOk()
            ->assertSee('Teacher Result Workspace')
            ->assertSee($scope['subject']->subjectName)
            ->assertSee($scope['exam']->examName)
            ->assertSee('All')
            ->assertDontSee('Other Subject')
            ->assertDontSee('Other Class');
        $this->assertSame(1, substr_count($response->getContent(), 'Open Workspace'));
    }

    public function test_matching_gender_assignments_render_unique_male_first_students_and_preserve_draft_confirm_order(): void
    {
        $scope = $this->scope();
        $original = $scope['students']->first();
        $original->forceFill(['gender' => '1', 'rollNumber' => '4'])->save();
        $maleTwo = $this->student($scope, 'Male Roll Two');
        $maleTwo->forceFill(['gender' => '1', 'rollNumber' => '2'])->save();
        $maleOne = $this->student($scope, 'Male Roll One');
        $maleOne->forceFill(['gender' => '1', 'rollNumber' => '1'])->save();
        $femaleFive = $this->student($scope, 'Female Roll Five');
        $femaleFive->forceFill(['gender' => '2', 'rollNumber' => '5'])->save();
        $femaleOne = $this->student($scope, 'Female Roll One');
        $femaleOne->forceFill(['gender' => '2', 'rollNumber' => '1'])->save();
        $femaleThree = $this->student($scope, 'Female Roll Three');
        $femaleThree->forceFill(['gender' => '2', 'rollNumber' => '3'])->save();

        $this->assignment($scope['teacher'], $scope, 'male');
        $this->assignment($scope['teacher'], $scope, 'male');
        $this->assignment($scope['teacher'], $scope, 'female');
        $this->assignment($scope['teacher'], $scope, 'female');

        $query = $this->query($scope, ['gender' => 'all']);
        $workspace = $this->actingAs($scope['teacher'], 'teacher')
            ->get(route('teacher.results.workspace', $query));
        $workspace->assertOk();

        $expectedIds = [$maleOne->id, $maleTwo->id, $original->id, $femaleOne->id, $femaleThree->id, $femaleFive->id];
        $renderedIds = collect($workspace->viewData('students'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame($expectedIds, $renderedIds);
        $this->assertCount(count($expectedIds), array_unique($renderedIds));

        $payload = $query + [
            'studentId' => $expectedIds,
            'cqMarks' => array_fill(0, count($expectedIds), 80),
            'mcqMarks' => array_fill(0, count($expectedIds), null),
            'practical' => array_fill(0, count($expectedIds), null),
            'scope_revision' => 1,
        ];
        $this->post(route('teacher.results.draft'), $payload)->assertRedirect();

        $reloaded = $this->get(route('teacher.results.workspace', $query));
        $reloadedIds = collect($reloaded->viewData('students'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame($expectedIds, $reloadedIds);

        $this->post(route('teacher.results.confirm'), $query + ['scope_revision' => 2])
            ->assertRedirect()
            ->assertSessionHas('success', 'Subject marks confirmed successfully.');
        $this->assertSame(count($expectedIds), Marksheet::whereIn('studentId', $expectedIds)->count());
    }

    public function test_different_sections_remain_distinct_effective_scopes(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $secondSection = $this->section('Section B');
        TeacherClassSubject::create([
            'teacher_id' => $scope['teacher']->id,
            'session_id' => $scope['session']->id,
            'class_id' => $scope['class']->id,
            'section_id' => $secondSection->id,
            'group_id' => $scope['department']->id,
            'subject_id' => $scope['subject']->id,
            'gender_scope' => 'all',
        ]);

        $response = $this->actingAs($scope['teacher'], 'teacher')->get(route('teacher.results.index'));
        $response->assertOk()->assertSee('Section A')->assertSee('Section B');
        $this->assertSame(2, substr_count($response->getContent(), 'Open Workspace'));
    }

    public function test_different_departments_remain_distinct_effective_scopes(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $secondDepartment = new Department();
        $secondDepartment->forceFill(['departmentName' => 'Commerce']);
        $secondDepartment->save();
        TeacherClassSubject::create([
            'teacher_id' => $scope['teacher']->id,
            'session_id' => $scope['session']->id,
            'class_id' => $scope['class']->id,
            'section_id' => $scope['section']->id,
            'group_id' => $secondDepartment->id,
            'subject_id' => $scope['subject']->id,
            'gender_scope' => 'all',
        ]);

        $response = $this->actingAs($scope['teacher'], 'teacher')->get(route('teacher.results.index'));
        $response->assertOk()->assertSee($scope['department']->departmentName)->assertSee('Commerce');
        $this->assertSame(2, substr_count($response->getContent(), 'Open Workspace'));
    }

    public function test_different_subjects_remain_distinct_effective_scopes(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $secondSubject = $this->subject('Physics');
        TeacherClassSubject::create([
            'teacher_id' => $scope['teacher']->id,
            'session_id' => $scope['session']->id,
            'class_id' => $scope['class']->id,
            'section_id' => $scope['section']->id,
            'group_id' => $scope['department']->id,
            'subject_id' => $secondSubject->id,
            'gender_scope' => 'all',
        ]);

        $response = $this->actingAs($scope['teacher'], 'teacher')->get(route('teacher.results.index'));
        $response->assertOk()->assertSee($scope['subject']->subjectName)->assertSee('Physics');
        $this->assertSame(2, substr_count($response->getContent(), 'Open Workspace'));
    }

    public function test_guest_and_admin_session_are_blocked_from_teacher_results(): void
    {
        $this->get(route('teacher.results.index'))->assertRedirect(route('teacher.login'));
        $this->withSession(['cultivationAdmin' => 1])
            ->get(route('teacher.results.index'))
            ->assertRedirect(route('teacher.login'));
    }

    public function test_exam_eligibility_uses_matching_class_and_verified_zero_all_classes_value(): void
    {
        $scope = $this->scope();
        $all = $this->exam('All Class Assessment', '0');
        $other = $this->exam('Other Class Assessment', '999');
        $eligible = app(TeacherResultExamEligibilityService::class)->eligibleForClass($scope['class']->id);

        $this->assertTrue($eligible->contains($scope['exam']));
        $this->assertTrue($eligible->contains($all));
        $this->assertFalse($eligible->contains($other));
        $this->assertSame('0', TeacherResultExamEligibilityService::ALL_CLASSES_VALUE);
    }

    public function test_exam_without_routine_is_eligible_and_routine_subjects_do_not_restrict_it(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);

        $this->assertDatabaseCount('exam_routines', 0);
        $this->actingAs($scope['teacher'], 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope)))
            ->assertOk();

        $routine = $this->routine($scope, $this->subject('Unrelated Routine Subject'));
        $this->assertNotNull($routine);
        $this->actingAs($scope['teacher'], 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope)))
            ->assertOk()
            ->assertSee($scope['subject']->subjectName);
    }

    public function test_routine_for_related_scope_cannot_grant_incompatible_exam_or_assignment(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $incompatible = $this->exam('Wrong Class Exam', '999');
        $this->routine($scope + ['exam' => $incompatible], $scope['subject']);

        $this->actingAs($scope['teacher'], 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope, ['examId' => $incompatible->id])))
            ->assertRedirect(route('teacher.results.index'));

        $other = $this->teacher('Unassigned Teacher', 'T-9');
        $this->actingAs($other, 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope)))
            ->assertRedirect(route('teacher.results.index'));
    }

    public function test_unknown_and_class_incompatible_exam_are_rejected_on_workspace_draft_and_confirm(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $bad = $this->exam('Wrong Exam', '999');

        foreach ([$bad->id, 999999] as $examId) {
            $query = $this->query($scope, ['examId' => $examId]);
            $this->actingAs($scope['teacher'], 'teacher')
                ->get(route('teacher.results.workspace', $query))
                ->assertRedirect(route('teacher.results.index'));
            $this->post(route('teacher.results.draft'), $this->marksPayload($scope, ['examId' => $examId]))
                ->assertSessionHas('error');
            $this->post(route('teacher.results.confirm'), $query + ['scope_revision' => 1])
                ->assertSessionHas('error');
        }
        $this->assertDatabaseCount('marksheets', 0);
    }

    public function test_forged_assignment_dimensions_are_rejected(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $other = $this->scope($scope['teacher'], 'Other Subject', 'Other Class');

        foreach ([
            ['sessionId' => $other['session']->id],
            ['classId' => $other['class']->id],
            ['groupId' => $other['section']->id],
            ['optionalGroupId' => $other['department']->id],
            ['subjectId' => $other['subject']->id],
        ] as $forgery) {
            $this->actingAs($scope['teacher'], 'teacher')
                ->get(route('teacher.results.workspace', $this->query($scope, $forgery)))
                ->assertRedirect(route('teacher.results.index'));
        }
    }

    public function test_workspace_loads_only_authorized_students_and_existing_marks(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $outside = $this->student($scope, 'Outside Student', section: $this->section('Other Section'));
        $mark = Marksheet::create([
            'studentId' => $scope['students']->first()->id,
            'sessionId' => $scope['session']->id,
            'classId' => $scope['class']->id,
            'groupId' => $scope['section']->id,
            'examId' => $scope['exam']->id,
            'subjectId' => $scope['subject']->id,
            'subjectMarks' => 72,
            'totalMarks' => 72,
            'laterGrade' => 'A',
            'gradePoint' => 4,
        ]);

        $this->actingAs($scope['teacher'], 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope)))
            ->assertOk()
            ->assertSee($scope['students']->first()->fullName)
            ->assertSee((string) $mark->subjectMarks)
            ->assertDontSee($outside->fullName);
    }

    public function test_workspace_displays_centralized_component_threshold_results_instead_of_stored_grades(): void
    {
        $scope = $this->scope();
        $scope['subject']->forceFill(['CQ' => 70, 'MCQ' => 30, 'Practical' => null])->save();
        $scope['exam']->forceFill(['passingSystem' => 1])->save();
        $this->mapSubject($scope, $scope['subject']);
        $passing = $scope['students']->first();
        $failing = $this->student($scope, 'Threshold Fail Student');
        $scope['students']->push($failing);
        $this->assignment($scope['teacher'], $scope);

        foreach ([[$passing, 23, 'F', 0], [$failing, 22, 'A+', 5]] as [$student, $cq, $storedGrade, $storedPoint]) {
            Marksheet::create([
                'studentId' => $student->id,
                'sessionId' => $scope['session']->id,
                'classId' => $scope['class']->id,
                'groupId' => $scope['section']->id,
                'examId' => $scope['exam']->id,
                'subjectId' => $scope['subject']->id,
                'subjectMarks' => $cq,
                'objectMarks' => 10,
                'totalMarks' => $cq + 10,
                'laterGrade' => $storedGrade,
                'gradePoint' => $storedPoint,
            ]);
        }

        $response = $this->actingAs($scope['teacher'], 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope)));

        $response->assertOk()->assertSeeText('Point')->assertSeeText('Result');
        $results = $response->viewData('calculatedResults');
        $this->assertSame('Pass', $results->get($passing->id)->status);
        $this->assertSame('D', $results->get($passing->id)->letterGrade);
        $this->assertSame(1.0, $results->get($passing->id)->gradePoint);
        $this->assertSame('Fail', $results->get($failing->id)->status);
        $this->assertSame('F', $results->get($failing->id)->letterGrade);
        $this->assertSame(0.0, $results->get($failing->id)->gradePoint);
        $this->assertDatabaseHas('marksheets', [
            'studentId' => $passing->id,
            'laterGrade' => 'F',
            'gradePoint' => 0,
        ]);
    }

    public function test_workspace_displays_twenty_five_mark_component_boundary_from_centralized_calculator(): void
    {
        $scope = $this->scope();
        $scope['subject']->forceFill(['CQ' => 50, 'MCQ' => 25, 'Practical' => 25])->save();
        $scope['exam']->forceFill(['passingSystem' => 1])->save();
        $this->mapSubject($scope, $scope['subject']);
        $passing = $scope['students']->first();
        $failing = $this->student($scope, 'Twenty Five Mark Fail Student');
        $scope['students']->push($failing);
        $this->assignment($scope['teacher'], $scope);

        foreach ([[$passing, 8], [$failing, 7]] as [$student, $mcq]) {
            Marksheet::create([
                'studentId' => $student->id,
                'sessionId' => $scope['session']->id,
                'classId' => $scope['class']->id,
                'groupId' => $scope['section']->id,
                'examId' => $scope['exam']->id,
                'subjectId' => $scope['subject']->id,
                'subjectMarks' => 17,
                'objectMarks' => $mcq,
                'practicalMarks' => 8,
                'totalMarks' => 25 + $mcq,
                'laterGrade' => $mcq === 8 ? 'F' : 'A+',
                'gradePoint' => $mcq === 8 ? 0 : 5,
            ]);
        }

        $response = $this->actingAs($scope['teacher'], 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope)));
        $results = $response->viewData('calculatedResults');

        $this->assertSame('Pass', $results->get($passing->id)->status);
        $this->assertSame('Fail', $results->get($failing->id)->status);
    }

    public function test_workspace_displays_the_same_paired_subject_result_as_the_centralized_calculator(): void
    {
        $scope = $this->scope(subjectName: 'English 1st Paper');
        $scope['subject']->forceFill(['alias' => 'english_1st_paper', 'CQ' => 100])->save();
        $companion = Subject::create([
            'subjectName' => 'English 2nd Paper',
            'alias' => 'english_2nd_paper',
            'subjectType' => 'Theory',
            'CQ' => 100,
        ]);
        $this->mapSubject($scope, $scope['subject']);
        $this->mapSubject($scope, $companion);
        $this->assignment($scope['teacher'], $scope);
        $student = $scope['students']->first();

        foreach ([[$scope['subject'], 29, 'F'], [$companion, 50, 'B']] as [$subject, $cq, $storedGrade]) {
            Marksheet::create([
                'studentId' => $student->id,
                'sessionId' => $scope['session']->id,
                'classId' => $scope['class']->id,
                'groupId' => $scope['section']->id,
                'examId' => $scope['exam']->id,
                'subjectId' => $subject->id,
                'subjectMarks' => $cq,
                'totalMarks' => $cq,
                'laterGrade' => $storedGrade,
                'gradePoint' => $storedGrade === 'F' ? 0 : 3,
            ]);
        }

        $response = $this->actingAs($scope['teacher'], 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope)));
        $result = $response->viewData('calculatedResults')->get($student->id);

        $this->assertSame([$scope['subject']->id, $companion->id], array_map('intval', $result->sourceSubjectIds));
        $this->assertSame(79.0, $result->obtainedMarks);
        $this->assertSame('D', $result->letterGrade);
        $this->assertSame(1.0, $result->gradePoint);
        $this->assertSame('Pass', $result->status);
    }

    public function test_forged_student_is_rejected_without_partial_write(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $outside = $this->student($scope, 'Outside Student', section: $this->section('Other Section'));
        $payload = $this->marksPayload($scope);
        $payload['studentId'][] = $outside->id;
        $payload['cqMarks'][] = 70;

        $this->actingAs($scope['teacher'], 'teacher')
            ->post(route('teacher.results.draft'), $payload)
            ->assertSessionHas('error');
        $this->assertDatabaseCount('marksheets', 0);
    }

    public function test_authorized_draft_uses_engine_revision_and_creates_teacher_audit(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);

        $this->actingAs($scope['teacher'], 'teacher')
            ->post(route('teacher.results.draft'), $this->marksPayload($scope))
            ->assertRedirect();

        $this->assertDatabaseHas('marksheets', [
            'studentId' => (string) $scope['students']->first()->id,
            'teacher_id' => $scope['teacher']->id,
            'subjectMarks' => 80,
        ]);
        $this->assertDatabaseHas('marks_scope_states', ['status' => 'Draft', 'revision' => 2]);
        $this->assertDatabaseHas('result_lifecycle_events', [
            'actor_id' => $scope['teacher']->id,
            'actor_role' => 'teacher',
            'action' => 'draft_marks_created',
        ]);
    }

    public function test_invalid_mark_range_and_stale_revision_are_rejected(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $invalid = $this->marksPayload($scope);
        $invalid['cqMarks'] = [101];

        $this->actingAs($scope['teacher'], 'teacher')
            ->post(route('teacher.results.draft'), $invalid)
            ->assertSessionHasErrors('cqMarks.0');

        $this->post(route('teacher.results.draft'), $this->marksPayload($scope))->assertRedirect();
        $this->post(route('teacher.results.draft'), $this->marksPayload($scope, ['cqMarks' => [70]]))
            ->assertSessionHas('error', 'This workspace is stale. Reload it before submitting again.');
        $this->assertDatabaseHas('marksheets', ['subjectMarks' => 80]);
    }

    public function test_draft_and_confirm_enforce_the_subject_component_maximums(): void
    {
        $scope = $this->scope();
        $scope['subject']->forceFill(['CQ' => 40, 'MCQ' => 30, 'Practical' => 20])->save();
        $this->assignment($scope['teacher'], $scope);
        $this->actingAs($scope['teacher'], 'teacher');

        foreach ([
            'cqMarks' => [41],
            'mcqMarks' => [31],
            'practical' => [21],
        ] as $field => $marks) {
            $payload = $this->marksPayload($scope, [
                'cqMarks' => [40],
                'mcqMarks' => [30],
                'practical' => [20],
                $field => $marks,
            ]);

            $this->post(route('teacher.results.draft'), $payload)
                ->assertSessionHasErrors($field.'.0');
            $this->post(route('teacher.results.confirm'), $payload + ['submission_action' => 'confirm'])
                ->assertSessionHasErrors($field.'.0');
        }

        $this->assertDatabaseMissing('marksheets', [
            'studentId' => (string) $scope['students']->first()->id,
            'examId' => $scope['exam']->id,
            'subjectId' => $scope['subject']->id,
        ]);

        $workspace = $this->get(route('teacher.results.workspace', $this->query($scope)));
        $workspace->assertOk()
            ->assertSee('data-configured-maximum="40"', false)
            ->assertSee('data-configured-maximum="30"', false)
            ->assertSee('data-configured-maximum="20"', false);
    }

    public function test_confirm_succeeds_only_when_ready_and_audits_actor(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $this->actingAs($scope['teacher'], 'teacher')
            ->post(route('teacher.results.draft'), $this->marksPayload($scope));

        $this->post(route('teacher.results.confirm'), $this->query($scope) + ['scope_revision' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('marks_scope_states', ['status' => 'Confirmed', 'revision' => 2]);
        $this->assertDatabaseHas('result_lifecycle_events', [
            'actor_id' => $scope['teacher']->id,
            'action' => 'subject_confirmed',
        ]);
    }

    public function test_gender_scoped_teacher_can_draft_and_confirm_only_the_assigned_population(): void
    {
        $scope = $this->scope();
        $female = $this->student($scope, 'Female Student');
        $female->forceFill(['gender' => '2', 'rollNumber' => '2'])->save();
        $this->assignment($scope['teacher'], $scope, 'male');

        $query = $this->query($scope, ['gender' => 'male']);
        $payload = $this->marksPayload($scope, ['gender' => 'male']);

        $this->actingAs($scope['teacher'], 'teacher')
            ->post(route('teacher.results.draft'), $payload)
            ->assertRedirect();

        $this->post(route('teacher.results.confirm'), $query + ['scope_revision' => 2])
            ->assertRedirect()
            ->assertSessionHas('success', 'Subject marks confirmed successfully.');

        $this->assertDatabaseHas('marks_scope_states', [
            'status' => MarksScopeState::STATUS_CONFIRMED,
            'revision' => 2,
        ]);
        $this->assertDatabaseMissing('marksheets', [
            'studentId' => (string) $female->id,
            'examId' => (string) $scope['exam']->id,
            'subjectId' => (string) $scope['subject']->id,
        ]);
    }

    public function test_female_only_assignment_excludes_male_students(): void
    {
        $scope = $this->scope();
        $female = $this->student($scope, 'Female Student');
        $female->forceFill(['gender' => '2', 'rollNumber' => '1'])->save();
        $this->assignment($scope['teacher'], $scope, 'female');

        $response = $this->actingAs($scope['teacher'], 'teacher')->get(route(
            'teacher.results.workspace',
            $this->query($scope, ['gender' => 'female'])
        ));

        $response->assertOk();
        $ids = collect($response->viewData('students'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame([$female->id], $ids);
    }

    public function test_confirm_rejects_incomplete_and_stale_lifecycle_state(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);

        $this->actingAs($scope['teacher'], 'teacher')
            ->post(route('teacher.results.confirm'), $this->query($scope) + ['scope_revision' => 1])
            ->assertSessionHas('error');

        $this->post(route('teacher.results.draft'), $this->marksPayload($scope));
        $this->post(route('teacher.results.confirm'), $this->query($scope) + ['scope_revision' => 1])
            ->assertSessionHas('error', 'This workspace is stale. Reload it before submitting again.');
    }

    public function test_confirmed_and_published_scopes_are_read_only(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $this->actingAs($scope['teacher'], 'teacher')
            ->post(route('teacher.results.draft'), $this->marksPayload($scope));
        $this->post(route('teacher.results.confirm'), $this->query($scope) + ['scope_revision' => 2]);

        $this->get(route('teacher.results.workspace', $this->query($scope)))
            ->assertOk()->assertSee('Confirmed')->assertDontSee('Save Draft');
        $this->post(route('teacher.results.draft'), $this->marksPayload($scope, ['scope_revision' => 2]))
            ->assertSessionHas('error', 'Confirmed marks are read-only.');

        ResultPublish::create($this->query($scope) + ['status' => ResultPublish::STATUS_PUBLISHED]);
        $this->get(route('teacher.results.workspace', $this->query($scope)))
            ->assertOk()->assertSee('Published')->assertDontSee('Save Draft');
    }

    public function test_other_teacher_cannot_view_or_modify_scope(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $other = $this->teacher('Other Teacher', 'T-2');

        $this->actingAs($other, 'teacher')
            ->get(route('teacher.results.workspace', $this->query($scope)))
            ->assertRedirect(route('teacher.results.index'));
        $this->post(route('teacher.results.draft'), $this->marksPayload($scope))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('marksheets', 0);
    }

    public function test_same_class_exam_keeps_separate_authorized_session_scopes(): void
    {
        $scope = $this->scope();
        $secondSession = $this->academicSession('2027');
        $second = $scope;
        $second['session'] = $secondSession;
        $second['students'] = collect([$this->student($second, 'Second Session Student')]);
        $this->assignment($scope['teacher'], $scope);
        $this->assignment($scope['teacher'], $second);

        $this->actingAs($scope['teacher'], 'teacher')
            ->post(route('teacher.results.draft'), $this->marksPayload($scope));
        $this->post(route('teacher.results.draft'), $this->marksPayload($second));

        $this->assertDatabaseHas('marks_scope_states', ['sessionId' => (string) $scope['session']->id]);
        $this->assertDatabaseHas('marks_scope_states', ['sessionId' => (string) $secondSession->id]);
        $this->assertDatabaseCount('marks_scope_states', 2);
    }

    public function test_reopen_publish_and_promotion_routes_are_not_exposed_in_teacher_namespace(): void
    {
        $routes = app('router')->getRoutes();
        $this->assertNull($routes->getByName('teacher.results.reopen'));
        $this->assertNull($routes->getByName('teacher.results.publish'));
        $this->assertNull($routes->getByName('teacher.results.promote'));
    }

    public function test_results_activity_is_actor_scoped_and_raw_payload_is_hidden(): void
    {
        $scope = $this->scope();
        $this->assignment($scope['teacher'], $scope);
        $this->actingAs($scope['teacher'], 'teacher')
            ->post(route('teacher.results.draft'), $this->marksPayload($scope));

        $event = ResultLifecycleEvent::where('actor_id', $scope['teacher']->id)->firstOrFail();
        $this->get(route('teacher.results.index'))
            ->assertOk()
            ->assertSee('Draft marks saved')
            ->assertDontSee($event->event_uuid)
            ->assertDontSee('change_set');
    }

    private function scope(?CultivationAdmin $teacher = null, string $subjectName = 'Mathematics', string $className = 'Class Nine'): array
    {
        $teacher ??= $this->teacher();
        $session = $this->academicSession('2026');
        $class = new ClassManage(); $class->forceFill(['className' => $className]); $class->save();
        $section = $this->section('Section A');
        $department = new Department(); $department->forceFill(['departmentName' => 'Science']); $department->save();
        $subject = $this->subject($subjectName);
        $exam = $this->exam('Annual Examination', (string) $class->id);
        $base = compact('teacher', 'session', 'class', 'section', 'department', 'subject', 'exam');
        $students = collect([$this->student($base, 'Authorized Student')]);
        return $base + compact('students');
    }

    private function teacher(string $name = 'Portal Teacher', string $username = 'T-1'): CultivationAdmin
    {
        $teacher = new CultivationAdmin();
        $teacher->forceFill([
            'adminName' => $name, 'adminUser' => $username,
            'adminMail' => strtolower(str_replace(' ', '.', $name)).uniqid().'@example.test',
            'adminMobile' => '017'.random_int(10000000, 99999999),
            'userType' => CultivationAdmin::ROLE_TEACHER,
            'loginPassword' => Hash::make('secret'),
        ]);
        $teacher->save();
        return $teacher;
    }

    private function assignment(CultivationAdmin $teacher, array $scope, string $gender = 'all'): void
    {
        TeacherClassSubject::create([
            'teacher_id' => $teacher->id, 'session_id' => $scope['session']->id,
            'class_id' => $scope['class']->id, 'section_id' => $scope['section']->id,
            'group_id' => $scope['department']->id, 'subject_id' => $scope['subject']->id,
            'gender_scope' => $gender,
        ]);
    }

    private function marksPayload(array $scope, array $override = []): array
    {
        return array_merge($this->query($scope), [
            'studentId' => $scope['students']->pluck('id')->all(),
            'cqMarks' => $scope['students']->map(fn () => 80)->all(),
            'mcqMarks' => $scope['students']->map(fn () => null)->all(),
            'practical' => $scope['students']->map(fn () => null)->all(),
            'scope_revision' => 1,
        ], $override);
    }

    private function query(array $scope, array $override = []): array
    {
        return array_merge([
            'sessionId' => $scope['session']->id, 'classId' => $scope['class']->id,
            'groupId' => $scope['section']->id, 'optionalGroupId' => $scope['department']->id,
            'subjectId' => $scope['subject']->id, 'examId' => $scope['exam']->id,
        ], $override);
    }

    private function student(array $scope, string $name, ?SectionManage $section = null): NewAdmission
    {
        return NewAdmission::create([
            'stdId' => random_int(10000, 99999), 'fullName' => $name, 'gender' => '1',
            'sessName' => (string) $scope['session']->id, 'className' => (string) $scope['class']->id,
            'sectionName' => (string) ($section?->id ?? $scope['section']->id),
            'departmentName' => (string) $scope['department']->id, 'rollNumber' => '1',
        ]);
    }

    private function exam(string $name, string $class): Exam
    {
        $exam = new Exam(); $exam->examName = $name; $exam->className = $class;
        $exam->baseMark = 100; $exam->passingSystem = 2; $exam->save(); return $exam;
    }

    private function subject(string $name): Subject
    {
        return Subject::create(['subjectName' => $name, 'subjectType' => 'Theory', 'CQ' => 100]);
    }

    private function academicSession(string $name): SessionManage
    {
        $session = new SessionManage(); $session->forceFill(['session' => $name]); $session->save(); return $session;
    }

    private function section(string $name): SectionManage
    {
        $section = new SectionManage(); $section->forceFill(['section' => $name]); $section->save(); return $section;
    }

    private function routine(array $scope, Subject $itemSubject): ExamRoutine
    {
        $routine = new ExamRoutine();
        $routine->forceFill([
            'title' => 'Optional Schedule', 'assignClass' => $scope['class']->id,
            'assignSection' => $scope['section']->id, 'assignDepartment' => $scope['department']->id,
            'assignSession' => $scope['session']->id, 'assignExam' => $scope['exam']->id,
            'status' => 'result_routine',
        ]);
        $routine->save();
        ExamRoutineItem::create(['exam_routine_id' => $routine->id, 'subject_id' => $itemSubject->id]);
        return $routine;
    }

    private function mapSubject(array $scope, Subject $subject, string $type = 'main'): void
    {
        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $scope['session']->id,
            'class_id' => (string) $scope['class']->id,
            'section_id' => (string) $scope['section']->id,
            'department_id' => null,
            'subject_id' => $subject->id,
            'mapping_type' => $type,
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

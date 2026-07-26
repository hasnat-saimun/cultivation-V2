<?php

namespace Tests\Support;

use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\Exam;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Services\ResultMarksDraftService;
use App\Services\ResultMarksConfirmationService;

trait CreatesResultLifecycleScope
{
    protected function lifecycleScope(int $studentCount = 1, ?sectionManage $section = null): array
    {
        $session = new sessionManage();
        $session->session = '2026';
        $session->save();
        $class = new classManage();
        $class->className = 'Class 8';
        $class->save();
        if (!$section) {
            $section = new sectionManage();
            $section->section = 'A';
            $section->save();
        }
        $department = new Department();
        $department->departmentName = 'General';
        $department->save();
        $exam = new Exam();
        $exam->examName = 'Annual';
        $exam->passingSystem = 0;
        $exam->save();
        $subject = new Subject();
        $subject->subjectName = 'Bangla';
        $subject->subjectType = 'Theory';
        $subject->CQ = 100;
        $subject->save();

        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $section->id,
            'department_id' => null,
            'subject_id' => (int) $subject->id,
            'mapping_type' => 'main',
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $students = collect();
        for ($i = 1; $i <= $studentCount; $i++) {
            $student = new newAdmission([
                'stdId' => 5000 + $i,
                'fullName' => 'Student',
                'sureName' => (string) $i,
                'gender' => '1',
                'sessName' => (string) $session->id,
                'className' => (string) $class->id,
                'sectionName' => (string) $section->id,
                'departmentName' => (string) $department->id,
                'rollNumber' => (string) $i,
            ]);
            $student->save();
            $students->push($student);
        }
        return compact('session', 'class', 'section', 'department', 'exam', 'subject', 'students');
    }

    protected function lifecycleActor(int $role = CultivationAdmin::ROLE_GENERAL): CultivationAdmin
    {
        $actor = new CultivationAdmin();
        $actor->adminName = 'Lifecycle Actor';
        $actor->adminUser = 'lifecycle_'.uniqid();
        $actor->userType = $role;
        $actor->loginPassword = Hash::make('secret');
        $actor->adminMobile = '01700000000';
        $actor->adminMail = uniqid().'@example.test';
        $actor->save();
        return $actor;
    }

    protected function lifecycleInput(array $data, float|string|null $mark = 80): array
    {
        return [
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => $data['section']->id,
            'examId' => $data['exam']->id,
            'subjectId' => $data['subject']->id,
            'studentId' => $data['students']->pluck('id')->all(),
            'cqMarks' => $data['students']->map(fn () => $mark)->all(),
            'mcqMarks' => $data['students']->map(fn () => '')->all(),
            'practical' => $data['students']->map(fn () => '')->all(),
            'gender' => 'all',
        ];
    }

    protected function confirmedLifecycleScope(float|string|null $mark = 80, int $studentCount = 1): array
    {
        $data = $this->lifecycleScope($studentCount);
        $actor = $this->lifecycleActor();
        app(ResultMarksDraftService::class)->save($this->lifecycleInput($data, $mark), $actor, null, true);
        app(ResultMarksConfirmationService::class)->confirm(
            $this->lifecycleInput($data, $mark) + ['scope_revision' => 2],
            $actor,
        );
        return [$data, $actor, [
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => $data['section']->id,
            'examId' => $data['exam']->id,
        ]];
    }
}

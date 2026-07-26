<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassManage;
use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\NewAdmission;
use App\Models\SectionManage;
use App\Models\SessionManage;
use App\Models\Subject;
use App\Models\TeacherClassSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherAcademicWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_classes_show_context_and_scoped_student_count(): void
    {
        $s=$this->scope(); $this->assign($s); $this->student($s,'Authorized');
        $this->student($this->scope(), 'Outside');
        $this->actingAs($s['teacher'],'teacher')->get(route('teacher.classes.index'))
            ->assertOk()->assertSee($s['subject']->subjectName)->assertSee('1');
    }

    public function test_student_list_search_and_pagination_stay_in_authorized_population(): void
    {
        $s=$this->scope(); $this->assign($s);
        for($i=1;$i<=22;$i++) $this->student($s, "Student {$i}", (string)$i);
        $outside=$this->student($this->scope(), 'Forbidden Student');
        $this->actingAs($s['teacher'],'teacher')->get(route('teacher.students.index'))
            ->assertOk()->assertSee('Student 1')->assertDontSee($outside->fullName)->assertSee('?page=2', false);
        $this->get(route('teacher.students.index',['search'=>'Student 22']))
            ->assertOk()->assertSee('Student 22')->assertDontSee('Student 21');
    }

    public function test_authorized_profile_has_read_only_result_and_attendance_summaries(): void
    {
        $s=$this->scope(); $this->assign($s); $student=$this->student($s,'Profile Student');
        $exam=new Exam(); $exam->forceFill(['examName'=>'Annual','className'=>(string)$s['class']->id,'baseMark'=>100,'passingSystem'=>2]); $exam->save();
        Marksheet::create(['studentId'=>$student->id,'sessionId'=>$s['session']->id,'classId'=>$s['class']->id,
            'groupId'=>$s['section']->id,'examId'=>$exam->id,'subjectId'=>$s['subject']->id,'subjectMarks'=>80,
            'totalMarks'=>80,'laterGrade'=>'A+','gradePoint'=>5]);
        Attendance::create(['attendance_date'=>'2026-07-25','class_id'=>$s['class']->id,'section_id'=>$s['section']->id,
            'session_id'=>$s['session']->id,'student_id'=>$student->id,'teacher_id'=>$s['teacher']->id,'status'=>'Present']);
        $this->actingAs($s['teacher'],'teacher')->get(route('teacher.students.show',$student))
            ->assertOk()->assertSee('Annual')->assertSee($s['subject']->subjectName)->assertSee('Present:')->assertDontSee('Edit');
    }

    public function test_unassigned_subject_result_is_not_exposed(): void
    {
        $s=$this->scope(); $this->assign($s); $student=$this->student($s,'Student');
        $other=Subject::create(['subjectName'=>'Private Subject','subjectType'=>'Theory','CQ'=>100]);
        $exam=new Exam(); $exam->forceFill(['examName'=>'Exam','className'=>(string)$s['class']->id,'baseMark'=>100,'passingSystem'=>2]); $exam->save();
        Marksheet::create(['studentId'=>$student->id,'sessionId'=>$s['session']->id,'classId'=>$s['class']->id,'groupId'=>$s['section']->id,
            'examId'=>$exam->id,'subjectId'=>$other->id,'subjectMarks'=>90,'totalMarks'=>90,'laterGrade'=>'A+','gradePoint'=>5]);
        $this->actingAs($s['teacher'],'teacher')->get(route('teacher.students.show',$student))->assertOk()->assertDontSee('Private Subject');
    }

    public function test_forged_and_other_teacher_student_profiles_return_not_found(): void
    {
        $s=$this->scope(); $this->assign($s); $student=$this->student($s,'Protected');
        $other=$this->scope(); $this->assign($other);
        $this->actingAs($other['teacher'],'teacher')->get(route('teacher.students.show',$student))->assertNotFound();
        $this->get(route('teacher.students.show',999999))->assertNotFound();
    }

    public function test_gender_section_department_and_session_assignment_dimensions_are_enforced(): void
    {
        $s=$this->scope(); $this->assign($s,'male'); $male=$this->student($s,'Male','1', '1');
        $female=$this->student($s,'Female','2','2');
        $this->actingAs($s['teacher'],'teacher')->get(route('teacher.students.index'))
            ->assertOk()->assertSee($male->fullName)->assertDontSee($female->fullName);
    }

    public function test_guest_and_admin_only_session_are_denied(): void
    {
        foreach(['teacher.classes.index','teacher.students.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('teacher.login'));
            $this->withSession(['cultivationAdmin'=>1])->get(route($route))->assertRedirect(route('teacher.login'));
        }
    }

    private function scope(): array
    {
        $teacher=$this->teacher(); $session=$this->model(SessionManage::class,['session'=>uniqid('S')]);
        $class=$this->model(ClassManage::class,['className'=>uniqid('C')]); $section=$this->model(SectionManage::class,['section'=>uniqid('Sec')]);
        $department=$this->model(Department::class,['departmentName'=>uniqid('D')]);
        $subject=Subject::create(['subjectName'=>uniqid('Subject'),'subjectType'=>'Theory','CQ'=>100]);
        return compact('teacher','session','class','section','department','subject');
    }
    private function assign(array $s,string $gender='all'): void { TeacherClassSubject::create(['teacher_id'=>$s['teacher']->id,'session_id'=>$s['session']->id,'class_id'=>$s['class']->id,'section_id'=>$s['section']->id,'group_id'=>$s['department']->id,'subject_id'=>$s['subject']->id,'gender_scope'=>$gender]); }
    private function student(array $s,string $name,string $roll='1',string $gender='1'): NewAdmission { return NewAdmission::create(['stdId'=>random_int(10000,99999),'fullName'=>$name,'gender'=>$gender,'sessName'=>(string)$s['session']->id,'className'=>(string)$s['class']->id,'sectionName'=>(string)$s['section']->id,'departmentName'=>(string)$s['department']->id,'rollNumber'=>$roll,'status'=>'Active']); }
    private function teacher(): CultivationAdmin { $m=new CultivationAdmin();$m->forceFill(['adminName'=>'Teacher','adminUser'=>uniqid('t'),'adminMail'=>uniqid().'@test.local','adminMobile'=>'01700000000','userType'=>1,'loginPassword'=>Hash::make('secret')]);$m->save();return $m; }
    private function model(string $class,array $data) { $m=new $class();$m->forceFill($data);$m->save();return $m; }
}

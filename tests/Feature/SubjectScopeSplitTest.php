<?php
namespace Tests\Feature;
use App\Models\{ClassManage,CultivationAdmin,Department,Exam,Marksheet,NewAdmission,SectionManage,SessionManage,Subject,TeacherClassSubject};
use App\Services\{MarksEntryAuthorizationService,SubjectScopeSplitService};
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Hash};
use RuntimeException;
use Tests\TestCase;
class SubjectScopeSplitTest extends TestCase {
 use RefreshDatabase;
 public function test_generic_split_migrates_paired_nonpaired_and_optional_subjects_without_changing_marks():void {
  foreach([
   ['Bangla 2nd Paper','bangla_2nd_paper','Theory',null],
   ['General Science','general_science','Theory',null],
   ['Higher Math','higher_math','Optional','fourthSubjectId'],
  ] as [$name,$alias,$type,$studentField]){
   $scope=$this->fixture($name,$alias,$type,$studentField);
   $service=app(SubjectScopeSplitService::class);
   $beforeBatch=app(ResultCalculationBatchBuilder::class)->build($scope['exam']->id,$scope['moveClass']->id,$scope['session']->id,$scope['section']->id,$scope['department']->id);
   $beforeResult=$beforeBatch['entries'][$scope['moveStudent']->id]['result'];
   $beforeSubject=collect($beforeResult->subjectResults)->first(fn($r)=>in_array((string)$scope['source']->id,$r->sourceSubjectIds,true));
   $dry=$service->execute($scope['source']->id,$scope['destination']->id,[$scope['remainClass']->id],[$scope['moveClass']->id]);
   $this->assertFalse($dry['apply']);$this->assertSame(1,$dry['counts']['marksheets.subjectId']);
   $this->assertDatabaseHas('marksheets',['id'=>$scope['moveMark']->id,'subjectId'=>(string)$scope['source']->id,'subjectMarks'=>23.25]);
   $service->execute($scope['source']->id,$scope['destination']->id,[$scope['remainClass']->id],[$scope['moveClass']->id],true,'test');
   $this->assertDatabaseHas('marksheets',['id'=>$scope['moveMark']->id,'subjectId'=>(string)$scope['destination']->id,'subjectMarks'=>23.25,'objectMarks'=>15.5,'practicalMarks'=>4.5]);
   $this->assertDatabaseHas('marksheets',['id'=>$scope['remainMark']->id,'subjectId'=>(string)$scope['source']->id]);
   $this->assertDatabaseHas('curriculum_subject_mappings',['class_id'=>(string)$scope['moveClass']->id,'subject_id'=>$scope['destination']->id]);
   $this->assertDatabaseHas('teacher_class_subjects',['class_id'=>$scope['moveClass']->id,'subject_id'=>$scope['destination']->id]);
   if($studentField)$this->assertSame($scope['destination']->id,(int)$scope['moveStudent']->fresh()->$studentField);
   $this->assertTrue(app(MarksEntryAuthorizationService::class)->canEnterMarksFor($scope['teacher'],$scope['moveClass']->id,$scope['destination']->id,$scope['section']->id,$scope['department']->id,$scope['session']->id));
   $afterBatch=app(ResultCalculationBatchBuilder::class)->build($scope['exam']->id,$scope['moveClass']->id,$scope['session']->id,$scope['section']->id,$scope['department']->id);
   $afterResult=$afterBatch['entries'][$scope['moveStudent']->id]['result'];
   $afterSubject=collect($afterResult->subjectResults)->first(fn($r)=>in_array((string)$scope['destination']->id,$r->sourceSubjectIds,true));
   $this->assertSame([$beforeSubject->obtainedMarks,$beforeSubject->letterGrade,$beforeSubject->gradePoint,$beforeSubject->status],[$afterSubject->obtainedMarks,$afterSubject->letterGrade,$afterSubject->gradePoint,$afterSubject->status]);
   $this->assertSame([$beforeResult->gpa,$beforeResult->status],[$afterResult->gpa,$afterResult->status]);
   $service->execute($scope['source']->id,$scope['destination']->id,[$scope['remainClass']->id],[$scope['moveClass']->id],true,'test-rerun');
   $this->assertSame(1,Marksheet::whereKey($scope['moveMark']->id)->count());
  }
 }
 public function test_collision_rolls_back_every_reference():void {
  $s=$this->fixture('Religion','religion','Theory',null);
  Marksheet::create(['studentId'=>$s['moveStudent']->id,'sessionId'=>$s['session']->id,'classId'=>$s['moveClass']->id,'groupId'=>$s['section']->id,'examId'=>$s['exam']->id,'subjectId'=>$s['destination']->id,'subjectMarks'=>20]);
  try{app(SubjectScopeSplitService::class)->execute($s['source']->id,$s['destination']->id,[$s['remainClass']->id],[$s['moveClass']->id],true);$this->fail('Expected collision.');}catch(RuntimeException $e){$this->assertStringContainsString('no overwrite',$e->getMessage());}
  $this->assertSame($s['source']->id,(int)$s['moveMark']->fresh()->subjectId);
  $this->assertDatabaseHas('teacher_class_subjects',['class_id'=>$s['moveClass']->id,'subject_id'=>$s['source']->id]);
  $this->assertDatabaseCount('subject_scope_migration_audits',0);
 }
 public function test_dry_run_create_destination_and_compatibility_guards_are_safe():void {
  $s=$this->fixture('Future Subject','future_subject','Theory',null);
  $this->artisan('subject:split-scope',['source'=>$s['source']->id,'destination'=>$s['destination']->id,'--remain'=>[$s['remainClass']->id],'--migrate'=>[$s['moveClass']->id]])->assertSuccessful()->expectsOutputToContain('Dry-run only');
  $this->assertSame($s['source']->id,(int)$s['moveMark']->fresh()->subjectId);
  $s['destination']->delete();
  app(SubjectScopeSplitService::class)->execute($s['source']->id,null,[$s['remainClass']->id],[$s['moveClass']->id],true,'test',true);
  $created=Subject::where('subjectName','Future Subject')->where('id','!=',$s['source']->id)->firstOrFail();
  $this->assertSame('23.25',(string)$s['moveMark']->fresh()->subjectMarks);
  $this->assertSame($created->id,(int)$s['moveMark']->fresh()->subjectId);

  $bad=$this->fixture('Incompatible Subject','incompatible','Theory',null);$bad['destination']->update(['CQ'=>40]);
  $this->expectException(RuntimeException::class);
  app(SubjectScopeSplitService::class)->execute($bad['source']->id,$bad['destination']->id,[$bad['remainClass']->id],[$bad['moveClass']->id],true);
 }
 public function test_overlapping_normalized_scope_is_rejected():void {
  $s=$this->fixture('Overlap Subject','overlap','Theory',null);
  Subject::create(['subjectName'=>' overlap   subject ','alias'=>'overlap-other','subjectType'=>'Theory','assign_class'=>(string)$s['moveClass']->id,'passingSystem'=>'1','CQ'=>50,'MCQ'=>25,'Practical'=>25]);
  $this->expectException(RuntimeException::class);$this->expectExceptionMessage('Overlapping active class scope');
  app(SubjectScopeSplitService::class)->execute($s['source']->id,$s['destination']->id,[$s['remainClass']->id],[$s['moveClass']->id],true);
 }
 public function test_classless_teacher_subject_is_auto_resolved_from_exact_class_assignments():void {
  $s=$this->fixture('Teacher Scoped Subject','teacher_scoped','Theory',null);
  DB::table('teacher_subjects')->insert(['teacher_id'=>$s['teacher']->id,'subject_id'=>$s['source']->id,'created_at'=>now(),'updated_at'=>now()]);
  $dry=app(SubjectScopeSplitService::class)->execute($s['source']->id,$s['destination']->id,[$s['remainClass']->id],[$s['moveClass']->id]);
  $this->assertSame(1,$dry['teacher_resolution']['auto_resolved']);
  $this->assertSame(0,$dry['teacher_resolution']['manual_unresolved']);
  $this->assertSame('both',$dry['teacher_resolution']['rows'][0]['action']);
  app(SubjectScopeSplitService::class)->execute($s['source']->id,$s['destination']->id,[$s['remainClass']->id],[$s['moveClass']->id],true,'test');
  $this->assertDatabaseHas('teacher_subjects',['teacher_id'=>$s['teacher']->id,'subject_id'=>$s['source']->id]);
  $this->assertDatabaseHas('teacher_subjects',['teacher_id'=>$s['teacher']->id,'subject_id'=>$s['destination']->id]);
  $this->assertSame(2,DB::table('teacher_subjects')->where('teacher_id',$s['teacher']->id)->count());
 }
 public function test_unproven_classless_teacher_subject_requires_explicit_resolution():void {
  $s=$this->fixture('Manual Teacher Subject','manual_teacher','Theory',null);
  DB::table('teacher_class_subjects')->where('teacher_id',$s['teacher']->id)->delete();
  $rowId=DB::table('teacher_subjects')->insertGetId(['teacher_id'=>$s['teacher']->id,'subject_id'=>$s['source']->id,'created_at'=>now(),'updated_at'=>now()]);
  $dry=app(SubjectScopeSplitService::class)->execute($s['source']->id,$s['destination']->id,[$s['remainClass']->id],[$s['moveClass']->id]);
  $this->assertSame(1,$dry['teacher_resolution']['manual_unresolved']);
  try{app(SubjectScopeSplitService::class)->execute($s['source']->id,$s['destination']->id,[$s['remainClass']->id],[$s['moveClass']->id],true,'test');$this->fail('Expected manual resolution blocker.');}catch(RuntimeException $e){$this->assertStringContainsString('explicit resolution',$e->getMessage());}
  app(SubjectScopeSplitService::class)->execute($s['source']->id,$s['destination']->id,[$s['remainClass']->id],[$s['moveClass']->id],true,'test',false,[$rowId=>'both']);
  $this->assertDatabaseHas('teacher_subjects',['teacher_id'=>$s['teacher']->id,'subject_id'=>$s['source']->id]);
  $this->assertDatabaseHas('teacher_subjects',['teacher_id'=>$s['teacher']->id,'subject_id'=>$s['destination']->id]);
 }
 private function fixture(string $name,string $alias,string $type,?string $studentField):array {
  $session=new SessionManage();$session->forceFill(['session'=>'2026']);$session->save();$remainClass=new ClassManage();$remainClass->forceFill(['className'=>'Class Eight']);$remainClass->save();$moveClass=new ClassManage();$moveClass->forceFill(['className'=>'Class Nine']);$moveClass->save();$section=new SectionManage();$section->forceFill(['section'=>'A']);$section->save();$department=new Department();$department->forceFill(['departmentName'=>'Science']);$department->save();
  $source=Subject::create(['subjectName'=>$name,'alias'=>$alias,'subjectType'=>$type,'assign_class'=>$remainClass->id.','.$moveClass->id,'passingSystem'=>'1','CQ'=>50,'MCQ'=>25,'Practical'=>25]);
  $destination=Subject::create(['subjectName'=>'  '.mb_strtoupper($name).'  ','alias'=>$alias,'subjectType'=>$type,'assign_class'=>'','passingSystem'=>'1','CQ'=>50,'MCQ'=>25,'Practical'=>25]);
  $exam=new Exam();$exam->forceFill(['examName'=>'Annual','className'=>'0','baseMark'=>100,'passingSystem'=>2]);$exam->save();$teacher=new CultivationAdmin();$teacher->forceFill(['adminName'=>'Teacher','adminUser'=>uniqid('t'),'adminMail'=>uniqid().'@test','adminMobile'=>'01700000000','userType'=>1,'loginPassword'=>Hash::make('secret')]);$teacher->save();
  foreach([[$remainClass,$source],[$moveClass,$source]] as [$class,$subject])TeacherClassSubject::create(['teacher_id'=>$teacher->id,'session_id'=>$session->id,'class_id'=>$class->id,'section_id'=>$section->id,'group_id'=>$department->id,'subject_id'=>$subject->id,'gender_scope'=>'all']);
  $students=[];$marks=[];foreach([$remainClass,$moveClass] as $class){$attrs=['stdId'=>(string)random_int(10000,99999),'fullName'=>'Student','gender'=>'1','sessName'=>(string)$session->id,'className'=>(string)$class->id,'sectionName'=>(string)$section->id,'departmentName'=>(string)$department->id,'rollNumber'=>'1'];if($studentField)$attrs[$studentField]=$source->id;$student=NewAdmission::create($attrs);$mark=Marksheet::create(['studentId'=>$student->id,'sessionId'=>$session->id,'classId'=>$class->id,'groupId'=>$section->id,'examId'=>$exam->id,'subjectId'=>$source->id,'subjectMarks'=>23.25,'objectMarks'=>15.5,'practicalMarks'=>4.5]);DB::table('curriculum_subject_mappings')->insert(['session_id'=>(string)$session->id,'class_id'=>(string)$class->id,'section_id'=>(string)$section->id,'department_id'=>(string)$department->id,'subject_id'=>$source->id,'mapping_type'=>$type==='Optional'?'optional':'main','sort_order'=>1,'is_active'=>1,'source'=>'test','created_at'=>now(),'updated_at'=>now()]);$students[]=$student;$marks[]=$mark;}
  return compact('session','remainClass','moveClass','section','department','source','destination','exam','teacher')+['remainStudent'=>$students[0],'moveStudent'=>$students[1],'remainMark'=>$marks[0],'moveMark'=>$marks[1]];
 }
}

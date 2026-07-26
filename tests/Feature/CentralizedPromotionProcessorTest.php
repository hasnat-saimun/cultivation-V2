<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\Placement;
use App\Models\PromotionAuditLog;
use App\Models\ResultArchive;
use App\Models\ResultPublish;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\ResultCalculation\CentralizedPromotionProcessor;
use App\Services\ResultCalculation\PromotionPreviewBuilder;
use App\Services\ResultCalculation\PromotionProcessingException;
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use App\Http\Controllers\AdmissionController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CentralizedPromotionProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['result_engine.promotion_enabled'=>true,'cache.default'=>'array']);
    }

    public function test_published_pass_promotes_atomically_with_selected_exam_archive_and_audit(): void
    {
        $scope=$this->scope();$subject=$this->subject('Main','Main',100);$student=$this->student($scope,'01');
        $selected=$this->mark($student,$scope,$subject,80);
        $otherExam=new Exam();$otherExam->examName='Other';$otherExam->passingSystem=2;$otherExam->save();
        $other=$this->mark($student,$scope,$subject,0,$otherExam);
        $legacyOtherArchive=ResultArchive::create(['student_id'=>$student->id,'old_class'=>$scope['class']->id,'old_session'=>$scope['session']->id,
            'old_section'=>$scope['section']->id,'old_roll'=>'01','exam_id'=>$otherExam->id,'result_data'=>['legacy'=>true]]);
        $placement=$this->placement($student,$scope);
        $this->publish($scope);
        $marksBefore=Marksheet::orderBy('id')->get()->map->getAttributes()->all();
        $placementBefore=$placement->fresh()->getAttributes();
        $publishBefore=ResultPublish::first()->getAttributes();

        $report=$this->processor()->process(...$this->args($scope,[$student->id]));

        $this->assertSame(1,$report['studentsPromoted']);
        $student->refresh();
        $this->assertSame($scope['toClass']->id,(int)$student->className);
        $this->assertSame($scope['toSession']->id,(int)$student->sessName);
        $this->assertSame($scope['toSection']->id,(int)$student->sectionName);
        $this->assertSame('01',$student->rollNumber);
        $archive=ResultArchive::where('exam_id',$scope['exam']->id)->firstOrFail();
        $this->assertSame($scope['exam']->id,(int)$archive->exam_id);
        $this->assertSame($scope['class']->id,(int)$archive->old_class);
        $this->assertSame($scope['session']->id,(int)$archive->old_session);
        $this->assertSame('Pass',$archive->result_data['status']);
        $this->assertSame(5.0,(float)$archive->result_data['gpa']);
        $this->assertCount(1,$archive->result_data['marks']);
        $this->assertSame($subject->id,(int)$archive->result_data['marks'][0]['subject_id']);
        $this->assertDatabaseCount('promotion_audit_logs',1);
        $this->assertSame($marksBefore,Marksheet::orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame($placementBefore,$placement->fresh()->getAttributes());
        $this->assertSame($publishBefore,ResultPublish::first()->getAttributes());
        $this->assertNotNull($other->fresh());
        $this->assertSame(['legacy'=>true],$legacyOtherArchive->fresh()->result_data);
    }

    public function test_unpublished_and_flag_disabled_write_block_but_dry_run_never_writes(): void
    {
        $scope=$this->scope();$subject=$this->subject('Main','Main',100);$student=$this->student($scope,'01');$this->mark($student,$scope,$subject,80);
        $this->assertBlocked(fn()=> $this->processor()->process(...$this->args($scope,[$student->id])),'PUBLICATION_REQUIRED');
        $this->assertDatabaseCount('result_archives',0);
        $this->publish($scope);
        ResultPublish::first()->forceFill(['status' => ResultPublish::STATUS_UNPUBLISHED])->save();
        $this->assertBlocked(fn()=> $this->processor()->process(...$this->args($scope,[$student->id])),'PUBLICATION_REQUIRED');
        ResultPublish::first()->forceFill(['status' => ResultPublish::STATUS_PUBLISHED])->save();
        config(['result_engine.promotion_enabled'=>false]);
        $dry=$this->processor()->process(...$this->args($scope,[$student->id],dryRun:true));
        $this->assertTrue($dry['writeSafe']);$this->assertTrue($dry['noRecordsModified']);
        $this->assertBlocked(fn()=> $this->processor()->process(...$this->args($scope,[$student->id])),'PROMOTION_ENGINE_DISABLED');
        $this->assertSame($scope['class']->id,(int)$student->fresh()->className);
    }

    public function test_fail_or_incomplete_blocks_entire_mixed_batch(): void
    {
        foreach (['fail','incomplete'] as $case) {
            $scope=$this->scope();$subject=$this->subject('Main '.$case,'Main',100);
            $pass=$this->student($scope,'01');$blocked=$this->student($scope,'02');
            $this->mark($pass,$scope,$subject,80);
            if($case==='fail')$this->mark($blocked,$scope,$subject,32);
            $this->publish($scope);
            $code=$case==='fail'?'ACADEMIC_STATUS_FAIL':'ACADEMIC_STATUS_INCOMPLETE';
            $this->assertBlocked(fn()=> $this->processor()->process(...$this->args($scope,[$pass->id,$blocked->id])),$code);
            $this->assertSame($scope['class']->id,(int)$pass->fresh()->className);
            $this->assertDatabaseCount('result_archives',0);$this->assertDatabaseCount('promotion_audit_logs',0);
        }
    }

    public function test_roll_conflicts_archive_ambiguity_and_invalid_fourth_subject_block(): void
    {
        $scope=$this->scope();$main=$this->subject('Main','Main',100);$student=$this->student($scope,'07');$this->mark($student,$scope,$main,80);$this->publish($scope);
        newAdmission::create(['stdId'=>99999999,'fullName'=>'Dest','sessName'=>$scope['toSession']->id,'className'=>$scope['toClass']->id,'sectionName'=>$scope['toSection']->id,'rollNumber'=>'07']);
        $this->assertBlocked(fn()=> $this->processor()->process(...$this->args($scope,[$student->id])),'DESTINATION_ROLL_CONFLICT');

        newAdmission::where('stdId',99999999)->delete();
        ResultArchive::create(['student_id'=>$student->id,'old_class'=>$scope['class']->id,'old_session'=>$scope['session']->id,
            'old_section'=>$scope['section']->id,'old_roll'=>'07','exam_id'=>null,'result_data'=>[]]);
        $this->assertBlocked(fn()=> $this->processor()->process(...$this->args($scope,[$student->id])),'AMBIGUOUS_PROMOTION_STATE');
        ResultArchive::query()->delete();

        $invalid=$this->subject('Invalid optional','Main',100);$student->fourthSubjectId=$invalid->id;$student->save();
        $this->assertBlocked(fn()=> $this->processor()->process(...$this->args($scope,[$student->id])),'INVALID_DESTINATION_FOURTH_SUBJECT');
    }

    public function test_explicit_roll_persists_and_exact_rerun_is_blocked(): void
    {
        $scope=$this->scope();$subject=$this->subject('Main','Main',100);$student=$this->student($scope,'01');$this->mark($student,$scope,$subject,80);$this->publish($scope);
        $this->processor()->process(...$this->args($scope,[$student->id],rolls:[$student->id=>'55']));
        $this->assertSame('55',$student->fresh()->rollNumber);
        $this->assertBlocked(fn()=> $this->processor()->process(...$this->args($scope,[$student->id])),'ALREADY_PROMOTED');
        $this->assertDatabaseCount('result_archives',1);$this->assertDatabaseCount('promotion_audit_logs',1);
    }

    public function test_audit_failure_rolls_back_archive_and_student_update(): void
    {
        $scope=$this->scope();$subject=$this->subject('Main','Main',100);$student=$this->student($scope,'01');$mark=$this->mark($student,$scope,$subject,80);$this->publish($scope);
        $service=new class(app(PromotionPreviewBuilder::class),app(ResultCalculationBatchBuilder::class)) extends CentralizedPromotionProcessor {
            protected function insertAudits(array $rows):void{throw new \RuntimeException('Injected audit failure');}
        };
        $this->assertBlocked(fn()=> $service->process(...$this->args($scope,[$student->id])),'PROMOTION_TRANSACTION_FAILED');
        $this->assertSame($scope['class']->id,(int)$student->fresh()->className);
        $this->assertDatabaseCount('result_archives',0);$this->assertDatabaseCount('promotion_audit_logs',0);
        $this->assertSame(80.0,(float)$mark->fresh()->subjectMarks);
    }

    public function test_optional_failure_promotes_and_valid_fourth_and_religious_subjects_are_preserved(): void
    {
        $scope=$this->scope();$main=$this->subject('Main','Main',100);$optional=$this->subject('Optional','Optional',100);
        $religious=$this->subject('Religion','Main',100);$religious->isReligious=true;$religious->save();
        $student=$this->student($scope,'01');$student->fourthSubjectId=$optional->id;$student->religiousSubjectId=$religious->id;$student->save();
        $this->mark($student,$scope,$main,80);$this->mark($student,$scope,$optional,0);$this->mark($student,$scope,$religious,80);$this->publish($scope);

        $this->processor()->process(...$this->args($scope,[$student->id]));

        $student->refresh();
        $this->assertSame($optional->id,(int)$student->fourthSubjectId);
        $this->assertSame($religious->id,(int)$student->religiousSubjectId);
        $this->assertSame('Pass',ResultArchive::where('exam_id',$scope['exam']->id)->firstOrFail()->result_data['status']);
    }

    public function test_required_component_failure_blocks_promotion(): void
    {
        $scope=$this->scope();$scope['exam']->passingSystem=1;$scope['exam']->save();
        $subject=Subject::create(['subjectName'=>'Components','alias'=>'components','subjectType'=>'Main','assign_class'=>'0','CQ'=>50,'MCQ'=>25,'Practical'=>25]);
        $student=$this->student($scope,'01');
        $this->ensureCurriculumMapping($scope, $subject);
        Marksheet::create(['studentId'=>$student->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,
            'groupId'=>$scope['section']->id,'examId'=>$scope['exam']->id,'subjectId'=>$subject->id,
            'subjectMarks'=>30,'objectMarks'=>1,'practicalMarks'=>20,'totalMarks'=>51,'gradePoint'=>3,'laterGrade'=>'B']);
        $this->publish($scope);

        $this->assertBlocked(fn()=> $this->processor()->process(...$this->args($scope,[$student->id])),'ACADEMIC_STATUS_FAIL');
        $this->assertSame($scope['class']->id,(int)$student->fresh()->className);
    }

    public function test_archive_and_student_failure_stages_each_roll_back_everything(): void
    {
        foreach (['archive','student'] as $stage) {
            $scope=$this->scope();$subject=$this->subject('Main '.$stage,'Main',100);$student=$this->student($scope,'01');
            $subject->assign_class=(string)$scope['class']->id;$subject->save();
            $this->mark($student,$scope,$subject,80);$this->publish($scope);
            $service=$stage==='archive'
                ? new class(app(PromotionPreviewBuilder::class),app(ResultCalculationBatchBuilder::class)) extends CentralizedPromotionProcessor {
                    protected function insertArchives(array $rows):void{throw new \RuntimeException('Injected archive failure');}
                }
                : new class(app(PromotionPreviewBuilder::class),app(ResultCalculationBatchBuilder::class)) extends CentralizedPromotionProcessor {
                    protected function saveStudent(newAdmission $student):void{throw new \RuntimeException('Injected student failure');}
                };
            $this->assertBlocked(fn()=> $service->process(...$this->args($scope,[$student->id])),'PROMOTION_TRANSACTION_FAILED');
            $this->assertSame($scope['class']->id,(int)$student->fresh()->className);
            $this->assertDatabaseCount('result_archives',0);$this->assertDatabaseCount('promotion_audit_logs',0);
        }
    }

    public function test_command_dry_run_is_read_only_and_real_write_requires_flag(): void
    {
        $scope=$this->scope();$subject=$this->subject('Main','Main',100);$student=$this->student($scope,'01');$this->mark($student,$scope,$subject,80);$this->publish($scope);
        config(['result_engine.promotion_enabled'=>false]);
        $opts=['--exam'=>$scope['exam']->id,'--class'=>$scope['class']->id,'--session'=>$scope['session']->id,
            '--to-class'=>$scope['toClass']->id,'--to-session'=>$scope['toSession']->id,'--to-section'=>$scope['toSection']->id,
            '--section'=>$scope['section']->id,'--student'=>[$student->id],'--engine'=>'centralized'];
        $this->artisan('students:promote',$opts+['--dry-run'=>true])->assertSuccessful()->expectsOutputToContain('no records were modified');
        $this->artisan('students:promote',$opts)->assertFailed()->expectsOutputToContain('PROMOTION_ENGINE_DISABLED');
        $this->assertSame($scope['class']->id,(int)$student->fresh()->className);
    }

    public function test_feature_enabled_existing_web_confirmation_uses_centralized_processor(): void
    {
        $scope=$this->scope();$subject=$this->subject('Main','Main',100);$student=$this->student($scope,'01');$this->mark($student,$scope,$subject,80);$this->publish($scope);
        $token='phase9-web-token';Session::put('promotion_submit_token',$token);
        $request=Request::create('/student/promotion/confirm','POST',[
            'sessionId'=>$scope['session']->id,'classId'=>$scope['class']->id,'groupId'=>$scope['section']->id,
            'type'=>'sectionwise','promotSession'=>$scope['toSession']->id,'promotId'=>$scope['toClass']->id,
            'promotSection'=>$scope['toSection']->id,'examId'=>$scope['exam']->id,
            'selected_students'=>[$student->id],'roll_numbers'=>[$student->id=>'66'],'submit_token'=>$token,
        ]);
        $request->setLaravelSession(app('session')->driver());

        $response=app(AdmissionController::class)->confirmPromotData($request);

        $this->assertTrue($response->getSession()->has('success'));
        $this->assertSame($scope['toClass']->id,(int)$student->fresh()->className);
        $this->assertSame('66',$student->fresh()->rollNumber);
        $this->assertDatabaseCount('result_archives',1);
        $this->assertDatabaseCount('promotion_audit_logs',1);
    }

    private function processor():CentralizedPromotionProcessor{return app(CentralizedPromotionProcessor::class);}
    private function args(array $s,array $ids,bool $dryRun=false,array $rolls=[]):array{return[$s['exam']->id,$s['class']->id,$s['session']->id,$s['toClass']->id,$s['toSession']->id,$s['toSection']->id,$s['section']->id,null,null,$ids,$rolls,$dryRun,'test'];}
    private function assertBlocked(callable $call,string $code):void{try{$call();$this->fail("Expected {$code}");}catch(PromotionProcessingException $e){$this->assertContains($code,collect($e->report['blockingErrors'])->pluck('code')->all());}}
    private function scope():array{$session=new sessionManage();$session->session='2026';$session->save();$toSession=new sessionManage();$toSession->session='2027';$toSession->save();$class=new classManage();$class->className='8';$class->save();$toClass=new classManage();$toClass->className='11';$toClass->save();$section=new sectionManage();$section->section='A';$section->save();$toSection=new sectionManage();$toSection->section='B';$toSection->save();$exam=new Exam();$exam->examName='Annual';$exam->passingSystem=2;$exam->save();return compact('session','toSession','class','toClass','section','toSection','exam');}
    private function student(array $s,string $roll):newAdmission{return newAdmission::create(['stdId'=>(string)random_int(100000,9999999),'fullName'=>'Student','sessName'=>$s['session']->id,'className'=>$s['class']->id,'sectionName'=>$s['section']->id,'rollNumber'=>$roll]);}
    private function subject(string $n,string $t,float $cq):Subject{return Subject::create(['subjectName'=>$n,'alias'=>strtolower($n),'subjectType'=>$t,'assign_class'=>'0','CQ'=>$cq,'MCQ'=>0,'Practical'=>0]);}
    private function mark(newAdmission $st,array $s,Subject $sub,float $cq,?Exam $exam=null):Marksheet{$this->ensureCurriculumMapping($s,$sub);return Marksheet::create(['studentId'=>$st->id,'classId'=>$s['class']->id,'sessionId'=>$s['session']->id,'groupId'=>$s['section']->id,'examId'=>($exam??$s['exam'])->id,'subjectId'=>$sub->id,'subjectMarks'=>$cq,'totalMarks'=>$cq,'gradePoint'=>$cq>=80?5:0,'laterGrade'=>$cq>=33?'A+':'F']);}
    private function ensureCurriculumMapping(array $s, Subject $sub): void {$exists=DB::table('curriculum_subject_mappings')->where('session_id',(string)$s['session']->id)->where('class_id',(string)$s['class']->id)->where('section_id',(string)$s['section']->id)->whereNull('department_id')->where('subject_id',(int)$sub->id)->exists(); if($exists)return; $next=(int)(DB::table('curriculum_subject_mappings')->where('session_id',(string)$s['session']->id)->where('class_id',(string)$s['class']->id)->where('section_id',(string)$s['section']->id)->whereNull('department_id')->max('sort_order')??0)+1; DB::table('curriculum_subject_mappings')->insert(['session_id'=>(string)$s['session']->id,'class_id'=>(string)$s['class']->id,'section_id'=>(string)$s['section']->id,'department_id'=>null,'subject_id'=>(int)$sub->id,'mapping_type'=>'main','sort_order'=>$next,'is_active'=>1,'source'=>'test-fixture','created_at'=>now(),'updated_at'=>now()]);}
    private function publish(array $s):void{ResultPublish::create(['examId'=>$s['exam']->id,'classId'=>$s['class']->id,'sessionId'=>$s['session']->id,'groupId'=>$s['section']->id]);}
    private function placement(newAdmission $st,array $s):Placement{return Placement::create(['studentId'=>$st->id,'classId'=>$s['class']->id,'sessionId'=>$s['session']->id,'groupId'=>$s['section']->id,'examId'=>$s['exam']->id,'subjectsCount'=>1,'totalGradePoints'=>5,'gpa'=>5,'totalMarks'=>80,'position'=>1,'status'=>'Pass']);}
}

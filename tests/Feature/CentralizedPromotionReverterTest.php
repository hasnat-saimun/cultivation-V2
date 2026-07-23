<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\PromotionAuditLog;
use App\Models\ResultArchive;
use App\Models\ResultPublish;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\ResultCalculation\CentralizedPromotionProcessor;
use App\Services\ResultCalculation\CentralizedPromotionReverter;
use App\Services\ResultCalculation\PromotionRevertException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralizedPromotionReverterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'result_engine.promotion_enabled'=>true,
            'result_engine.promotion_revert_enabled'=>true,
        ]);
    }

    public function test_promotion_persists_exam_aware_cycle_identity_and_database_uniqueness(): void
    {
        [$scope,$student]=$this->promotable();
        $report=$this->promote($scope,$student);
        $archive=ResultArchive::firstOrFail();
        $audit=PromotionAuditLog::firstOrFail();

        $this->assertNotEmpty($report['promotionCycleId']);
        $this->assertSame($report['promotionCycleId'],$archive->promotion_cycle_id);
        $this->assertSame($report['promotionCycleId'],$audit->promotion_cycle_id);
        $this->assertSame($report['promotionCycleId'],$audit->promotion_id);
        $this->assertSame('centralized',$audit->engine);
        $this->assertSame($scope['exam']->id,(int)$audit->exam_id);
        $this->assertNull($audit->reverted_at);

        $duplicate=$archive->replicate();
        $this->expectException(QueryException::class);
        $duplicate->save();
    }

    public function test_legacy_null_cycle_rows_remain_nullable_and_unconstrained(): void
    {
        $scope=$this->scope();
        foreach ([1,2] as $n) {
            ResultArchive::create([
                'student_id'=>99,'old_class'=>$scope['class']->id,'old_session'=>$scope['session']->id,
                'old_section'=>null,'old_roll'=>(string)$n,'exam_id'=>null,
                'promotion_cycle_id'=>null,'result_data'=>['legacy'=>$n],
            ]);
            PromotionAuditLog::create([
                'promotion_id'=>'legacy-'.$n,'student_id'=>99,'promotion_cycle_id'=>null,
            ]);
        }
        $this->assertDatabaseCount('result_archives',2);
        $this->assertDatabaseCount('promotion_audit_logs',2);
        $this->assertTrue(Schema::hasColumns('promotion_audit_logs',[
            'exam_id','promotion_cycle_id','engine','reverted_at','reverted_by','revert_cycle_id','revert_reason',
        ]));
    }

    public function test_valid_revert_restores_source_marks_audit_and_retains_immutable_archive(): void
    {
        [$scope,$student,$mark]=$this->promotable(withMark:true);
        $sourceRoll=$student->getRawOriginal('rollNumber');
        $promotion=$this->promote($scope,$student,['77']);
        $archive=ResultArchive::firstOrFail();
        $archiveBefore=$archive->getAttributes();
        $markBefore=$mark->fresh()->getAttributes();

        $report=app(CentralizedPromotionReverter::class)->process(
            $promotion['promotionCycleId'],[$student->id],false,false,'44','Controlled test'
        );

        $student->refresh();$audit=PromotionAuditLog::firstOrFail();
        $this->assertSame($scope['session']->id,(int)$student->sessName);
        $this->assertSame($scope['class']->id,(int)$student->className);
        $this->assertSame($scope['section']->id,(int)$student->sectionName);
        $this->assertSame($sourceRoll,$student->getRawOriginal('rollNumber'));
        $this->assertNotNull($audit->reverted_at);
        $this->assertSame($report['revertCycleId'],$audit->revert_cycle_id);
        $this->assertSame(44,(int)$audit->reverted_by);
        $this->assertSame('Controlled test',$audit->revert_reason);
        $this->assertSame($archiveBefore,$archive->fresh()->getAttributes());
        $this->assertSame($markBefore,$mark->fresh()->getAttributes());
        $this->assertTrue($report['transactionCommitted']);
    }

    public function test_dry_run_works_while_flag_disabled_and_real_write_refuses(): void
    {
        [$scope,$student]=$this->promotable();
        $promotion=$this->promote($scope,$student);
        config(['result_engine.promotion_revert_enabled'=>false]);

        $dry=app(CentralizedPromotionReverter::class)->process(
            $promotion['promotionCycleId'],[$student->id],false,true
        );
        $this->assertTrue($dry['writeSafe']);
        $this->assertTrue($dry['noRecordsModified']);
        $this->assertBlocked(fn()=>app(CentralizedPromotionReverter::class)->process(
            $promotion['promotionCycleId'],[$student->id]
        ),'PROMOTION_REVERT_ENGINE_DISABLED');
        $this->assertSame($scope['toClass']->id,(int)$student->fresh()->className);
        $this->assertNull(PromotionAuditLog::first()->reverted_at);
    }

    public function test_revert_blocks_changed_destination_missing_archive_source_roll_conflict_and_already_reverted(): void
    {
        foreach (['moved','archive','roll','reverted'] as $case) {
            [$scope,$student]=$this->promotable();
            $promotion=$this->promote($scope,$student);
            if($case==='moved'){DB::table('new_admissions')->where('id',$student->id)->update(['rollNumber'=>'99']);$code='STUDENT_DESTINATION_MISMATCH';}
            if($case==='archive'){ResultArchive::query()->delete();$code='MISSING_ARCHIVE';}
            if($case==='roll'){newAdmission::create(['stdId'=>999000+random_int(1,99),'fullName'=>'Conflict','sessName'=>$scope['session']->id,
                'className'=>$scope['class']->id,'sectionName'=>$scope['section']->id,'rollNumber'=>$student->getRawOriginal('rollNumber')]);$code='SOURCE_ROLL_CONFLICT';}
            if($case==='reverted'){PromotionAuditLog::query()->update(['reverted_at'=>now(),'revert_cycle_id'=>'prior']);$code='ALREADY_REVERTED';}
            $this->assertBlocked(fn()=>app(CentralizedPromotionReverter::class)->process(
                $promotion['promotionCycleId'],[$student->id]
            ),$code);
            $this->assertSame(0,PromotionAuditLog::whereNotNull('reverted_at')->where('revert_cycle_id','!=','prior')->count());
        }
    }

    public function test_student_or_audit_failure_rolls_back_entire_revert(): void
    {
        foreach (['student','audit'] as $stage) {
            [$scope,$student]=$this->promotable();
            $promotion=$this->promote($scope,$student);
            $service=$stage==='student'
                ? new class extends CentralizedPromotionReverter {
                    protected function saveStudent(newAdmission $student):void{throw new \RuntimeException('student');}
                }
                : new class extends CentralizedPromotionReverter {
                    protected function markAuditsReverted(string $cycle,array $ids,string $revert,string|int|null $actor,?string $reason):int{throw new \RuntimeException('audit');}
                };
            $this->assertBlocked(fn()=>$service->process($promotion['promotionCycleId'],[$student->id]),'PROMOTION_REVERT_TRANSACTION_FAILED');
            $this->assertSame($scope['toClass']->id,(int)$student->fresh()->className);
            $this->assertNull(PromotionAuditLog::first()->reverted_at);
        }
    }

    public function test_successful_revert_allows_repromotion_with_new_cycle_and_old_evidence_retained(): void
    {
        [$scope,$student]=$this->promotable();
        $first=$this->promote($scope,$student);
        app(CentralizedPromotionReverter::class)->process($first['promotionCycleId'],[$student->id]);
        $second=$this->promote($scope,$student);

        $this->assertNotSame($first['promotionCycleId'],$second['promotionCycleId']);
        $this->assertDatabaseCount('result_archives',2);
        $this->assertDatabaseCount('promotion_audit_logs',2);
        $this->assertNotNull(PromotionAuditLog::where('promotion_cycle_id',$first['promotionCycleId'])->first()->reverted_at);
        $this->assertNull(PromotionAuditLog::where('promotion_cycle_id',$second['promotionCycleId'])->first()->reverted_at);
        $this->assertSame($scope['toClass']->id,(int)$student->fresh()->className);
    }

    public function test_revert_command_dry_run_and_real_flow_use_cycle_identity(): void
    {
        [$scope,$student]=$this->promotable();
        $promotion=$this->promote($scope,$student);
        config(['result_engine.promotion_revert_enabled'=>false]);
        $options=['--promotion-cycle'=>$promotion['promotionCycleId'],'--student'=>[$student->id],'--engine'=>'centralized'];
        $this->artisan('students:promotion-revert',$options+['--dry-run'=>true])
            ->assertSuccessful()->expectsOutputToContain('no records were modified');
        $this->artisan('students:promotion-revert',$options)
            ->assertFailed()->expectsOutputToContain('PROMOTION_REVERT_ENGINE_DISABLED');
        config(['result_engine.promotion_revert_enabled'=>true]);
        $this->artisan('students:promotion-revert',$options)->assertSuccessful();
        $this->assertSame($scope['class']->id,(int)$student->fresh()->className);
    }

    private function promotable(bool $withMark=false): array
    {
        $scope=$this->scope();
        $subject=Subject::create(['subjectName'=>'Main '.$scope['class']->id,'alias'=>'main-'.$scope['class']->id,
            'subjectType'=>'Main','assign_class'=>(string)$scope['class']->id,'CQ'=>100,'MCQ'=>0,'Practical'=>0]);
        $student=newAdmission::create(['stdId'=>(string)random_int(100000,9999999),'fullName'=>'Student','sessName'=>$scope['session']->id,
            'className'=>$scope['class']->id,'sectionName'=>$scope['section']->id,'rollNumber'=>(string)random_int(1000,999999)]);
        $mark=Marksheet::create(['studentId'=>$student->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,
            'groupId'=>$scope['section']->id,'examId'=>$scope['exam']->id,'subjectId'=>$subject->id,
            'subjectMarks'=>80,'totalMarks'=>80,'gradePoint'=>5,'laterGrade'=>'A+']);
        ResultPublish::create(['examId'=>$scope['exam']->id,'classId'=>$scope['class']->id,
            'sessionId'=>$scope['session']->id,'groupId'=>$scope['section']->id]);
        return $withMark?[$scope,$student,$mark]:[$scope,$student];
    }

    private function promote(array $scope,newAdmission $student,array $roll=[]): array
    {
        $rolls=$roll===[]?[]:[$student->id=>$roll[0]];
        return app(CentralizedPromotionProcessor::class)->process(
            $scope['exam']->id,$scope['class']->id,$scope['session']->id,
            $scope['toClass']->id,$scope['toSession']->id,$scope['toSection']->id,
            $scope['section']->id,null,null,[$student->id],$rolls,false,'test'
        );
    }

    private function assertBlocked(callable $call,string $code): void
    {
        try{$call();$this->fail("Expected {$code}");}
        catch(PromotionRevertException $e){$this->assertContains($code,collect($e->report['blockingErrors'])->pluck('code')->all());}
    }

    private function scope(): array
    {
        $session=new sessionManage();$session->session='2026';$session->save();
        $toSession=new sessionManage();$toSession->session='2027';$toSession->save();
        $class=new classManage();$class->className='10';$class->save();
        $toClass=new classManage();$toClass->className='11';$toClass->save();
        $section=new sectionManage();$section->section='A';$section->save();
        $toSection=new sectionManage();$toSection->section='B';$toSection->save();
        $exam=new Exam();$exam->examName='Annual';$exam->passingSystem=2;$exam->save();
        return compact('session','toSession','class','toClass','section','toSection','exam');
    }
}

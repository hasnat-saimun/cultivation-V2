<?php
namespace App\Services;
use App\Models\Subject;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
use RuntimeException;
final class SubjectScopeSplitService {
 public function __construct(private SubjectReferenceDiscoveryService $discovery){}
 public function execute(int $sourceId,?int $destinationId,array $remain,array $migrate,bool $apply=false,?string $actor=null,bool $createDestination=false): array {
  $remain=$this->ids($remain);$migrate=$this->ids($migrate);
  if(array_intersect($remain,$migrate)||!$migrate)throw new RuntimeException('Remain and migrate classes must be non-overlapping and migrate classes cannot be empty.');
  $requested=array_values(array_unique(array_merge($remain,$migrate)));if(DB::table('class_manages')->whereIn('id',$requested)->count()!==count($requested))throw new RuntimeException('One or more selected classes do not exist.');
  $source=Subject::findOrFail($sourceId);
  if($destinationId===null&&!$createDestination)throw new RuntimeException('Select a destination subject or use --create-destination.');
  $destination=$destinationId===null?$source->replicate():Subject::findOrFail($destinationId);
  $this->compatible($source,$destination);$this->assertName($source,$destination,$remain,$migrate);
  $references=$this->discovery->discover();$counts=[];$blockers=[];
  foreach($references as $ref){
   if(in_array($ref['table'],['subjects','subject_class_scopes','subject_scope_migration_audits'],true))continue;
   if($ref['json_payload']??false){$q=$this->jsonQuery($ref,$sourceId,$migrate);$counts[$ref['table'].'.'.$ref['column']]=$q->count();if(!$ref['class_column']&&$q->exists())$blockers[]=$ref;continue;}
   $q=$this->referenceQuery($ref,$sourceId,$migrate);
   if(!$ref['class_column']&&!($ref['scope_join']??null)&&$q->exists()){$blockers[]=$ref;continue;}
   $counts[$ref['table'].'.'.$ref['column']]=$q->count();
  }
  if($blockers)throw new RuntimeException('Class-less subject references require manual resolution: '.collect($blockers)->map(fn($r)=>$r['table'].'.'.$r['column'])->implode(', '));
  $report=compact('sourceId','destinationId','remain','migrate','references','counts','apply');
  if(!$apply)return $report;
  return DB::transaction(function()use($source,$destination,$remain,$migrate,$references,$counts,$actor,$report,$createDestination){
   Subject::whereKey(array_filter([$source->id,$destination->id]))->lockForUpdate()->get();
   if($createDestination&&!$destination->exists){$destination->assign_class='';$destination->save();}
   foreach($references as $ref){if(in_array($ref['table'],['subjects','subject_class_scopes','subject_scope_migration_audits'],true)||(!$ref['class_column']&&!($ref['scope_join']??null)))continue;
    if($ref['json_payload']??false){foreach($this->jsonQuery($ref,$source->id,$migrate)->get(['id',$ref['column']]) as $row){$payload=json_decode((string)$row->{$ref['column']},true);DB::table($ref['table'])->where('id',$row->id)->update([$ref['column']=>json_encode($this->replaceJsonSubject($payload,$source->id,$destination->id))]);}continue;}
    $q=$this->referenceQuery($ref,$source->id,$migrate);
    if($ref['table']==='marksheets')$this->assertNoMarkCollision($q,$destination->id);
    $q->update([$ref['column']=>$destination->id]);
   }
   $this->writeScopes($source,$remain);$this->writeScopes($destination,$migrate);
   $operationUuid=(string)Str::uuid();
   DB::table('subject_scope_migration_audits')->insert(['operation_uuid'=>$operationUuid,'source_subject_id'=>$source->id,'destination_subject_id'=>$destination->id,'remain_class_ids'=>json_encode($remain),'migrate_class_ids'=>json_encode($migrate),'discovered_references'=>json_encode($references),'affected_counts'=>json_encode($counts),'actor'=>$actor,'applied_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
   return array_merge($report,['destinationId'=>$destination->id,'operation_uuid'=>$operationUuid]);
  });
 }
 private function assertNoMarkCollision($query,int $destination):void {foreach((clone $query)->get() as $row){$exists=DB::table('marksheets')->where('studentId',$row->studentId)->where('sessionId',$row->sessionId)->where('classId',$row->classId)->where('normalizedGroupScope',$row->normalizedGroupScope)->where('examId',$row->examId)->where('subjectId',$destination)->exists();if($exists)throw new RuntimeException('Destination marksheet identity already exists; no overwrite performed.');}}
 private function referenceQuery(array $ref,int $source,array $classes){$q=DB::table($ref['table'])->where($ref['column'],$source);if($ref['class_column'])return $q->whereIn($ref['class_column'],$classes);if($join=$ref['scope_join']??null){$ids=DB::table($ref['table'].' as target')->join($join['table'].' as scope','scope.'.$join['foreign_column'],'=','target.'.$join['local_column'])->where('target.'.$ref['column'],$source)->whereIn('scope.'.$join['class_column'],$classes)->pluck('target.id');return $q->whereIn('id',$ids);}return $q;}
 private function jsonQuery(array $ref,int $source,array $classes){$q=DB::table($ref['table'])->where(function($x)use($ref,$source){foreach(['subject_id','subjectId','fourthSubjectId','religiousSubjectId'] as $key)$x->orWhere($ref['column'],'like','%"'.$key.'":'.$source.'%')->orWhere($ref['column'],'like','%"'.$key.'":"'.$source.'"%');});if($ref['class_column'])$q->whereIn($ref['class_column'],$classes);return $q;}
 private function replaceJsonSubject(mixed $value,int $source,int $destination,?string $key=null):mixed {if(is_array($value)){foreach($value as $k=>$v)$value[$k]=$this->replaceJsonSubject($v,$source,$destination,(string)$k);return $value;}return in_array($key,['subject_id','subjectId','fourthSubjectId','religiousSubjectId'],true)&&(string)$value===(string)$source?$destination:$value;}
 private function compatible(Subject $a,Subject $b):void {foreach(['subjectType','passingSystem','CQ','MCQ','Practical','isReligious'] as $f)if((string)$a->$f!==(string)$b->$f)throw new RuntimeException("Incompatible subject configuration: {$f}.");if($this->pairKey($a)!==$this->pairKey($b))throw new RuntimeException('Incompatible paired-subject setup.');}
 private function pairKey(Subject $s):?string {$ids=config('subject_pairs.ids',[]);$aliases=config('subject_pairs.aliases',[]);$names=config('subject_pairs.names',[]);return $ids[$s->id]??$aliases[strtolower(trim((string)$s->alias))]??$names[(string)$s->subjectName]??null;}
 private function assertName(Subject $a,Subject $b,array $remain,array $migrate):void {if($this->name($a->subjectName)!==$this->name($b->subjectName))throw new RuntimeException('Source and destination normalized subject names must match.');$all=array_merge($remain,$migrate);$others=Subject::whereNotIn('id',[$a->id,$b->id])->get()->filter(fn($s)=>$this->name($s->subjectName)===$this->name($a->subjectName));foreach($others as $s)if(array_intersect($all,$this->legacyScopes($s)))throw new RuntimeException('Overlapping active class scope exists for the normalized subject name.');}
 private function writeScopes(Subject $s,array $ids):void {DB::table('subject_class_scopes')->where('subject_id',$s->id)->delete();foreach($ids as $id)DB::table('subject_class_scopes')->insert(['subject_id'=>$s->id,'class_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);$s->assign_class=implode(',',$ids);$s->save();}
 private function legacyScopes(Subject $s):array {$v=trim((string)$s->assign_class);return $v===''?[]:($v==='0'?$this->ids(['all']):$this->ids(explode(',',$v)));}
 private function name(string $v):string{return mb_strtolower(preg_replace('/\s+/u',' ',trim($v)));}
 private function ids(array $v):array{if(collect($v)->contains(fn($x)=>strtolower(trim((string)$x))==='all'))return DB::table('class_manages')->orderBy('id')->pluck('id')->map(fn($x)=>(int)$x)->all();return collect($v)->filter(fn($x)=>is_numeric($x)&&(int)$x>0)->map(fn($x)=>(int)$x)->unique()->sort()->values()->all();}
}

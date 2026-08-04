<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
final class SubjectReferenceDiscoveryService {
 public function discover(): array {
  $schema=DB::getDatabaseName();
  $columns=DB::table('information_schema.columns')->where('table_schema',$schema)->orderBy('table_name')->orderBy('ordinal_position')->get(['table_name','column_name','data_type'])->groupBy('table_name');
  $foreignKeys=DB::table('information_schema.key_column_usage')->where('table_schema',$schema)->whereNotNull('referenced_table_name')->get(['table_name','column_name','referenced_table_name','referenced_column_name'])->groupBy('table_name');
  $refs=[];
  foreach($columns as $table=>$items){
   $names=$items->pluck('column_name')->all();
   $subjectCols=array_values(array_intersect($names,['subject_id','subjectId','fourthSubjectId','religiousSubjectId']));
   $classCol=collect(['class_id','classId','className','assignClass'])->first(fn($c)=>in_array($c,$names,true));
   $scopeJoin=null;
   if(!$classCol)foreach($foreignKeys->get($table,collect()) as $fk){$parentNames=$columns->get($fk->referenced_table_name,collect())->pluck('column_name')->all();$parentClass=collect(['class_id','classId','className','assignClass'])->first(fn($c)=>in_array($c,$parentNames,true));if($parentClass){$scopeJoin=['local_column'=>$fk->column_name,'table'=>$fk->referenced_table_name,'foreign_column'=>$fk->referenced_column_name,'class_column'=>$parentClass];break;}}
   foreach($subjectCols as $column)$refs[]=['table'=>$table,'column'=>$column,'class_column'=>$classCol,'scope_join'=>$scopeJoin];
   foreach($items as $item)if(preg_match('/(result|archive|lifecycle)/i',$table)&&in_array($item->data_type,['json','text','longtext','mediumtext'],true)&&preg_match('/(result|payload|state|data|change|evidence)/i',$item->column_name))$refs[]=['table'=>$table,'column'=>$item->column_name,'class_column'=>$classCol,'json_payload'=>true];
  }
  return $refs;
 }
}

<?php
namespace App\Console\Commands;
use App\Services\SubjectScopeSplitService;
use Illuminate\Console\Command;
final class SplitSubjectScope extends Command {
 protected $signature='subject:split-scope {source} {destination?} {--create-destination} {--remain=*} {--migrate=*} {--apply} {--actor=cli}';
 protected $description='Dry-run or apply a transactional class-scoped subject split';
 public function handle(SubjectScopeSplitService $service):int {try{$destination=$this->argument('destination');$r=$service->execute((int)$this->argument('source'),$destination===null?null:(int)$destination,$this->option('remain'),$this->option('migrate'),(bool)$this->option('apply'),$this->option('actor'),(bool)$this->option('create-destination'));$this->line(json_encode($r,JSON_PRETTY_PRINT));$this->info($this->option('apply')?'Applied. Source was preserved.':'Dry-run only. Use --apply to commit.');return self::SUCCESS;}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}}
}

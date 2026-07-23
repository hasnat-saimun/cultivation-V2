<?php

namespace App\Console\Commands;

use App\Services\ResultCalculation\CentralizedPromotionProcessor;
use App\Services\ResultCalculation\PromotionProcessingException;
use Illuminate\Console\Command;

class PromoteStudents extends Command
{
    protected $signature = 'students:promote
        {--exam= : Required selected exam ID}
        {--class= : Required source class ID}
        {--session= : Required source session ID}
        {--to-class= : Required destination class ID}
        {--to-session= : Required destination session ID}
        {--to-section= : Required destination section ID}
        {--section= : Optional source section/group ID}
        {--group= : Alias for source section/group ID}
        {--department= : Optional source department ID}
        {--to-department= : Optional destination department ID}
        {--student=* : Explicit selected student database ID; repeatable}
        {--roll=* : Optional studentId=roll assignment; repeatable}
        {--engine=centralized : Must be centralized}
        {--dry-run : Validate and build payloads without writing}
        {--all : Explicitly select the complete source scope}';

    protected $description = 'Dry-run or execute selected-exam centralized student promotion.';

    public function handle(CentralizedPromotionProcessor $processor): int
    {
        if (strtolower((string)$this->option('engine')) !== 'centralized') {
            $this->error('--engine must be centralized.');
            return self::INVALID;
        }
        foreach (['exam','class','session','to-class','to-session','to-section'] as $required) {
            if (!$this->positive($this->option($required))) {
                $this->error('--exam, --class, --session, --to-class, --to-session and --to-section are required positive IDs.');
                return self::INVALID;
            }
        }
        $selected = collect($this->option('student'))->filter(fn ($id) => $this->positive($id))->map(fn ($id) => (int)$id)->all();
        if ($this->option('all')) {
            $selected = \App\Models\newAdmission::query()->where('className',(int)$this->option('class'))
                ->where('sessName',(int)$this->option('session'))
                ->when($this->id('section') ?? $this->id('group'), fn ($query, $section) => $query->where('sectionName',$section))
                ->when($this->id('department'), fn ($query, $department) => $query->where('departmentName',$department))
                ->pluck('id')->map(fn ($id)=>(int)$id)->all();
        }
        $rolls = [];
        foreach ($this->option('roll') as $assignment) {
            if (str_contains((string)$assignment, '=')) {
                [$studentId,$roll] = explode('=',(string)$assignment,2);
                if ($this->positive($studentId)) $rolls[(int)$studentId] = $roll;
            }
        }
        try {
            $report = $processor->process(
                (int)$this->option('exam'),(int)$this->option('class'),(int)$this->option('session'),
                (int)$this->option('to-class'),(int)$this->option('to-session'),(int)$this->option('to-section'),
                $this->id('section') ?? $this->id('group'),$this->id('department'),$this->id('to-department'),
                $selected,$rolls,(bool)$this->option('dry-run'),'artisan'
            );
            $this->table(
                ['Cycle','Checked','Pass','Published','Archive safe','Would promote','Would archive','Would update','Would audit','Promoted','Archives','Updated','Audits'],
                [[$report['promotionCycleId'],$report['studentsChecked'],$report['passStudents'],$report['published']?'yes':'no',$report['archiveSafeStudents'],
                    $report['wouldPromoteCount'],$report['wouldCreateArchiveCount'],$report['wouldUpdateStudentCount'],$report['wouldCreateAuditCount'],
                    $report['studentsPromoted'],$report['archivesCreated'],$report['studentsUpdated'],$report['auditsCreated']]]
            );
            if ($report['dryRun']) $this->info('Dry-run complete: no records were modified. Real write safe: '.($report['writeSafe']?'yes':'no'));
            else $this->info('Centralized promotion committed successfully.');
            return self::SUCCESS;
        } catch (PromotionProcessingException $exception) {
            foreach ($exception->report['blockingErrors'] as $error) {
                $this->error($error['code'].': '.$error['message'].(isset($error['studentId'])?' [student '.$error['studentId'].']':''));
            }
            $this->error('No promotion records were modified.');
            return self::FAILURE;
        }
    }

    private function id(string $name): ?int { return $this->positive($this->option($name))?(int)$this->option($name):null; }
    private function positive(mixed $value): bool { return is_numeric($value) && (int)$value > 0; }
}

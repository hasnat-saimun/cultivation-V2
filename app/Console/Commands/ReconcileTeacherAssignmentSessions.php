<?php

namespace App\Console\Commands;

use App\Models\CultivationAdmin;
use App\Services\TeacherAssignmentSessionReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ReconcileTeacherAssignmentSessions extends Command
{
    protected $signature = 'teacher-assignment:reconcile-sessions
        {--assignment=* : Limit to one or more null-session assignment IDs}
        {--session= : Explicit session ID for the selected assignment IDs}
        {--execute : Apply validated proposals; default is read-only dry-run}
        {--actor= : General/Super Admin ID required for execution}
        {--backup= : Path to a non-empty database backup required for execution}';

    protected $description = 'Audit and safely reconcile legacy teacher assignments that have no academic session.';

    public function handle(TeacherAssignmentSessionReconciliationService $service): int
    {
        $audit = $service->audit();
        $requestedIds = collect($this->option('assignment'))
            ->filter(fn ($id) => ctype_digit((string) $id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique();
        if ($requestedIds->isNotEmpty()) {
            $audit = $audit->whereIn('assignment_id', $requestedIds)->values();
        }

        $this->table(
            ['ID', 'Teacher', 'Class', 'Section', 'Department', 'Subject', 'Gender', 'Created', 'Plausible sessions', 'Resolution'],
            $audit->map(fn ($row) => [
                $row['assignment_id'],
                $row['teacher'],
                $row['class'],
                $row['section'],
                $row['department'],
                $row['subject'],
                $row['gender'],
                $row['created_at'],
                implode(',', $row['plausible_sessions']),
                $row['resolution'],
            ])->all()
        );

        $conclusive = $audit->where('resolution', 'conclusive')->count();
        $ambiguous = $audit->where('resolution', 'ambiguous')->count();
        $this->line("Null-session assignments: {$audit->count()}; conclusive: {$conclusive}; ambiguous: {$ambiguous}.");

        $explicitSession = $this->option('session');
        if ($explicitSession !== null && (!ctype_digit((string) $explicitSession) || (int) $explicitSession <= 0)) {
            $this->error('The --session value must be a positive session ID.');
            return self::FAILURE;
        }
        if ($explicitSession !== null && $requestedIds->isEmpty()) {
            $this->error('--session requires at least one explicit --assignment ID.');
            return self::FAILURE;
        }

        $proposals = $audit->map(function ($row) use ($explicitSession) {
            $row['selected_session_id'] = $explicitSession !== null
                ? (int) $explicitSession
                : $row['proposed_session_id'];
            return $row;
        })->filter(fn ($row) => $row['selected_session_id'] !== null)->values();

        if (!$this->option('execute')) {
            $this->info("DRY-RUN: {$proposals->count()} assignment(s) have a proposed session; no data was modified.");
            if ($ambiguous > 0) {
                $this->warn('Ambiguous rows remain unresolved. Select them explicitly with --assignment and --session after evidence review.');
            }
            return self::SUCCESS;
        }

        $actorId = $this->option('actor');
        $backup = (string) $this->option('backup');
        if (!ctype_digit((string) $actorId) || !$backup) {
            $this->error('Execution requires --actor=<admin-id> and --backup=<verified-backup-path>.');
            return self::FAILURE;
        }
        $actor = CultivationAdmin::find((int) $actorId);
        if (!$actor) {
            $this->error('The reconciliation actor does not exist.');
            return self::FAILURE;
        }

        $updated = 0;
        foreach ($proposals as $proposal) {
            try {
                $service->reconcile(
                    (int) $proposal['assignment_id'],
                    (int) $proposal['selected_session_id'],
                    $actor,
                    $backup,
                );
                $updated++;
                $this->info("Reconciled assignment {$proposal['assignment_id']} to session {$proposal['selected_session_id']}.");
            } catch (ValidationException $exception) {
                $this->error("Assignment {$proposal['assignment_id']} was not changed: ".collect($exception->errors())->flatten()->first());
                return self::FAILURE;
            }
        }

        $this->info("Execution complete: {$updated} assignment(s) reconciled; {$ambiguous} ambiguous row(s) remain unless explicitly selected.");
        return self::SUCCESS;
    }
}

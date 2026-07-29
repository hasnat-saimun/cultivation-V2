<?php

namespace App\Services\ResultCalculation;

use App\Models\MarksScopeState;
use App\Models\ResultLifecycleEvent;
use Illuminate\Support\Collection;

final class ConfirmedBlankOverrideResolver
{
    public function annotate(iterable $students): void
    {
        $students = collect($students);
        $marks = $students->flatMap(fn ($student) => $student->marksheet ?? collect())->values();
        if ($marks->isEmpty()) {
            return;
        }

        $sessionIds = $marks->pluck('sessionId')->map(fn ($id) => (string) $id)->unique();
        $classIds = $marks->pluck('classId')->map(fn ($id) => (string) $id)->unique();
        $examIds = $marks->pluck('examId')->map(fn ($id) => (string) $id)->unique();
        $subjectIds = $marks->pluck('subjectId')->map(fn ($id) => (string) $id)->unique();

        $states = MarksScopeState::query()
            ->whereIn('sessionId', $sessionIds)
            ->whereIn('classId', $classIds)
            ->whereIn('examId', $examIds)
            ->whereIn('subjectId', $subjectIds)
            ->get()
            ->keyBy(fn ($state) => $this->key($state));

        $events = ResultLifecycleEvent::query()
            ->where('action', 'subject_confirmed')
            ->whereIn('sessionId', $sessionIds)
            ->whereIn('classId', $classIds)
            ->whereIn('examId', $examIds)
            ->whereIn('subjectId', $subjectIds)
            ->orderBy('id')
            ->get()
            ->keyBy(fn ($event) => $this->key($event));

        foreach ($marks as $mark) {
            $key = $this->key($mark);
            $state = $states->get($key);
            $event = $events->get($key);
            $accepted = $state?->status === MarksScopeState::STATUS_CONFIRMED
                && (bool) data_get($event?->change_set, 'blank_override', false);
            $mark->setAttribute('component_scope_tracked', $state !== null);
            $mark->setAttribute('confirmed_blank_override', $accepted);
        }
    }

    private function key(object $record): string
    {
        return implode(':', [
            (string) ($record->sessionId ?? ''),
            (string) ($record->classId ?? ''),
            $record->groupId === null || $record->groupId === '' ? 'class' : (string) $record->groupId,
            (string) ($record->examId ?? ''),
            (string) ($record->subjectId ?? ''),
        ]);
    }
}

<?php

namespace App\Services;

use App\Exceptions\ResultPublicationException;
use App\Models\MarksScopeState;
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use Illuminate\Support\Collection;

class ResultPublicationReadinessService
{
    public function __construct(
        private ResultCalculationBatchBuilder $results,
        private ResultPublicationScopeService $scopes,
    ) {}

    public function prepare(array $scope, bool $allowNonReady = false): array
    {
        try {
            $batch = $scope['groupId'] === null
                ? $this->results->buildSectionless($scope['examId'], $scope['classId'], $scope['sessionId'])
                : $this->results->buildPublicationScope(
                    $scope['examId'], $scope['classId'], $scope['sessionId'], $scope['groupId']
                );
        } catch (\Throwable $exception) {
            throw ResultPublicationException::invalid(
                'PublicationIncomplete',
                'Authoritative result preparation failed for the publication scope.',
                [['issue' => 'calculation_failed', 'count' => 1]],
            );
        }

        if ($batch['students']->isEmpty()) {
            throw ResultPublicationException::invalid(
                'PublicationIncomplete',
                'The publication scope contains no applicable students.',
                [['issue' => 'missing_students', 'count' => 1]],
            );
        }

        $subjectIds = collect($batch['entries'])
            ->flatMap(fn ($entry) => $entry['subjects']->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->sort()
            ->values();
        if ($subjectIds->isEmpty()) {
            throw ResultPublicationException::invalid(
                'PublicationIncomplete',
                'No applicable subject configuration was found.',
                [['issue' => 'missing_subject_configuration', 'count' => 1]],
            );
        }

        $states = MarksScopeState::query()
            ->where('sessionId', (string) $scope['sessionId'])
            ->where('classId', (string) $scope['classId'])
            ->where('examId', (string) $scope['examId'])
            ->when($scope['groupId'] === null,
                fn ($query) => $query->whereNull('groupId'),
                fn ($query) => $query->where('groupId', (string) $scope['groupId']))
            ->whereIn('subjectId', $subjectIds->map(fn ($id) => (string) $id))
            ->get()
            ->keyBy(fn ($state) => (int) $state->subjectId);

        $unconfirmed = $subjectIds->filter(
            fn ($subjectId) => $states->get($subjectId)?->status !== MarksScopeState::STATUS_CONFIRMED
        )->values();
        $incomplete = collect($batch['entries'])->filter(
            fn ($entry) => $entry['result']->status === 'Incomplete'
        )->keys()->values();
        $subjectNames = collect($batch['entries'])
            ->flatMap(fn ($entry) => $entry['subjects'])
            ->mapWithKeys(fn ($subject) => [
                (int) $subject->id => (string) ($subject->subjectName ?: 'Subject '.$subject->id),
            ]);
        $nonReadyScopes = $unconfirmed->map(function ($subjectId) use ($states, $subjectNames) {
            $state = $states->get($subjectId);
            return [
                'scope' => (string) ($subjectNames->get($subjectId) ?: 'Subject '.$subjectId),
                'subjectId' => (int) $subjectId,
                'status' => $state === null
                    ? 'Missing'
                    : ($state->status === MarksScopeState::STATUS_DRAFT ? 'Draft' : 'Not Confirmed'),
                'reason' => $state === null
                    ? 'No lifecycle scope exists.'
                    : 'The subject scope is not Confirmed.',
            ];
        })->values();
        foreach ($incomplete as $studentId) {
            $nonReadyScopes->push([
                'scope' => 'Student '.$studentId,
                'studentId' => (int) $studentId,
                'status' => 'Missing',
                'reason' => 'Required result evidence is incomplete.',
            ]);
        }

        if (!$allowNonReady && $nonReadyScopes->isNotEmpty()) {
            $details = [];
            if ($unconfirmed->isNotEmpty()) {
                $details[] = [
                    'issue' => 'unconfirmed_subject_scopes',
                    'count' => $unconfirmed->count(),
                    'subjectIds' => $unconfirmed->all(),
                ];
            }
            if ($incomplete->isNotEmpty()) {
                $details[] = ['issue' => 'incomplete_students', 'count' => $incomplete->count()];
            }
            $details[] = [
                'issue' => 'non_ready_scopes',
                'count' => $nonReadyScopes->count(),
                'scopes' => $nonReadyScopes->all(),
            ];
            throw ResultPublicationException::invalid(
                $unconfirmed->isNotEmpty() ? 'PublicationUnconfirmed' : 'PublicationIncomplete',
                'The result scope is not ready for publication.',
                $details,
            );
        }

        $revisions = $subjectIds
            ->filter(fn ($subjectId) => $states->get($subjectId)?->status === MarksScopeState::STATUS_CONFIRMED)
            ->mapWithKeys(fn ($subjectId) => [(string) $subjectId => (int) $states->get($subjectId)->revision])
            ->all();
        $outcomes = collect($batch['entries'])->countBy(fn ($entry) => $entry['result']->status)->all();

        return [
            'scope' => $scope,
            'scope_key' => $this->scopes->key($scope),
            'student_count' => $batch['students']->count(),
            'subject_count' => $subjectIds->count(),
            'subject_revisions' => $revisions,
            'outcomes' => $outcomes,
            'non_ready_scopes' => $nonReadyScopes->all(),
        ];
    }

    /** @return Collection<int,array> */
    public function prepareAll(Collection $scopes, bool $allowNonReady = false): Collection
    {
        return $scopes->map(fn ($scope) => $this->prepare($scope, $allowNonReady));
    }

    public function lockAndAssertSubjectStates(Collection $evidence): void
    {
        $requirements = $evidence->flatMap(function ($item) {
            return collect($item['subject_revisions'])->map(function ($revision, $subjectId) use ($item) {
                return $item['scope'] + ['subjectId' => (int) $subjectId, 'revision' => (int) $revision];
            });
        })->sortBy(fn ($item) => implode(':', [
            $item['sessionId'], $item['classId'], $item['groupId'] ?? 'class',
            $item['examId'], $item['subjectId'],
        ]))->values();

        foreach ($requirements as $required) {
            $state = MarksScopeState::query()
                ->where('sessionId', (string) $required['sessionId'])
                ->where('classId', (string) $required['classId'])
                ->where('examId', (string) $required['examId'])
                ->where('subjectId', (string) $required['subjectId'])
                ->when($required['groupId'] === null,
                    fn ($query) => $query->whereNull('groupId'),
                    fn ($query) => $query->where('groupId', (string) $required['groupId']))
                ->lockForUpdate()
                ->first();
            if (!$state || $state->status !== MarksScopeState::STATUS_CONFIRMED) {
                throw ResultPublicationException::invalid(
                    'PublicationUnconfirmed',
                    'A required subject scope is no longer Confirmed.'
                );
            }
            if ((int) $state->revision !== $required['revision']) {
                throw ResultPublicationException::conflict(
                    'PublicationRevisionConflict',
                    'Confirmed marks changed during publication readiness validation.'
                );
            }
        }
    }
}

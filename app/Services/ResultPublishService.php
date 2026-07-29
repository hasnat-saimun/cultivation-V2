<?php

namespace App\Services;

use App\Exceptions\ResultPublicationException;
use App\Models\CultivationAdmin;
use App\Models\ResultPublish;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResultPublishService
{
    public function __construct(
        private ResultPublicationScopeService $scopes,
        private ResultPublicationReadinessService $readiness,
        private ResultLifecycleEventService $events,
    ) {}

    public function publish(array $input, CultivationAdmin $actor, ?string $ipAddress = null): array
    {
        $this->scopes->assertActor($actor);
        $scopes = $this->scopes->resolve($input);
        $states = $scopes->mapWithKeys(fn ($scope) => [
            $this->scopes->key($scope) => $this->publicationQuery($scope)->first(),
        ]);

        $pending = collect();
        $idempotent = collect();
        foreach ($scopes as $scope) {
            $key = $this->scopes->key($scope);
            $state = $states[$key];
            $submitted = $this->submittedRevision($input, $key);
            if ($state?->status === ResultPublish::STATUS_PUBLISHED) {
                if ($submitted === (int) $state->revision || ($state->legacyImported && $submitted === null)) {
                    $idempotent->push($this->response($scope, $state, true));
                    continue;
                }
                throw ResultPublicationException::conflict(
                    'PublicationRevisionConflict',
                    'The publication is already Published at a different revision.'
                );
            }
            if ($state) {
                if ($submitted === null || $submitted !== (int) $state->revision) {
                    throw ResultPublicationException::conflict(
                        'PublicationRevisionConflict',
                        'A current publication revision is required to republish.'
                    );
                }
            } elseif ($submitted !== null) {
                throw ResultPublicationException::conflict(
                    'PublicationRevisionConflict',
                    'No publication row exists; do not submit a fabricated revision.'
                );
            }
            $pending->push($scope);
        }

        if ($pending->isEmpty()) {
            return ['success' => true, 'publications' => $idempotent->values()->all(), 'idempotent' => true];
        }
        $confirmAnyway = filter_var($input['confirm_anyway'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $evidence = $this->readiness->prepareAll($pending, $confirmAnyway);

        try {
            $published = DB::transaction(function () use (
                $input, $actor, $ipAddress, $scopes, $pending, $evidence, $idempotent, $confirmAnyway
            ) {
                $locked = collect();
                foreach ($scopes as $scope) {
                    $key = $this->scopes->key($scope);
                    $locked[$key] = $this->publicationQuery($scope)->lockForUpdate()->first();
                }

                foreach ($scopes as $scope) {
                    $key = $this->scopes->key($scope);
                    $state = $locked[$key];
                    $submitted = $this->submittedRevision($input, $key);
                    if ($pending->contains(fn ($item) => $this->scopes->key($item) === $key)) {
                        if ($state?->status === ResultPublish::STATUS_PUBLISHED) {
                            throw ResultPublicationException::conflict(
                                'PublicationTransitionConflict',
                                'The publication state changed concurrently.'
                            );
                        }
                        if ($state && $submitted !== (int) $state->revision) {
                            throw ResultPublicationException::conflict(
                                'PublicationRevisionConflict',
                                'The publication revision changed concurrently.'
                            );
                        }
                        if (!$state && $submitted !== null) {
                            throw ResultPublicationException::conflict(
                                'PublicationRevisionConflict',
                                'The publication state changed concurrently.'
                            );
                        }
                    }
                }

                $this->readiness->lockAndAssertSubjectStates($evidence);
                $finalEvidence = $this->readiness->prepareAll($pending, $confirmAnyway);
                foreach ($evidence as $index => $item) {
                    if ($item['subject_revisions'] !== $finalEvidence[$index]['subject_revisions']) {
                        throw ResultPublicationException::conflict(
                            'PublicationRevisionConflict',
                            'Confirmed marks changed during publication.'
                        );
                    }
                }

                $correlation = $this->events->correlationUuid();
                $responses = $idempotent->values();
                foreach ($pending as $scope) {
                    $key = $this->scopes->key($scope);
                    $state = $locked[$key] ?: new ResultPublish();
                    $before = $state->exists ? $this->state($state) : null;
                    $state->forceFill([
                        'examId' => (string) $scope['examId'],
                        'sessionId' => (string) $scope['sessionId'],
                        'classId' => (string) $scope['classId'],
                        'groupId' => $scope['groupId'] === null ? null : (string) $scope['groupId'],
                        'status' => ResultPublish::STATUS_PUBLISHED,
                        'revision' => $state->exists ? (int) $state->revision + 1 : 1,
                        'published_by' => $actor->id,
                        'published_at' => now(),
                        'unpublished_by' => null,
                        'unpublished_at' => null,
                        'unpublish_reason' => null,
                        'legacyImported' => false,
                    ]);
                    $state->save();
                    $itemEvidence = $finalEvidence->firstWhere('scope_key', $key);
                    $this->events->append(
                        'result_published',
                        $scope,
                        $actor,
                        $correlation,
                        $before,
                        $this->state($state),
                        [
                            'subject_revisions' => $itemEvidence['subject_revisions'],
                            'student_count' => $itemEvidence['student_count'],
                            'subject_count' => $itemEvidence['subject_count'],
                            'outcomes' => $itemEvidence['outcomes'],
                            'non_ready_scopes' => $itemEvidence['non_ready_scopes'],
                            'confirmed_anyway' => $confirmAnyway,
                        ],
                        null,
                        $ipAddress,
                    );
                    $responses->push($this->response($scope, $state, false));
                }
                return $responses->values();
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ResultPublicationException::conflict(
                    'PublicationTransitionConflict',
                    'Another request changed this publication scope. Reload and retry.'
                );
            }
            throw $exception;
        }

        return ['success' => true, 'publications' => $published->all(), 'idempotent' => false];
    }

    private function publicationQuery(array $scope)
    {
        return ResultPublish::query()
            ->where('examId', (string) $scope['examId'])
            ->where('sessionId', (string) $scope['sessionId'])
            ->where('classId', (string) $scope['classId'])
            ->when($scope['groupId'] === null,
                fn ($query) => $query->whereNull('groupId'),
                fn ($query) => $query->where('groupId', (string) $scope['groupId']));
    }

    private function submittedRevision(array $input, string $key): ?int
    {
        $value = $input['publication_revisions'][$key] ?? $input['publication_revision'] ?? null;
        return is_numeric($value) ? (int) $value : null;
    }

    private function state(ResultPublish $state): array
    {
        return [
            'status' => $state->status,
            'revision' => (int) $state->revision,
            'published_by' => $state->published_by,
            'published_at' => $state->published_at?->toISOString(),
            'unpublished_by' => $state->unpublished_by,
            'unpublished_at' => $state->unpublished_at?->toISOString(),
            'unpublish_reason' => $state->unpublish_reason,
            'legacyImported' => (bool) $state->legacyImported,
        ];
    }

    private function response(array $scope, ResultPublish $state, bool $idempotent): array
    {
        return [
            'scope' => $scope,
            'status' => $state->status,
            'revision' => (int) $state->revision,
            'published_by' => $state->published_by,
            'published_at' => $state->published_at?->toISOString(),
            'idempotent' => $idempotent,
        ];
    }
}

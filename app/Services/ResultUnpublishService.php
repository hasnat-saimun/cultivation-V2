<?php

namespace App\Services;

use App\Exceptions\ResultPublicationException;
use App\Models\CultivationAdmin;
use App\Models\ResultPublish;
use Illuminate\Support\Facades\DB;

class ResultUnpublishService
{
    public function __construct(
        private ResultPublicationScopeService $scopes,
        private ResultLifecycleEventService $events,
    ) {}

    public function unpublish(array $input, CultivationAdmin $actor, ?string $ipAddress = null): array
    {
        $this->scopes->assertActor($actor);
        $reason = trim((string) ($input['reason'] ?? ''));
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw ResultPublicationException::invalid(
                'PublicationTransitionConflict',
                'An unpublish reason of 1 to 500 characters is required.'
            );
        }
        $scopes = $this->scopes->resolve($input);

        $responses = DB::transaction(function () use ($input, $actor, $ipAddress, $reason, $scopes) {
            $locked = collect();
            foreach ($scopes as $scope) {
                $key = $this->scopes->key($scope);
                $state = $this->publicationQuery($scope)->lockForUpdate()->first();
                if (!$state) throw ResultPublicationException::missing(
                    'No publication state exists for one or more selected scopes.'
                );
                $submitted = $this->submittedRevision($input, $key);
                if ($submitted === null || $submitted !== (int) $state->revision) {
                    throw ResultPublicationException::conflict(
                        'PublicationRevisionConflict',
                        'The publication revision is stale or missing.'
                    );
                }
                $locked[$key] = $state;
            }

            $correlation = $this->events->correlationUuid();
            $results = collect();
            foreach ($scopes as $scope) {
                $key = $this->scopes->key($scope);
                $state = $locked[$key];
                if ($state->status === ResultPublish::STATUS_UNPUBLISHED) {
                    $results->push($this->response($scope, $state, true));
                    continue;
                }
                if ($state->status !== ResultPublish::STATUS_PUBLISHED) {
                    throw ResultPublicationException::conflict(
                        'PublicationTransitionConflict',
                        'The publication cannot be unpublished from its current state.'
                    );
                }

                $before = $this->state($state);
                $state->status = ResultPublish::STATUS_UNPUBLISHED;
                $state->revision = (int) $state->revision + 1;
                $state->unpublished_by = $actor->id;
                $state->unpublished_at = now();
                $state->unpublish_reason = $reason;
                $state->save();
                $this->events->append(
                    'result_unpublished',
                    $scope,
                    $actor,
                    $correlation,
                    $before,
                    $this->state($state),
                    null,
                    $reason,
                    $ipAddress,
                );
                $results->push($this->response($scope, $state, false));
            }
            return $results;
        }, 3);

        return [
            'success' => true,
            'publications' => $responses->all(),
            'idempotent' => $responses->every(fn ($response) => $response['idempotent']),
        ];
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
            'unpublished_by' => $state->unpublished_by,
            'unpublished_at' => $state->unpublished_at?->toISOString(),
            'reason' => $state->unpublish_reason,
            'idempotent' => $idempotent,
        ];
    }
}

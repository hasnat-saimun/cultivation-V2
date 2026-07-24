<?php

namespace App\Services;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Models\MarksScopeState;
use Illuminate\Support\Facades\DB;

class ResultMarksReopenService
{
    public function __construct(
        private ResultMarksScopeService $scopes,
        private ResultLifecycleEventService $events,
    ) {}

    public function reopen(array $input, CultivationAdmin $actor, ?string $ipAddress = null): array
    {
        $this->scopes->assertActor($actor, true);
        $reason = trim((string) ($input['reason'] ?? ''));
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw ResultLifecycleException::invalid('LifecycleTransitionConflict', 'A reopen reason of 1 to 500 characters is required.');
        }
        $scope = $this->scope($input);
        $revision = $this->revision($input);

        return DB::transaction(function () use ($scope, $revision, $reason, $actor, $ipAddress) {
            $state = $this->scopes->query($scope)->lockForUpdate()->first();
            if (!$state) throw ResultLifecycleException::missing();
            $this->scopes->assertNotPublished($scope);
            if ($state->status === MarksScopeState::STATUS_DRAFT) {
                throw ResultLifecycleException::conflict('ScopeAlreadyDraft', 'The subject scope is already Draft.');
            }
            if ($state->status !== MarksScopeState::STATUS_CONFIRMED) {
                throw ResultLifecycleException::conflict('LifecycleTransitionConflict', 'The subject scope cannot be reopened from its current state.');
            }
            if ((int) $state->revision !== $revision) {
                throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'The submitted scope revision is stale.');
            }
            $before = [
                'status' => $state->status,
                'revision' => (int) $state->revision,
                'confirmed_by' => $state->confirmed_by,
                'confirmed_at' => $state->confirmed_at?->toISOString(),
            ];
            $state->status = MarksScopeState::STATUS_DRAFT;
            $state->revision = (int) $state->revision + 1;
            $state->reopened_by = $actor->id;
            $state->reopened_at = now();
            $state->reopen_reason = $reason;
            $state->save();
            $after = [
                'status' => $state->status,
                'revision' => (int) $state->revision,
                'reopened_by' => (int) $actor->id,
                'reopened_at' => $state->reopened_at?->toISOString(),
            ];
            $this->events->append(
                'subject_reopened',
                $scope,
                $actor,
                $this->events->correlationUuid(),
                $before,
                $after,
                null,
                $reason,
                $ipAddress,
            );
            return [
                'success' => true,
                'scope' => $scope,
                'status' => $state->status,
                'revision' => (int) $state->revision,
                'reopened_by' => (int) $actor->id,
                'reopened_at' => $state->reopened_at?->toISOString(),
            ];
        }, 3);
    }

    private function scope(array $input): array
    {
        foreach (['sessionId', 'classId', 'examId', 'subjectId'] as $key) {
            if (!isset($input[$key]) || (int) $input[$key] <= 0) throw ResultLifecycleException::missing();
        }
        $group = $input['groupId'] ?? null;
        return [
            'sessionId' => (int) $input['sessionId'],
            'classId' => (int) $input['classId'],
            'groupId' => is_numeric($group) && (int) $group > 0 ? (int) $group : null,
            'examId' => (int) $input['examId'],
            'subjectId' => (int) $input['subjectId'],
        ];
    }

    private function revision(array $input): int
    {
        if (!isset($input['scope_revision']) || !is_numeric($input['scope_revision'])) {
            throw ResultLifecycleException::conflict('ScopeRevisionConflict', 'A current scope revision is required.');
        }
        return (int) $input['scope_revision'];
    }
}

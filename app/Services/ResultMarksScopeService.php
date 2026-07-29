<?php

namespace App\Services;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Models\MarksScopeState;
use App\Models\ResultPublish;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ResultMarksScopeService
{
    public function key(array $scope): string
    {
        return $scope['groupId'] === null ? 'class' : 'section:'.$scope['groupId'];
    }

    public function query(array $scope)
    {
        return MarksScopeState::query()
            ->where('sessionId', (string) $scope['sessionId'])
            ->where('classId', (string) $scope['classId'])
            ->where('examId', (string) $scope['examId'])
            ->where('subjectId', (string) $scope['subjectId'])
            ->when($scope['groupId'] === null,
                fn ($q) => $q->whereNull('groupId'),
                fn ($q) => $q->where('groupId', (string) $scope['groupId']));
    }

    public function find(array $scope): ?MarksScopeState
    {
        return $this->query($scope)->first();
    }

    public function lockOrCreate(array $scope): MarksScopeState
    {
        $state = $this->query($scope)->lockForUpdate()->first();
        if ($state) return $state;

        try {
            $state = new MarksScopeState();
            $state->forceFill($scope + [
                'status' => MarksScopeState::STATUS_DRAFT,
                'revision' => 1,
            ])->save();
        } catch (QueryException $exception) {
            if (!in_array((string) $exception->getCode(), ['23000', '23505'], true)) throw $exception;
        }

        $state = $this->query($scope)->lockForUpdate()->first();
        if (!$state) throw ResultLifecycleException::conflict(
            'LifecycleTransitionConflict',
            'The marks scope could not be initialized safely.'
        );
        return $state;
    }

    public function assertNotPublished(array $scope): void
    {
        $publishedQuery = ResultPublish::query()
            ->where('status', ResultPublish::STATUS_PUBLISHED)
            ->where('sessionId', (string) $scope['sessionId'])
            ->where('classId', (string) $scope['classId'])
            ->where('examId', (string) $scope['examId'])
            ->where(function ($query) use ($scope) {
                $query->whereNull('groupId');
                if ($scope['groupId'] !== null) {
                    $query->orWhere('groupId', (string) $scope['groupId']);
                }
            });
        if (DB::transactionLevel() > 0) {
            $publishedQuery->lockForUpdate();
        }
        $published = $publishedQuery->exists();

        if ($published) throw ResultLifecycleException::conflict(
            'ScopePublished',
            'This result has already been published. Please unpublish it before making any changes.'
        );
    }

    public function assertActor(?CultivationAdmin $actor, bool $administratorOnly = false): void
    {
        if (!$actor || $actor->isCash()) throw ResultLifecycleException::forbidden();
        if ($administratorOnly && $actor->isTeacher()) {
            throw ResultLifecycleException::forbidden('Only a General or Super administrator may reopen marks.');
        }
    }
}

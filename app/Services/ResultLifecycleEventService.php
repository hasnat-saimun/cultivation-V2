<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use App\Models\ResultLifecycleEvent;
use Illuminate\Support\Str;

class ResultLifecycleEventService
{
    public const MAX_PAYLOAD_BYTES = 1048576;

    public function correlationUuid(): string
    {
        return (string) Str::uuid();
    }

    public function append(
        string $action,
        array $scope,
        ?CultivationAdmin $actor,
        ?string $correlationUuid,
        ?array $before,
        ?array $after,
        ?array $changes = null,
        ?string $reason = null,
        ?string $ipAddress = null,
    ): ResultLifecycleEvent {
        $attributes = [
            'event_uuid' => (string) Str::uuid(),
            'correlation_uuid' => $correlationUuid,
            'actor_id' => $actor?->id,
            'actor_role' => $this->actorRole($actor),
            'action' => $action,
            'entity_type' => 'subject_scope',
            'sessionId' => $scope['sessionId'] ?? null,
            'classId' => $scope['classId'] ?? null,
            'groupId' => $scope['groupId'] ?? null,
            'examId' => $scope['examId'] ?? null,
            'subjectId' => $scope['subjectId'] ?? null,
            'before_state' => $before,
            'after_state' => $after,
            'change_set' => $changes,
            'reason' => $reason,
            'ip_address' => $ipAddress,
        ];
        $encoded = json_encode($attributes, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        if (strlen($encoded) > self::MAX_PAYLOAD_BYTES) {
            throw new \OverflowException('Lifecycle audit evidence exceeds the safe event size limit.');
        }

        $event = new ResultLifecycleEvent();
        $event->forceFill($attributes);
        $event->save();
        return $event;
    }

    public function actorRole(?CultivationAdmin $actor): ?string
    {
        if (!$actor) return null;
        if ($actor->isTeacher()) return 'teacher';
        if ($actor->isCash()) return 'cash_admin';
        if ($actor->isGeneral()) return 'general_admin';
        return 'super_admin';
    }
}

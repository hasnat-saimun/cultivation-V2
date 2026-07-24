<?php

namespace App\Services;

use App\Models\ResultPublish;

class ResultPublicationVisibilityService
{
    public function isPublished(int $examId, int $sessionId, int $classId, ?int $groupId): bool
    {
        return ResultPublish::query()
            ->where('status', ResultPublish::STATUS_PUBLISHED)
            ->where('examId', (string) $examId)
            ->where('sessionId', (string) $sessionId)
            ->where('classId', (string) $classId)
            ->where(function ($query) use ($groupId) {
                if ($groupId === null) {
                    $query->whereNull('groupId');
                    return;
                }
                $query->where('groupId', (string) $groupId)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('groupId')->where('legacyImported', true);
                    });
            })
            ->exists();
    }
}

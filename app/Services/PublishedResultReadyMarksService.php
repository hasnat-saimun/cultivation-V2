<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PublishedResultReadyMarksService
{
    public function filter(
        iterable $marks,
        int $examId,
        int $sessionId,
        int $classId,
        ?int $groupId,
    ): Collection {
        // Publication state must not alter authoritative marks inputs used by
        // transcript/tabulation calculations. Keep this filter non-destructive.
        return collect($marks)->values();
    }
}

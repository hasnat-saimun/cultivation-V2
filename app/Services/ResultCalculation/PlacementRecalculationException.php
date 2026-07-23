<?php

namespace App\Services\ResultCalculation;

use RuntimeException;

class PlacementRecalculationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly array $report,
    ) {
        parent::__construct($message);
    }
}

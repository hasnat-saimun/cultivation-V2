<?php

namespace App\Services\ResultCalculation;

final class EffectiveComponentMarkResolver
{
    public static function resolve(mixed $rawValue, bool $componentEnabled, bool $confirmedBlankOverride): ?float
    {
        if (!$componentEnabled) {
            return null;
        }

        if (is_numeric($rawValue)) {
            return (float) $rawValue;
        }

        return $confirmedBlankOverride ? 0.0 : null;
    }
}

<?php

namespace App\Services\ResultCalculation;

class SubjectHeaderFormatter
{
    private const CONNECTOR_WORDS = ['and', 'of', 'the'];

    public static function normalizeName(string $rawName): string
    {
        $withoutCode = preg_replace('/\s*-\s*\d+\s*$/', '', trim($rawName));
        $withoutCode = $withoutCode === null ? trim($rawName) : $withoutCode;

        return trim(preg_replace('/\s+/', ' ', $withoutCode) ?? $withoutCode);
    }

    public static function shortLabel(string $rawName): string
    {
        $normalized = self::normalizeName($rawName);
        if ($normalized === '') {
            return '';
        }

        $words = preg_split('/\s+/', $normalized) ?: [];
        $meaningful = array_values(array_filter($words, function (string $word): bool {
            return !in_array(strtolower($word), self::CONNECTOR_WORDS, true);
        }));

        if (count($meaningful) <= 1) {
            return $normalized;
        }

        $letters = array_map(function (string $word): string {
            $first = mb_substr($word, 0, 1);
            return strtoupper($first);
        }, $meaningful);

        return implode('.', $letters).'.';
    }
}

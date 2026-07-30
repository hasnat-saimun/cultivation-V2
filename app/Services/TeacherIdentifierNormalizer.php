<?php

namespace App\Services;

use Normalizer;

class TeacherIdentifierNormalizer
{
    public function normalize(string $identifier): string
    {
        $normalized = class_exists(Normalizer::class)
            ? (Normalizer::normalize($identifier, Normalizer::FORM_C) ?: $identifier)
            : $identifier;

        $normalized = preg_replace('/[\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $normalized)
            ?? $normalized;
        $normalized = trim($normalized);

        return $this->isEmail($normalized) ? mb_strtolower($normalized) : $normalized;
    }

    public function isEmail(string $identifier): bool
    {
        return str_contains($identifier, '@');
    }

    public function canonicalMobile(string $identifier): ?string
    {
        $compact = preg_replace('/[\s\-().]+/u', '', $identifier) ?? $identifier;

        if (preg_match('/^01\d{9}$/', $compact) === 1) {
            return $compact;
        }

        if (preg_match('/^(?:\+?88)(01\d{9})$/', $compact, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /** @return list<string> */
    public function mobileLookupValues(string $canonicalMobile): array
    {
        return [$canonicalMobile, '88'.$canonicalMobile, '+88'.$canonicalMobile];
    }
}

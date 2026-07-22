<?php

namespace App\Services;

class DepartmentBasedClassDetector
{
    public function isDepartmentBasedClass(?string $className): bool
    {
        if ($className === null) {
            return false;
        }

        $normalized = $this->normalizeClassName($className);
        if ($normalized === '') {
            return false;
        }

        if (preg_match('/\b(ssc|hsc)\b/u', $normalized) === 1) {
            return true;
        }

        $singleTokenIndicators = [
            '9', '10', '11', '12',
            'nine', 'ten', 'eleven', 'twelve',
            'ix', 'x', 'xi', 'xii',
            'নবম', 'দশম', 'একাদশ', 'দ্বাদশ',
            'নাইন', 'টেন',
            '9ম', '10ম', '11তম', '12তম',
        ];

        if (in_array($normalized, $singleTokenIndicators, true)) {
            return true;
        }

        $tokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (empty($tokens)) {
            return false;
        }

        $classContextTokens = ['class', 'grade', 'ক্লাস', 'শ্রেণি'];
        $hasClassContext = count(array_intersect($tokens, $classContextTokens)) > 0;
        if (!$hasClassContext) {
            return false;
        }

        $classLevelIndicators = [
            '9', '10', '11', '12',
            'nine', 'ten', 'eleven', 'twelve',
            'ix', 'x', 'xi', 'xii',
            'নবম', 'দশম', 'একাদশ', 'দ্বাদশ',
            'নাইন', 'টেন',
            '9ম', '10ম', '11তম', '12তম',
        ];

        foreach ($tokens as $token) {
            if (in_array($token, $classLevelIndicators, true)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeClassName(string $className): string
    {
        $normalized = trim($className);
        if ($normalized === '') {
            return '';
        }

        $normalized = mb_strtolower($normalized, 'UTF-8');
        $normalized = strtr($normalized, [
            '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
            '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
        ]);

        $normalized = preg_replace('/[_\-\x{2010}-\x{2015}]+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}

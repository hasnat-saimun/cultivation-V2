<?php

namespace App\Services\Students;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class StudentGenderService
{
    public const ALL = 'all';
    public const MALE = 'male';
    public const FEMALE = 'female';
    public const OTHER = 'other';

    public function normalize(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            throw ValidationException::withMessages(['gender' => ['Invalid gender selection.']]);
        }

        $normalized = strtolower(trim((string) ($value ?? self::ALL)));
        $normalized = match ($normalized) {
            '', self::ALL => self::ALL,
            '1', 'm', self::MALE => self::MALE,
            '2', 'f', self::FEMALE => self::FEMALE,
            '3', 'others', 'unknown', self::OTHER => self::OTHER,
            default => null,
        };

        if ($normalized === null) {
            throw ValidationException::withMessages(['gender' => ['Invalid gender selection.']]);
        }

        return $normalized;
    }

    public function apply(Builder $query, string $gender, string $column = 'new_admissions.gender'): Builder
    {
        $gender = $this->normalize($gender);
        if ($gender === self::ALL) {
            return $query;
        }

        $expression = "LOWER(TRIM(COALESCE({$column}, '')))";
        $male = ['1', 'male', 'm'];
        $female = ['2', 'female', 'f'];

        return match ($gender) {
            self::MALE => $query->whereIn(DB::raw($expression), $male),
            self::FEMALE => $query->whereIn(DB::raw($expression), $female),
            self::OTHER => $query->whereNotIn(DB::raw($expression), array_merge($male, $female)),
        };
    }

    public function label(string $gender): string
    {
        return match ($this->normalize($gender)) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            self::OTHER => 'Other/Unknown',
            default => 'All',
        };
    }
}

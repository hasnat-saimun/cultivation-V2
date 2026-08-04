<?php

namespace App\Services\Students;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class StudentOrderingService
{
    public function apply(Builder $query, string $table = 'new_admissions'): Builder
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            throw new InvalidArgumentException('Invalid student table alias.');
        }

        $column = static fn (string $name): string => $table.'.'.$name;
        $driver = $query->getConnection()->getDriverName();

        $query->reorder();
        $this->applyNumericTextOrder($query, $column('sessName'), $driver);
        $this->applyNumericTextOrder($query, $column('className'), $driver);
        $this->applyNumericTextOrder($query, $column('sectionName'), $driver);
        $this->applyNumericTextOrder($query, $column('departmentName'), $driver);
        $query->orderByRaw("CASE LOWER(TRIM(CAST({$column('gender')} AS CHAR))) WHEN '1' THEN 0 WHEN 'male' THEN 0 WHEN 'm' THEN 0 WHEN '2' THEN 1 WHEN 'female' THEN 1 WHEN 'f' THEN 1 ELSE 2 END");

        $this->applyNumericTextOrder($query, $column('rollNumber'), $driver);
        $this->applyNumericTextOrder($query, $column('stdId'), $driver);

        return $query->orderBy($column('id'));
    }

    private function applyNumericTextOrder(Builder $query, string $column, string $driver): void
    {
        if ($driver === 'sqlite') {
            $isNumeric = "TRIM(COALESCE({$column}, '')) <> '' AND TRIM(COALESCE({$column}, '')) NOT GLOB '*[^0-9]*'";
            $query->orderByRaw("CASE WHEN {$isNumeric} THEN 0 ELSE 1 END")
                ->orderByRaw("CASE WHEN {$isNumeric} THEN CAST({$column} AS INTEGER) ELSE NULL END")
                ->orderByRaw("TRIM(COALESCE({$column}, ''))");

            return;
        }

        $isNumeric = "TRIM(COALESCE({$column}, '')) REGEXP '^[0-9]+$'";
        $query->orderByRaw("CASE WHEN {$isNumeric} THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN {$isNumeric} THEN CAST({$column} AS UNSIGNED) ELSE NULL END")
            ->orderByRaw("TRIM(COALESCE({$column}, ''))");
    }
}

<?php

namespace App\Services\ResultCalculation;

use App\Models\GradeList;
use Illuminate\Support\Collection;

final class GradeScaleOrderingService
{
    private ?Collection $configuredRows = null;

    /** Configured grading rows ordered numerically and deterministically for presentation. */
    public function all(): Collection
    {
        return $this->configuredRows ??= GradeList::query()
            ->orderByRaw('CAST(gradePoint AS DECIMAL(10, 2)) DESC')
            ->orderByRaw('CAST(maxMark AS DECIMAL(10, 2)) DESC')
            ->orderByRaw('CAST(minMark AS DECIMAL(10, 2)) DESC')
            ->orderBy('gradeName')
            ->orderBy('id')
            ->get();
    }

    public function sort(iterable $rows): Collection
    {
        return collect($rows)->sort(function ($left, $right): int {
            foreach (['gradePoint', 'maxMark', 'minMark'] as $field) {
                $comparison = (float) ($right->{$field} ?? 0) <=> (float) ($left->{$field} ?? 0);
                if ($comparison !== 0) return $comparison;
            }

            return strcasecmp((string) ($left->gradeName ?? ''), (string) ($right->gradeName ?? ''))
                ?: ((int) ($left->id ?? PHP_INT_MAX) <=> (int) ($right->id ?? PHP_INT_MAX));
        })->values();
    }

    public function legend(?iterable $rows = null): array
    {
        return $this->sort($rows ?? $this->all())->map(fn ($grade) => [
            'range' => $grade->minMark.' - '.$grade->maxMark,
            'grade' => (string) $grade->gradeName,
            'point' => number_format((float) $grade->gradePoint, 2),
        ])->all();
    }

    /** Grade labels follow configured grade-point order; non-grade statuses remain deterministic. */
    public function sortDistribution(array $distribution, ?iterable $rows = null): array
    {
        $remaining = $distribution;
        $ordered = [];
        foreach ($this->sort($rows ?? $this->all()) as $grade) {
            $label = (string) $grade->gradeName;
            if (array_key_exists($label, $remaining)) {
                $ordered[$label] = $remaining[$label];
                unset($remaining[$label]);
            }
        }

        foreach ($remaining as $label => $count) {
            if (in_array($label, ['Incomplete', 'Absent'], true)) continue;
            $ordered[$label] = $count;
            unset($remaining[$label]);
        }
        foreach (['Incomplete', 'Absent'] as $status) {
            if (array_key_exists($status, $remaining)) {
                $ordered[$status] = $remaining[$status];
                unset($remaining[$status]);
            }
        }

        return $ordered + $remaining;
    }
}

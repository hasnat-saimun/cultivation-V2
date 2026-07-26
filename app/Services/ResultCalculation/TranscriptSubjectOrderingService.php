<?php

namespace App\Services\ResultCalculation;

class TranscriptSubjectOrderingService
{
    /** @param array<int,array<string,mixed>> $rows */
    public function sortMainRows(array $rows): array
    {
        usort($rows, fn (array $left, array $right): int => $this->compareRows($left, $right));
        return $rows;
    }

    /** @param array<int,array<string,mixed>> $rows */
    public function sortOptionalRows(array $rows): array
    {
        usort($rows, function (array $left, array $right): int {
            $sortCompare = ((int) ($left['sortOrder'] ?? PHP_INT_MAX)) <=> ((int) ($right['sortOrder'] ?? PHP_INT_MAX));
            if ($sortCompare !== 0) {
                return $sortCompare;
            }

            return $this->stableFallback($left, $right);
        });

        return $rows;
    }

    private function compareRows(array $left, array $right): int
    {
        $categoryCompare = $this->categoryPriority($left) <=> $this->categoryPriority($right);
        if ($categoryCompare !== 0) {
            return $categoryCompare;
        }

        $sortCompare = $this->effectiveSortOrder($left) <=> $this->effectiveSortOrder($right);
        if ($sortCompare !== 0) {
            return $sortCompare;
        }

        [$leftPairGroup, $leftPairOrder] = $this->pairMetadata($left);
        [$rightPairGroup, $rightPairOrder] = $this->pairMetadata($right);

        $pairGroupCompare = $leftPairGroup <=> $rightPairGroup;
        if ($pairGroupCompare !== 0) {
            return $pairGroupCompare;
        }

        $pairOrderCompare = $leftPairOrder <=> $rightPairOrder;
        if ($pairOrderCompare !== 0) {
            return $pairOrderCompare;
        }

        $semanticCompare = $this->semanticRank($left) <=> $this->semanticRank($right);
        if ($semanticCompare !== 0) {
            return $semanticCompare;
        }

        return $this->stableFallback($left, $right);
    }

    private function effectiveSortOrder(array $row): int
    {
        return (int) ($row['mappingSortOrder'] ?? $row['sortOrder'] ?? PHP_INT_MAX);
    }

    private function categoryPriority(array $row): int
    {
        if ((bool) ($row['isOptional'] ?? false)) {
            return 40;
        }

        $sources = collect($row['applicabilitySources'] ?? [])->map(fn ($source) => strtolower(trim((string) $source)))->all();
        if (in_array('optional subject', $sources, true)) {
            return 40;
        }
        if ((bool) ($row['isReligious'] ?? false) || in_array('religion mapping', $sources, true)) {
            return 20;
        }

        $categories = collect($row['mappingCategories'] ?? [])->map(fn ($category) => strtolower(trim((string) $category)))->all();
        if (in_array('department_group', $categories, true)) {
            return 30;
        }
        if (in_array('common', $categories, true)) {
            return 10;
        }

        $departmentIds = $row['mappingDepartmentIds'] ?? [];
        if (is_array($departmentIds) && count($departmentIds) > 0) {
            return 30;
        }

        return 10;
    }

    private function stableFallback(array $left, array $right): int
    {
        $nameCompare = strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        if ($nameCompare !== 0) {
            return $nameCompare;
        }

        return strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? ''));
    }

    /** @return array{0:int,1:int} */
    private function pairMetadata(array $row): array
    {
        $id = strtolower(trim((string) ($row['id'] ?? '')));
        if ($id === 'pair:bangla') {
            return [10, 1];
        }
        if ($id === 'pair:english') {
            return [20, 1];
        }

        $name = $this->normalizedName($row);
        if (str_contains($name, 'bangla')) {
            return [10, $this->paperOrder($name)];
        }
        if (str_contains($name, 'english')) {
            return [20, $this->paperOrder($name)];
        }

        return [999, 999];
    }

    private function semanticRank(array $row): int
    {
        if ((bool) ($row['isOptional'] ?? false)) {
            return 1000;
        }

        $name = $this->normalizedName($row);

        if (str_contains($name, 'mathematics') || preg_match('/\bmath\b/', $name) === 1) {
            return 300;
        }
        if (str_contains($name, 'ict') || str_contains($name, 'information and communication technology')) {
            return 400;
        }
        if (str_contains($name, 'bangladesh and global studies') || str_contains($name, 'bgs')) {
            return 500;
        }
        if ($this->isReligious($name)) {
            return 600;
        }

        $science = [
            'physics' => 910,
            'chemistry' => 920,
            'biology' => 930,
        ];
        foreach ($science as $keyword => $rank) {
            if (str_contains($name, $keyword)) {
                return $rank;
            }
        }

        $business = [
            'accounting' => 940,
            'finance and banking' => 950,
            'business entrepreneurship' => 960,
        ];
        foreach ($business as $keyword => $rank) {
            if (str_contains($name, $keyword)) {
                return $rank;
            }
        }

        $humanities = [
            'history' => 970,
            'civics' => 980,
            'geography' => 990,
        ];
        foreach ($humanities as $keyword => $rank) {
            if (str_contains($name, $keyword)) {
                return $rank;
            }
        }

        return 800;
    }

    private function normalizedName(array $row): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) ($row['name'] ?? ''))));
    }

    private function paperOrder(string $name): int
    {
        if (preg_match('/\b(1st|first|paper\s*i|paper\s*1)\b/', $name) === 1) {
            return 1;
        }
        if (preg_match('/\b(2nd|second|paper\s*ii|paper\s*2)\b/', $name) === 1) {
            return 2;
        }

        return 9;
    }

    private function isReligious(string $name): bool
    {
        foreach (['religion', 'moral', 'islam', 'hindu', 'christian', 'buddhist'] as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }
}

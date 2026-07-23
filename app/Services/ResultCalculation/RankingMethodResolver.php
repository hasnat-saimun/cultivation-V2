<?php

namespace App\Services\ResultCalculation;

use App\Models\ServerConfig;

class RankingMethodResolver
{
    /** @return array{method:string,warnings:array<int,array{code:string,message:string}>} */
    public function resolve(): array
    {
        $stored = ServerConfig::query()->latest('id')->value('ranking_method');
        $method = strtolower(trim((string) $stored));

        if (in_array($method, ServerConfig::RANKING_METHODS, true)) {
            return ['method' => $method, 'warnings' => []];
        }

        return [
            'method' => ServerConfig::RANKING_METHOD_GRADING,
            'warnings' => [[
                'code' => $stored === null || $method === '' ? 'RANKING_METHOD_MISSING' : 'RANKING_METHOD_INVALID',
                'message' => 'Ranking method was missing or invalid; Grading Method was used.',
            ]],
        ];
    }
}

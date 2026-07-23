<?php

return [
    'shadow_mode' => (bool) env('RESULT_ENGINE_SHADOW_MODE', false),
    'transcript_enabled' => (bool) env('RESULT_ENGINE_TRANSCRIPT_ENABLED', false),
    'bulk_transcript_enabled' => (bool) env('RESULT_ENGINE_BULK_TRANSCRIPT_ENABLED', false),
    'tabulation_enabled' => (bool) env('RESULT_ENGINE_TABULATION_ENABLED', false),
    'summary_enabled' => (bool) env('RESULT_ENGINE_SUMMARY_ENABLED', false),
    'placement_enabled' => (bool) env('RESULT_ENGINE_PLACEMENT_ENABLED', false),
    'promotion_enabled' => (bool) env('RESULT_ENGINE_PROMOTION_ENABLED', false),
    'promotion_revert_enabled' => (bool) env('RESULT_ENGINE_PROMOTION_REVERT_ENABLED', false),
];

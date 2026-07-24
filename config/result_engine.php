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
    'historical_exception_manifest' => [
        'databases' => ['cultivation_rhs', 'cultivation_rhs_rehearsal'],
        'orphan_student_ids' => [5, 6, 48, 64, 257, 332, 344, 347],
        'orphan_marks_count' => 95,
        'missing_exam_ids' => [1],
        'missing_subject_ids' => [1],
        'orphan_marks_sha256' => '737830306cae440444fcea0437c4473ab90f1b86d1cfc6b50366cc9e2b1f7f82',
        'missing_master_marks_sha256' => 'd63713e71e1ffea159b306f30cdf6a58b442a2ba5aedf0d2e79d93f220da478d',
    ],
];

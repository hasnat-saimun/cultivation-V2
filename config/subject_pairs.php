<?php

return [
    // Explicit alias-to-base mapping for pairs
    // Example: 'english_1st_paper' => 'english', 'english_2nd_paper' => 'english'
    'aliases' => [
        // Bangla
        'bangla_1st_paper'   => 'bangla',
        'bangla_2nd_paper'   => 'bangla',
        'bangla_first_paper' => 'bangla',
        'bangla_second_paper'=> 'bangla',
        'bangla_paper_i'     => 'bangla',
        'bangla_paper_ii'    => 'bangla',
        'bangla_paper_1'     => 'bangla',
        'bangla_paper_2'     => 'bangla',

        // English
        'english_1st_paper'   => 'english',
        'english_2nd_paper'   => 'english',
        'english_first_paper' => 'english',
        'english_second_paper'=> 'english',
        'english_paper_i'     => 'english',
        'english_paper_ii'    => 'english',
        'english_paper_1'     => 'english',
        'english_paper_2'     => 'english',
    ],

    // Explicit subjectName-to-base mapping
    // Example: 'English 1st Paper' => 'English', 'English 2nd Paper' => 'English'
    'names' => [
        // Bangla
        'Bangla 1st Paper'   => 'Bangla',
        'Bangla 2nd Paper'   => 'Bangla',
        'Bangla First Paper' => 'Bangla',
        'Bangla Second Paper'=> 'Bangla',
        'Bangla Paper I'     => 'Bangla',
        'Bangla Paper II'    => 'Bangla',
        'Bangla Paper-1'     => 'Bangla',
        'Bangla Paper-2'     => 'Bangla',

        // English
        'English 1st Paper'   => 'English',
        'English 2nd Paper'   => 'English',
        'English First Paper' => 'English',
        'English Second Paper'=> 'English',
        'English Paper I'     => 'English',
        'English Paper II'    => 'English',
        'English Paper-1'     => 'English',
        'English Paper-2'     => 'English',
    ],

    // Optional: direct subject-id to base mapping (overrides name/alias)
    'ids' => [
        // 12 => 'Bangla',
        // 13 => 'Bangla',
    ],

    // Optional: display name override per base key
    // Keys should match the base value from aliases/names/ids (case-insensitive)
    'displayNames' => [
        'bangla'  => 'Bangla',
        'english' => 'English',
    ],
];

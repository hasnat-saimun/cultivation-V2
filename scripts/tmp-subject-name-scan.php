<?php

use App\Models\Subject;

$rows = Subject::query()
    ->where(function ($query): void {
        $query->where('subjectName', 'like', '%Accounting%')
            ->orWhere('subjectName', 'like', '%Finance%')
            ->orWhere('subjectName', 'like', '%Entrepreneur%')
            ->orWhere('subjectName', 'like', '%Business%')
            ->orWhere('subjectName', 'like', '%History%')
            ->orWhere('subjectName', 'like', '%Civics%')
            ->orWhere('subjectName', 'like', '%Geography%');
    })
    ->orderBy('id')
    ->get(['id', 'subjectName']);

echo $rows->toJson(JSON_PRETTY_PRINT);

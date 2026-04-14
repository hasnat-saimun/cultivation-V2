<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher Wise Class Routine</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            background: #fff;
            margin: 0;
        }

        .routine-sheet {
            border: 1px solid #333;
            padding: 6px;
        }

        .routine-header {
            text-align: center;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #ddd;
        }

        .institute-name {
            margin: 0;
            font-size: 18px;
            line-height: 1.1;
            font-weight: 700;
            color: #000;
        }

        .routine-title {
            margin: 1px 0 0;
            font-size: 11px;
            font-weight: 700;
            color: #000;
        }

        .routine-subtitle {
            margin: 0;
            font-size: 9px;
            font-weight: 600;
            color: #333;
            display: none;
        }

        .routine-meta {
            font-size: 8px;
            color: #333;
            font-weight: 500;
        }

        .routine-meta span {
            margin: 0 7px;
        }

        .routine-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8px;
        }

        .routine-table th,
        .routine-table td {
            border: 1px solid #333;
            text-align: center;
            vertical-align: top;
            padding: 3px 2px;
            color: #000;
        }

        .routine-table thead th {
            font-size: 8px;
            font-weight: 700;
            background: #e0e0e0;
        }

        .teacher-col {
            width: 15%;
            background: #f0f0f0 !important;
            font-weight: 700;
            text-align: left;
            padding-left: 3px !important;
        }

        .period-head {
            background: #e8e8e8 !important;
        }

        .period-time {
            background: #f5f5f5 !important;
            font-size: 7px;
            font-weight: 600;
        }

        .break-col {
            width: 5%;
            background: #e8e8e8 !important;
            font-size: 8px;
            font-weight: 700;
        }

        .break-cell {
            background: #f0f0f0;
            font-weight: 700;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 10px;
            line-height: 1.2;
            padding: 4px 2px !important;
        }

        .teacher-name {
            background: #f9f9f9;
            font-weight: 700;
            text-align: left !important;
            font-size: 8px;
            padding-left: 3px !important;
            word-break: break-word;
        }

        .subject-cell {
            font-size: 8px;
            font-weight: 600;
            min-height: 28px;
            white-space: normal;
            line-height: 1.2;
            word-break: break-word;
            padding: 2px 1px !important;
        }

        .subject-empty {
            color: #9ca3af;
            font-weight: 500;
        }
    </style>
</head>
<body>
    @php
        $config = \App\Models\ServerConfig::orderBy('id', 'DESC')->first();
        $itemClass = \App\Models\classManage::find($routine->assignClass);
        $itemSection = \App\Models\sectionManage::find($routine->assignSection);
        $itemDepartment = \App\Models\Department::find($routine->assignDepartment);
        $itemSession = \App\Models\sessionManage::find($routine->assignSession);

        $periodColumns = $teacherWise['periodColumns'] ?? [];
        $breakExists = (bool)($teacherWise['breakExists'] ?? false);
        $breakLabel = (string)($teacherWise['breakLabel'] ?? '');
        $breakInsertIndex = $teacherWise['breakInsertIndex'] ?? null;
        $teacherRows = $teacherWise['teacherRows'] ?? [];

        $totalColumns = 1 + count($periodColumns) + ($breakExists ? 1 : 0);
    @endphp

    <div class="routine-sheet">
        <div class="routine-header">
            <h2 class="institute-name">{{ $config->instituteName ?? config('app.name', 'Institute Name') }}</h2>
            <div class="routine-title">{{ $routine->title ?? 'Class Routine' }}</div>
            <div class="routine-subtitle">Teacher Wise Routine</div>
            <div class="routine-meta">
                <span>Class: {{ $itemClass->className ?? '-' }}</span>
                <span>Session: {{ $itemSession->session ?? '-' }}</span>
                <span>Section: {{ $itemSection->section ?? 'All' }}</span>
                <span>Department: {{ $itemDepartment->departmentName ?? 'All' }}</span>
            </div>
        </div>

        <table class="routine-table">
            <thead>
                <tr>
                    <th class="teacher-col" rowspan="2">Teacher Name</th>
                    @foreach($periodColumns as $idx => $column)
                        @if($breakExists && $breakInsertIndex === $idx)
                            <th class="break-col" rowspan="2">Break<br>{{ $breakLabel }}</th>
                        @endif
                        <th class="period-head">{{ $column['period'] }} Period</th>
                    @endforeach
                    @if($breakExists && $breakInsertIndex === count($periodColumns))
                        <th class="break-col" rowspan="2">Break<br>{{ $breakLabel }}</th>
                    @endif
                </tr>
                <tr>
                    @foreach($periodColumns as $column)
                        <th class="period-time">{{ $column['time'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if(count($teacherRows) > 0)
                    @foreach($teacherRows as $rowIndex => $teacher)
                        <tr>
                            <td class="teacher-name">{{ $teacher['name'] }}</td>
                            @foreach($periodColumns as $idx => $column)
                                @if($breakExists && $breakInsertIndex === $idx && $rowIndex === 0)
                                    <td class="break-cell" rowspan="{{ count($teacherRows) }}">Break {{ $breakLabel }}</td>
                                @endif
                                @php
                                    $cellText = trim((string)($teacher['cells'][$column['key']] ?? ''));
                                @endphp
                                <td class="subject-cell {{ $cellText === '' ? 'subject-empty' : '' }}">{{ $cellText !== '' ? $cellText : '-' }}</td>
                            @endforeach

                            @if($breakExists && $breakInsertIndex === count($periodColumns) && $rowIndex === 0)
                                <td class="break-cell" rowspan="{{ count($teacherRows) }}">Break {{ $breakLabel }}</td>
                            @endif
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ $totalColumns }}" class="subject-cell subject-empty">No teacher assignment found for this class routine. Please assign teachers in teacher class-subject setup.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</body>
</html>

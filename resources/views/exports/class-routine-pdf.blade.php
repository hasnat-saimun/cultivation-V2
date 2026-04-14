<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Class Routine</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            background: #fff;
            margin: 0;
        }
        .routine-sheet {
            border: 1px solid #111827;
            padding: 8px;
        }

        .routine-header {
            text-align: center;
            margin-bottom: 8px;
        }

        .institute-name {
            margin: 0;
            font-size: 24px;
            line-height: 1;
            font-weight: 700;
            color: #111827;
        }

        .routine-title {
            margin: 2px 0;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .routine-meta {
            font-size: 10px;
            color: #374151;
        }

        .routine-meta span {
            margin: 0 8px;
        }

        .routine-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .routine-table th,
        .routine-table td {
            border: 1px solid #111827;
            text-align: center;
            vertical-align: middle;
            padding: 5px 3px;
            color: #111827;
        }

        .routine-table thead th {
            font-size: 10px;
            font-weight: 700;
            background: #eceff3;
        }

        .day-col {
            width: 11%;
            background: #e5e7eb !important;
            font-weight: 700;
        }

        .period-head {
            background: #dbe7f5 !important;
        }

        .period-time {
            background: #f3f4f6 !important;
            font-size: 9px;
            font-weight: 600;
        }

        .break-col {
            width: 7%;
            background: #fbe9c8 !important;
            font-size: 9px;
            font-weight: 700;
        }

        .break-cell {
            background: #fff3d6;
            font-weight: 700;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 9px;
        }

        .day-name {
            background: #f9fafb;
            font-weight: 700;
        }

        .subject-cell {
            font-size: 10px;
            font-weight: 600;
            min-height: 24px;
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

        $dayHeaders = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        $slotMap = [];
        $cellMap = [];
        $breakCounts = [];

        $isBreakText = function (?string $text): bool {
            $normalized = strtolower(trim((string) $text));
            return in_array($normalized, ['break/tiffin time', 'break', 'tiffin', 'tiffin time'], true);
        };

        $formatTimeRange = function (string $start, string $end): string {
            return date('h:i A', strtotime($start)).' - '.date('h:i A', strtotime($end));
        };

        $ordinal = function (int $number): string {
            $abs = abs($number);
            $lastTwo = $abs % 100;
            if ($lastTwo >= 11 && $lastTwo <= 13) {
                return $number.'th';
            }

            return match ($abs % 10) {
                1 => $number.'st',
                2 => $number.'nd',
                3 => $number.'rd',
                default => $number.'th',
            };
        };

        foreach (($entries ?? collect()) as $entry) {
            $dayName = ucfirst(strtolower((string)($entry->class_day ?? '')));
            $start = (string)($entry->start_time ?? '');
            $end = (string)($entry->end_time ?? '');
            $subject = trim((string)($entry->subject_name ?? ''));

            if ($dayName === '' || $start === '' || $end === '') {
                continue;
            }

            if (!in_array($dayName, $dayHeaders, true)) {
                continue;
            }

            $slotKey = $start.'|'.$end;
            if (!isset($slotMap[$slotKey])) {
                $slotMap[$slotKey] = [
                    'key' => $slotKey,
                    'start' => $start,
                    'end' => $end,
                    'label' => $formatTimeRange($start, $end),
                ];
            }

            if (!isset($cellMap[$dayName])) {
                $cellMap[$dayName] = [];
            }

            $cellMap[$dayName][$slotKey] = $subject;

            if ($isBreakText($subject)) {
                $breakCounts[$slotKey] = ($breakCounts[$slotKey] ?? 0) + 1;
            }
        }

        $sortedSlots = collect(array_values($slotMap))->sortBy('start')->values();
        $breakSlotKey = collect($breakCounts)->sortDesc()->keys()->first();
        $breakExists = !empty($breakSlotKey) && isset($slotMap[$breakSlotKey]);
        $breakLabel = $breakExists ? ($slotMap[$breakSlotKey]['label'] ?? 'Break') : '';
        $breakInsertIndex = null;

        if ($breakExists) {
            $breakInsertIndex = 0;
            foreach ($sortedSlots as $slot) {
                if (($slot['key'] ?? '') === $breakSlotKey) {
                    break;
                }

                $breakInsertIndex++;
            }
        }

        $periodSlots = $sortedSlots->filter(function ($slot) use ($breakSlotKey) {
            return ($slot['key'] ?? '') !== $breakSlotKey;
        })->values();

        $periodColumns = [];
        foreach ($periodSlots as $index => $slot) {
            $periodNumber = $index + 1;
            $periodColumns[] = [
                'key' => $slot['key'],
                'period' => $ordinal($periodNumber),
                'time' => $slot['label'],
            ];
        }
    @endphp

    <div class="routine-sheet">
        <div class="routine-header">
            <h2 class="institute-name">{{ $config->instituteName ?? config('app.name', 'Institute Name') }}</h2>
            <div class="routine-title">{{ $routine->title ?? 'Class Routine' }}</div>
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
                    <th class="day-col" rowspan="2">Day</th>
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
                @if(count($periodColumns) > 0 || $breakExists)
                    @foreach($dayHeaders as $dayIndex => $dayLabel)
                        <tr>
                            <td class="day-name">{{ $dayLabel }}</td>
                            @foreach($periodColumns as $idx => $column)
                                @if($breakExists && $breakInsertIndex === $idx && $dayIndex === 0)
                                    <td class="break-cell" rowspan="{{ count($dayHeaders) }}">Break {{ $breakLabel }}</td>
                                @endif
                                @php
                                    $subjectText = trim((string)($cellMap[$dayLabel][$column['key']] ?? ''));
                                @endphp
                                <td class="subject-cell {{ $subjectText === '' ? 'subject-empty' : '' }}">{{ $subjectText !== '' ? $subjectText : '-' }}</td>
                            @endforeach

                            @if($breakExists && $breakInsertIndex === count($periodColumns) && $dayIndex === 0)
                                <td class="break-cell" rowspan="{{ count($dayHeaders) }}">Break {{ $breakLabel }}</td>
                            @endif
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" class="subject-cell subject-empty">No routine rows found for Sunday to Thursday.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</body>
</html>

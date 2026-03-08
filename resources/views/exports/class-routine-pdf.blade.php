<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Class Routine</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1d1d1d;
            background: #ffffff;
            margin: 0;
        }
        .schedule-shell {
            background: #e6e6e8;
            border: 1px solid #cfd4d8;
            padding: 12px;
        }
        .schedule-title {
            margin: 0;
            font-size: 32px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #575f68;
        }
        .schedule-meta {
            margin-top: 6px;
            margin-bottom: 10px;
            color: #4d5258;
            font-size: 11px;
            font-weight: 600;
        }
        .schedule-meta span {
            margin-right: 12px;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background: #f4f4f5;
        }
        .schedule-table th,
        .schedule-table td {
            border: 2px solid #666b72;
            text-align: center;
            vertical-align: middle;
            padding: 8px 6px;
            color: #525861;
        }
        .schedule-table thead th {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .4px;
            text-transform: uppercase;
        }
        .slot-head { background: #eadc9f; width: 18%; }
        .day-sunday { background: #efc9a0; }
        .day-monday { background: #eea3a8; }
        .day-tuesday { background: #ef89d5; }
        .day-wednesday { background: #cda8d8; }
        .day-thursday { background: #beb1e6; }
        .slot-cell {
            background: #ececef;
            font-size: 14px;
            font-weight: 600;
        }
        .subject-cell {
            background: #f2f2f3;
            font-size: 11px;
            font-weight: 700;
            min-height: 36px;
        }
        .subject-empty {
            color: #a3a8ae;
            font-weight: 400;
        }
        .subject-break {
            background: #fff3cd;
            color: #6a4b00;
            font-weight: 800;
        }
    </style>
</head>
<body>
    @php
        $itemClass = \App\Models\classManage::find($routine->assignClass);
        $itemSection = \App\Models\sectionManage::find($routine->assignSection);
        $itemDepartment = \App\Models\Department::find($routine->assignDepartment);
        $itemSession = \App\Models\sessionManage::find($routine->assignSession);

        $dayHeaders = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        $slotMap = [];
        $cellMap = [];

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
                    'label' => date('H:i', strtotime($start)).' - '.date('H:i', strtotime($end)),
                ];
            }

            if (!isset($cellMap[$slotKey])) {
                $cellMap[$slotKey] = [];
            }

            $cellMap[$slotKey][$dayName] = $subject;
        }

        $slots = collect(array_values($slotMap))->sortBy('start')->values();
    @endphp

    <div class="schedule-shell">
        <h3 class="schedule-title">Class Schedule</h3>
        <div class="schedule-meta">
            <span>Class: {{ $itemClass->className ?? '-' }}</span>
            <span>Session: {{ $itemSession->session ?? '-' }}</span>
            <span>Section: {{ $itemSection->section ?? 'All' }}</span>
            <span>Department: {{ $itemDepartment->departmentName ?? 'All' }}</span>
        </div>

        <table class="schedule-table">
            <thead>
                <tr>
                    <th class="slot-head">&nbsp;</th>
                    <th class="day-sunday">Sunday</th>
                    <th class="day-monday">Monday</th>
                    <th class="day-tuesday">Tuesday</th>
                    <th class="day-wednesday">Wednesday</th>
                    <th class="day-thursday">Thursday</th>
                </tr>
            </thead>
            <tbody>
                @if($slots->count() > 0)
                    @foreach($slots as $slot)
                        <tr>
                            <td class="slot-cell">{{ $slot['label'] }}</td>
                            @foreach($dayHeaders as $dayLabel)
                                @php
                                    $subjectText = $cellMap[$slot['key']][$dayLabel] ?? '';
                                    $isBreakCell = strtolower(trim((string)$subjectText)) === 'break/tiffin time';
                                @endphp
                                <td class="subject-cell {{ $subjectText === '' ? 'subject-empty' : '' }} {{ $isBreakCell ? 'subject-break' : '' }}">{{ $subjectText !== '' ? $subjectText : '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="subject-cell subject-empty">No routine rows found for Sunday to Thursday.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</body>
</html>

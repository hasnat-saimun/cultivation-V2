@extends('result.include')
@section('backTitle')
Class Routine View
@endsection
@section('backIndex')
@php
    $config = \App\Models\ServerConfig::orderBy('id', 'DESC')->first();
    $itemClass = \App\Models\classManage::find($routine->assignClass);
    $itemSection = \App\Models\sectionManage::find($routine->assignSection);
    $itemDepartment = \App\Models\Department::find($routine->assignDepartment);
    $itemSession = \App\Models\sessionManage::find($routine->assignSession);
    $printMode = request()->query('print') == '1';

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
            'period_number' => $periodNumber,
            'time' => $slot['label'],
        ];
    }
@endphp

<style>
    @page { size: A4 landscape; margin: 6mm; }
    .routine-view-wrap {
        max-width: 1200px;
        margin: 0 auto;
    }

    .routine-sheet {
        background: #fff;
        border: 1px solid #1f2937;
        border-radius: 8px;
        padding: 12px;
    }

    .routine-header {
        text-align: center;
        margin-bottom: 10px;
    }

    .institute-name {
        margin: 0;
        font-size: 30px;
        font-weight: 700;
        color: #111827;
    }

    .routine-title {
        margin: 2px 0 0;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        text-transform: uppercase;
    }

    .routine-meta {
        margin-top: 4px;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 12px;
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
        padding: 7px 6px;
        color: #111827;
    }

    .routine-table thead th {
        background: #eceff3;
        font-weight: 700;
        font-size: 12px;
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
        font-size: 11px;
        font-weight: 600;
    }

    .break-col {
        width: 7%;
        background: #fbe9c8 !important;
        font-size: 11px;
        font-weight: 700;
    }

    .break-cell {
        background: #fff3d6;
        font-weight: 700;
        letter-spacing: .2px;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        font-size: 11px;
    }

    .day-name {
        background: #f9fafb;
        font-weight: 700;
        font-size: 12px;
    }

    .subject-cell {
        min-height: 42px;
        font-size: 12px;
        font-weight: 600;
    }

    .subject-empty {
        color: #9ca3af;
        font-weight: 500;
    }

    @media print {
        html,
        body,
        #wrapper,
        .dashboard-page-one,
        .dashboard-content-one,
        .routine-view-wrap {
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow: visible !important;
        }

        #preloader,
        .header-menu-one,
        .sidebar-main,
        .breadcrumbs-area,
        .footer-wrap-layout1,
        .no-print,
        .card-header {
            display: none !important;
        }
        .dashboard-content-one {
            margin-left: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        .row,
        .col-12,
        .card,
        .card-body,
        .cultivation {
            margin: 0 !important;
            padding: 0 !important;
        }

        .mb-4,
        .mb-3,
        .gutters-20 {
            margin-bottom: 0 !important;
        }

        .card,
        .card-body {
            border: 0 !important;
            box-shadow: none !important;
            padding: 0 !important;
            background: #fff !important;
        }

        .table-responsive {
            overflow: visible !important;
            border: 0 !important;
        }

        .routine-table {
            width: 100% !important;
            page-break-inside: auto;
        }

        .routine-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .routine-sheet {
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: #fff !important;
            padding: 0 !important;
        }

        .institute-name {
            font-size: 20px !important;
        }

        .routine-title {
            font-size: 13px !important;
        }

        .routine-meta {
            font-size: 10px !important;
            margin-bottom: 4px !important;
        }

        .routine-table th,
        .routine-table td {
            padding: 4px 3px !important;
            font-size: 10px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .break-cell {
            font-size: 9px !important;
        }
    }
</style>

<div class="row gutters-20 mb-4">
    <div class="col-12 routine-view-wrap">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center no-print">
                <span>Class Routine Details</span>
                <div>
                    <a href="{{ route('resultClassRoutineManage') }}" class="btn btn-secondary btn-sm">Back</a>
                    <a href="{{ route('viewResultClassRoutineTeacherWise',['id'=>$routine->id]) }}" class="btn btn-info btn-sm">Teacher Wise</a>
                    <a href="{{ route('downloadResultClassRoutinePdf',['id'=>$routine->id]) }}" class="btn btn-danger btn-sm">Download PDF</a>
                    <a href="{{ route('printResultClassRoutine',['id'=>$routine->id]) }}" target="_blank" class="btn btn-primary btn-sm">Print</a>
                </div>
            </div>
            <div class="card-body cultivation">
                @if(session()->has('error'))
                    <div class="alert alert-danger no-print">{{ session()->get('error') }}</div>
                @endif

                <div class="routine-sheet">
                    <div class="routine-header">
                        <h2 class="institute-name">{{ $config->instituteName ?? config('app.name', 'Institute Name') }}</h2>
                        <p class="routine-title">{{ $routine->title ?? 'Class Routine' }}</p>
                        <div class="routine-meta">
                            <span>Class: {{ $itemClass->className ?? '-' }}</span>
                            <span>Session: {{ $itemSession->session ?? '-' }}</span>
                            <span>Section: {{ $itemSection->section ?? 'All' }}</span>
                            <span>Department: {{ $itemDepartment->departmentName ?? 'All' }}</span>
                        </div>
                    </div>

                    <div class="table-responsive">
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
                                @foreach($periodColumns as $idx => $column)
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
                </div>
            </div>
        </div>
    </div>
</div>

@if($printMode)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.print();
    });
</script>
@endif
@endsection

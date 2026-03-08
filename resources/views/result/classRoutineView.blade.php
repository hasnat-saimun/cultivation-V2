@extends('result.include')
@section('backTitle')
Class Routine View
@endsection
@section('backIndex')
@php
    $itemClass = \App\Models\classManage::find($routine->assignClass);
    $itemSection = \App\Models\sectionManage::find($routine->assignSection);
    $itemDepartment = \App\Models\Department::find($routine->assignDepartment);
    $itemSession = \App\Models\sessionManage::find($routine->assignSession);
    $printMode = request()->query('print') == '1';

    $dayHeaders = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
    $slotMap = [];
    $cellMap = [];

    foreach (($entries ?? collect()) as $entry) {
        $dayName = ucfirst(strtolower((string)($entry->exam_day ?? '')));
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

<style>
    @page { size: A4 landscape; margin: 6mm; }
    .routine-view-wrap {
        max-width: 1200px;
        margin: 0 auto;
    }

    .schedule-shell {
        background: #e6e6e8;
        border-radius: 10px;
        border: 1px solid #cfd4d8;
        padding: 18px;
    }

    .schedule-title {
        margin: 0;
        font-size: 30px;
        line-height: 1;
        font-weight: 900;
        letter-spacing: 1px;
        color: #575f68;
        text-transform: uppercase;
    }

    .schedule-meta {
        margin-top: 8px;
        margin-bottom: 14px;
        color: #4d5258;
        font-size: 13px;
        font-weight: 600;
    }

    .schedule-meta span {
        margin-right: 16px;
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
        padding: 10px 8px;
        color: #525861;
    }

    .schedule-table thead th {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .slot-head {
        background: #eadc9f;
        width: 18%;
    }

    .day-sunday { background: #efc9a0; }
    .day-monday { background: #eea3a8; }
    .day-tuesday { background: #ef89d5; }
    .day-wednesday { background: #cda8d8; }
    .day-thursday { background: #beb1e6; }

    .slot-cell {
        font-size: 18px;
        font-weight: 500;
        width: 18%;
        background: #ececef;
    }

    .subject-cell {
        background: #f2f2f3;
        height: auto;
        font-size: 12px;
        font-weight: 700;
        color: #4f5560;
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

        .schedule-table {
            width: 100% !important;
            page-break-inside: auto;
        }

        .schedule-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        

        .schedule-table thead th {
            font-size: 13px;
        }
        .schedule-shell {
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: #fff !important;
            padding: 0 !important;
        }

        .schedule-title {
            font-size: 16px;
            margin-bottom: 2px !important;
        }

        .schedule-meta {
            margin: 2px 0 6px 0 !important;
            font-size: 10px !important;
        }

        .slot-cell {
            font-size: 11px !important;
        }

        .subject-cell {
            font-size: 10px;
            line-height: 1.2;
            padding: 4px 3px !important;
        }

        .schedule-table th,
        .schedule-table td {
            border-color: #666b72 !important;
            padding: 5px 4px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
                    <a href="{{ route('downloadResultClassRoutinePdf',['id'=>$routine->id]) }}" class="btn btn-danger btn-sm">Download PDF</a>
                    <a href="{{ route('printResultClassRoutine',['id'=>$routine->id]) }}" target="_blank" class="btn btn-primary btn-sm">Print</a>
                </div>
            </div>
            <div class="card-body cultivation">
                @if(session()->has('error'))
                    <div class="alert alert-danger no-print">{{ session()->get('error') }}</div>
                @endif

                <div class="schedule-shell">
                    <h3 class="schedule-title">Class Schedule</h3>
                    <div class="schedule-meta">
                        <span>Class: {{ $itemClass->className ?? '-' }}</span>
                        <span>Session: {{ $itemSession->session ?? '-' }}</span>
                        <span>Section: {{ $itemSection->section ?? 'All' }}</span>
                        <span>Department: {{ $itemDepartment->departmentName ?? 'All' }}</span>
                    </div>

                    <div class="table-responsive">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th class="slot-head">Time/Days</th>
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

@extends('result.include')
@section('backTitle')
Teacher Wise Class Routine
@endsection
@section('backIndex')
@php
    $config = \App\Models\ServerConfig::orderBy('id', 'DESC')->first();
    $itemClass = \App\Models\classManage::find($routine->assignClass);
    $itemSection = \App\Models\sectionManage::find($routine->assignSection);
    $itemDepartment = \App\Models\Department::find($routine->assignDepartment);
    $itemSession = \App\Models\sessionManage::find($routine->assignSession);
    $printMode = request()->query('print') == '1';

    $periodColumns = $teacherWise['periodColumns'] ?? [];
    $breakExists = (bool)($teacherWise['breakExists'] ?? false);
    $breakLabel = (string)($teacherWise['breakLabel'] ?? '');
    $breakInsertIndex = $teacherWise['breakInsertIndex'] ?? null;
    $teacherRows = $teacherWise['teacherRows'] ?? [];

    $totalColumns = 1 + count($periodColumns) + ($breakExists ? 1 : 0);
@endphp

<style>
    @page { size: A4 landscape; margin: 6mm; }

    .routine-view-wrap {
        max-width: 1400px;
        margin: 0 auto;
    }

    .routine-sheet {
        background: #fff;
        border: 1px solid #333;
        border-radius: 0;
        padding: 6px;
    }

    .routine-header {
        text-align: center;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 1px solid #ddd;
    }

    .institute-name {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #000;
        letter-spacing: .3px;
    }

    .routine-title {
        margin: 1px 0 0;
        font-size: 13px;
        font-weight: 700;
        color: #000;
    }

    .routine-subtitle {
        margin: 0;
        font-size: 11px;
        font-weight: 600;
        color: #333;
        display: none;
    }

    .routine-meta {
        margin-top: 1px;
        font-size: 9px;
        font-weight: 500;
        color: #333;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    .routine-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 9px;
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
        background: #e0e0e0;
        font-weight: 700;
        font-size: 9px;
        padding: 2px;
    }

    .teacher-col {
        width: 15%;
        background: #f0f0f0 !important;
        font-weight: 700;
        text-align: left;
        padding-left: 4px !important;
    }

    .period-head {
        background: #e8e8e8 !important;
        font-weight: 700;
        font-size: 8px;
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
        padding: 4px !important;
    }

    .teacher-name {
        background: #f9f9f9;
        font-weight: 700;
        text-align: left !important;
        font-size: 8px;
        padding-left: 4px !important;
        word-break: break-word;
    }

    .subject-cell {
        min-height: 35px;
        font-size: 8px;
        font-weight: 600;
        white-space: normal;
        line-height: 1.2;
        word-break: break-word;
        padding: 2px 1px !important;
    }

    .subject-empty {
        color: #bbb;
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

        .routine-sheet {
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: #fff !important;
            padding: 0 !important;
        }

        .institute-name {
            font-size: 18px !important;
        }

        .routine-title {
            font-size: 12px !important;
        }

        .routine-subtitle {
            font-size: 10px !important;
        }

        .routine-meta {
            font-size: 8px !important;
            margin-bottom: 2px !important;
        }

        .routine-table th,
        .routine-table td {
            padding: 2px 1px !important;
            font-size: 8px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .subject-cell {
            line-height: 1.2 !important;
            min-height: 30px !important;
        }

        .break-cell {
            font-size: 10px !important;
        }

        .routine-header {
            margin-bottom: 4px !important;
            padding-bottom: 2px !important;
        }
    }
</style>

<div class="row gutters-20 mb-4">
    <div class="col-12 routine-view-wrap">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center no-print">
                <span>Teacher Wise Class Routine</span>
                <div>
                    <a href="{{ route('resultClassRoutineManage') }}" class="btn btn-secondary btn-sm">Back</a>
                    <a href="{{ route('viewResultClassRoutine',['id'=>$routine->id]) }}" class="btn btn-info btn-sm">Class Wise</a>
                    <a href="{{ route('downloadResultClassRoutineTeacherWisePdf',['id'=>$routine->id]) }}" class="btn btn-danger btn-sm">Download PDF</a>
                    <a href="{{ route('printResultClassRoutineTeacherWise',['id'=>$routine->id]) }}" target="_blank" class="btn btn-primary btn-sm">Print</a>
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
                        <p class="routine-subtitle">Teacher Wise Routine</p>
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

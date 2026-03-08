@extends('result.include')
@section('backTitle')
Get Admit Card
@endsection
@php
    $classData = \App\Models\classManage::find($classId);
    $sectionData = \App\Models\sectionManage::find($groupId);
    $sessionData = \App\Models\sessionManage::find($sessionId);
    $examData = \App\Models\Exam::find($examId);
    $departmentData = !empty($departmentId) ? \App\Models\Department::find($departmentId) : null;

    $className = $classData ? $classData->className : '-';
    $sectionName = $sectionData ? $sectionData->section : 'All';
    $sessionName = $sessionData ? $sessionData->session : '-';
    $examName = $examData ? $examData->examName : '-';
    $departmentName = $departmentData ? $departmentData->departmentName : 'All';

    $routineAttachment = $routine->attachment ?? null;
    $routineUrl = !empty($routineAttachment) ? asset('public/upload/image/cultivation/examRoutine/'.$routineAttachment) : null;
    $routineExt = !empty($routineAttachment) ? strtolower(pathinfo($routineAttachment, PATHINFO_EXTENSION)) : null;
    $routineEntries = collect($routine->entries ?? []);
    $halfCount = (int) ceil($routineEntries->count() / 2);
    $leftEntries = $routineEntries->slice(0, $halfCount)->values();
    $rightEntries = $routineEntries->slice($halfCount)->values();

    $serverConfig = \App\Models\ServerConfig::latest('id')->first();
    $headMasterSign = null;
    if (!empty($serverConfig?->principalSign)) {
        $headMasterSign = preg_match('~^https?://~i', $serverConfig->principalSign)
            ? $serverConfig->principalSign
            : asset('/public/upload/image/cultivation/'.$serverConfig->principalSign);
    }
@endphp
@section('backIndex')
<style>
    .admit-card-wrapper {
        background: #fff;
    }
    .admit-card-item {
        border: 2px dashed #18b7a0;
        padding: 14px;
        margin: 20px;
        page-break-after: always;
        background: #fff;
    }
    .admit-title {
        border: 2px solid #39c8b6;
        display: inline-block;
        padding: 3px 20px;
        font-weight: 700;
        letter-spacing: .5px;
        margin: 8px 0 12px;
        border-radius: 18px;
    }
    .student-photo {
        width: 100%;
        max-width: 102px;
        height: 118px;
        object-fit: cover;
        border: 1px solid #222;
    }
    .meta-table {
        width: 100%;
        font-size: 14px;
    }
    .meta-table td {
        vertical-align: top;
        padding: 0;
        line-height: 1.35;
    }
    .meta-label {
        font-weight: 700;
        min-width: 112px;
        display: inline-block;
    }
    .meta-col-gap {
        width: 30px;
    }
    .routine-title {
        margin-top: 10px;
        border: 1px solid #111;
        background: #3dc8b6;
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        text-align: center;
        padding: 2px 8px;
    }
    .routine-box {
        min-height: 328px;
    }
    .routine-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
        font-size: 14px;
    }
    .routine-table th,
    .routine-table td {
        border: 1px solid #111;
        padding: 2px 4px;
        line-height: 1.1;
        vertical-align: top;
    }
    .routine-table th {
        font-weight: 700;
        text-align: left;
    }
    .split-cell {
        width: 6px;
        background: #fff;
        border: 0 !important;
    }
    .signature-row {
        margin-top: 16px;
    }
    .instruction-title {
        font-size: 14px;
        font-weight: 700;
        margin-top: 8px;
        margin-bottom: 2px;
    }
    .instruction-list {
        margin-bottom: 0;
        padding-left: 18px;
        font-size: 13px;
        line-height: 1.2;
    }
    .routine-fallback {
        border: 1px solid #111;
        min-height: 320px;
        padding: 6px;
    }
    .routine-fallback img {
        width: 100%;
        height: auto;
        object-fit: contain;
    }
    .routine-fallback iframe {
        width: 100%;
        height: 380px;
        border: 0;
    }
    .signature-line {
        display: inline-block;
        min-width: 210px;
        border-top: 1px solid #111;
        padding-top: 4px;
        text-align: center;
        font-size: 13px;
    }
    .head-sign-image {
        display: block;
        height: 42px;
        width: auto;
        max-width: 140px;
        margin: 0 420px;
        right: -10px !important;
        object-fit: contain;
    }
    @media print {
        .bg-ash{
            background: #fff !important;
        }
        body {
            background: #fff !important;
            overflow: visible !important;
        }
        .breadcrumbs-area,
        .sidebar-main,
        .header-menu-one,
        .footer-wrap-layout1,
        .d-print-none {
            display: none !important;
        }
        .dashboard-content-one,
        .card,
        .card-body {
            padding: 0 !important;
            margin: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
        }
        .admit-card-item {
            margin: 0;
            min-height: 96vh;
            page-break-after: always;
        }
        .admit-card-item:last-child {
            page-break-after: auto;
        }
        .meta-table {
            font-size: 13px;
        }
        .routine-table {
            font-size: 13px;
        }
    }
</style>

@if($studentList->count() > 0)
    <div class="row d-print-none mb-3">
        <div class="col-6 col-md-2 mb-2"><b>Class:</b> {{ $className }}</div>
        <div class="col-6 col-md-2 mb-2"><b>Group:</b> {{ $sectionName }}</div>
        <div class="col-6 col-md-2 mb-2"><b>Session:</b> {{ $sessionName }}</div>
        <div class="col-6 col-md-3 mb-2"><b>Exam:</b> {{ $examName }}</div>
        <div class="col-6 col-md-3 mb-2"><b>Department:</b> {{ $departmentName }}</div>
    </div>

    <div class="mb-3 d-print-none">
        <button class="btn btn-success btn-lg" onclick="window.print()"><i class="fa-light fa-print"></i> All Print</button>
        <a href="{{ route('admitCardRoutine') }}" class="btn btn-lg btn-primary"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
    </div>

    <div class="admit-card-wrapper" id="admitCardRoot">
        @foreach($studentList as $std)
            <div class="admit-card-item">
                <div class="text-center">
                    @include('cultivation.stdIdHeader')
                    <div class="admit-title">Admit Card</div>
                </div>

                <div class="row align-items-start">
                    <div class="col-2 text-center">
                        @if(!empty($std->avatar))
                            <img src="{{ asset('/public/upload/image/student/') }}/{{ $std->avatar }}" alt="{{ $std->stdId }}" class="student-photo">
                        @else
                            <img src="{{ asset('/public/back-office/img/') }}/avatar.jpeg" alt="{{ $std->stdId }}" class="student-photo">
                        @endif
                    </div>
                    <div class="col-10">
                        <table class="meta-table">
                            <tr>
                                <td><span class="meta-label">Student ID</span>: {{ $std->stdId }}</td>
                                <td class="meta-col-gap"></td>
                                <td><span class="meta-label">Roll</span>: {{ is_numeric($std->rollNumber) ? str_pad((string) $std->rollNumber, 2, '0', STR_PAD_LEFT) : $std->rollNumber }}</td>
                                <td><span class="meta-label">Group</span>: {{ $departmentName === 'All' ? 'N/A' : $departmentName }}</td>
                            </tr>
                            <tr>
                                <td><span class="meta-label">Student's Name</span>: {{ trim(($std->fullName ?? '').' '.($std->sureName ?? '')) }}</td>
                                <td class="meta-col-gap"></td>
                                <td><span class="meta-label">Class</span>: {{ $className }}</td>
                                <td><span class="meta-label">Exam Name</span>: {{ $examName }}</td>
                            </tr>
                            <tr>
                                <td><span class="meta-label">Father's Name</span>: {{ $std->fatherName ?? '-' }}</td>
                                <td class="meta-col-gap"></td>
                                <td><span class="meta-label">Shift</span>: {{ $std->shiftName ?? 'Day' }}</td>
                                <td><span class="meta-label">Registration</span>: {{ $std->registrationNumber ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><span class="meta-label">Mother's Name</span>: {{ $std->motherName ?? '-' }}</td>
                                <td class="meta-col-gap"></td>
                                <td><span class="meta-label">Section</span>: {{ $sectionName }}</td>
                                <td><span class="meta-label">Session</span>: {{ $sessionName }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="routine-box">
                    @if($routineEntries->count() > 0)
                        <div class="routine-title">{{ $routine->title ?? 'Exam Routine' }}</div>
                        <table class="routine-table">
                            <thead>
                                <tr>
                                    <th style="width:9%;">Date</th>
                                    <th style="width:9%;">Day</th>
                                    <th style="width:12%;">Time</th>
                                    <th style="width:20%;">Subject</th>
                                    <th class="split-cell"></th>
                                    <th style="width:9%;">Date</th>
                                    <th style="width:9%;">Day</th>
                                    <th style="width:12%;">Time</th>
                                    <th style="width:20%;">Subject</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i < max($leftEntries->count(), $rightEntries->count()); $i++)
                                    @php
                                        $left = $leftEntries[$i] ?? null;
                                        $right = $rightEntries[$i] ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $left && !empty($left->exam_date) ? \Carbon\Carbon::parse($left->exam_date)->format('d-m-Y') : '' }}</td>
                                        <td>{{ $left->exam_day ?? '' }}</td>
                                        <td>
                                            @if($left && !empty($left->start_time) && !empty($left->end_time))
                                                {{ \Carbon\Carbon::parse($left->start_time)->format('h:i A') }}-{{ \Carbon\Carbon::parse($left->end_time)->format('h:i A') }}
                                            @else
                                                {{ $left->exam_time ?? '' }}
                                            @endif
                                        </td>
                                        <td>{{ data_get($left, 'subject.subjectName', '') }}</td>
                                        <td class="split-cell"></td>
                                        <td>{{ $right && !empty($right->exam_date) ? \Carbon\Carbon::parse($right->exam_date)->format('d-m-Y') : '' }}</td>
                                        <td>{{ $right->exam_day ?? '' }}</td>
                                        <td>
                                            @if($right && !empty($right->start_time) && !empty($right->end_time))
                                                {{ \Carbon\Carbon::parse($right->start_time)->format('h:i A') }}-{{ \Carbon\Carbon::parse($right->end_time)->format('h:i A') }}
                                            @else
                                                {{ $right->exam_time ?? '' }}
                                            @endif
                                        </td>
                                        <td>{{ data_get($right, 'subject.subjectName', '') }}</td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    @elseif(!empty($routineUrl))
                        <div class="routine-fallback">
                            @if($routineExt === 'pdf')
                                <iframe src="{{ $routineUrl }}"></iframe>
                            @else
                                <img src="{{ $routineUrl }}" alt="Exam Routine">
                            @endif
                        </div>
                    @else
                        <div class="text-center py-5 routine-fallback">
                            <h6 class="mb-2">Exam Routine Not Found</h6>
                            <p class="mb-0">Please add routine rows from Result Management - Exam - Exam Routine for this class/session{{ !empty($departmentId) ? '/department' : '' }}.</p>
                        </div>
                    @endif
                </div>

                <div class="instruction-title">Instructions</div>
                <ol class="instruction-list">
                    <li>You must bring the admit card with you during the exam.</li>
                    <li>You cannot carry any other paper except the admit card. If any unfair means are used in the exam, the decision of authority will be final.</li>
                    <li>You must be present in the exam hall at least 20 minutes before the exam starts.</li>
                </ol>

                <div class="row signature-row">
                    <div class="col-6">
                        <span class="signature-line">Guardian</span>
                    </div>
                    <div class="col-6 text-right">
                        @if(!empty($headMasterSign))
                            <img src="{{ $headMasterSign }}" alt="Head Master Signature" class="head-sign-image">
                        @endif
                        <span class="signature-line">Head of Institution</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mb-3 d-print-none">
        <button class="btn btn-success btn-lg" onclick="window.print()"><i class="fa-light fa-print"></i> All Print</button>
        <a href="{{ route('admitCardRoutine') }}" class="btn btn-lg btn-primary"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
    </div>
@else
    <div class="alert alert-info">Sorry! No data found</div>
    <div class="mb-4"><a href="{{ route('admitCardRoutine') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
@endif
@endsection

@extends('cultivation.include')
@section('backTitle') Attendance Report @endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-md-11 col-12 mx-auto">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Attendance Report</h5>
                <div class="d-flex gap-2">
                    @if(!empty($filters['classId']))
                        <a class="btn btn-sm btn-success" href="{{ route('attendanceExport', array_filter($filters)) }}">Export CSV</a>
                        <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('attendancePrint', array_filter($filters)) }}">Print / Save PDF</a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @include('attendance._printHeader')
                <style>
                    /* Unified styling for daily attendance tables */
                    .att-daily-container{display:flex;justify-content:center;}
                    .att-daily-wrapper{width:100%;max-width:760px;background:#ffffff;border:1px solid #e2e6ea;border-radius:6px;padding:14px 18px;box-shadow:0 2px 4px rgba(0,0,0,0.05);}
                    .att-daily-wrapper .table-responsive{margin-bottom:0;}
                    table.att-daily,table.att-daily-summary{width:100% !important;border-collapse:collapse;margin:0 auto;table-layout:auto;}
                    @media (min-width:600px){table.att-daily,table.att-daily-summary{table-layout:fixed;}}
                    table.att-daily th,table.att-daily td,table.att-daily-summary th,table.att-daily-summary td{padding:5px 7px;font-size:12px;vertical-align:middle;}
                    table.att-daily th,table.att-daily-summary th{background:#f8f9fa;font-weight:600;}
                    table.att-daily tbody tr:nth-child(even),table.att-daily-summary tbody tr:nth-child(even){background:#fcfcfc;}
                    .att-daily-wrapper .legend{margin:8px 0 6px 0;font-size:11px;}
                    table.att-daily td.small-meta{font-size:11px;color:#666;}
                    table.att-daily td.status-cell,table.att-daily-summary td.status-cell{font-weight:600;letter-spacing:.5px;}
                    @media (min-width:992px){.att-daily-wrapper{padding:18px 22px;}}
                </style>
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                <form method="GET" action="{{ route('attendanceReport') }}" class="row align-items-end">
                    <div class="col-12 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setTodayAndSubmit()">Today</button>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" value="{{ $filters['date'] }}" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Class *</label>
                        <select name="classId" class="form-select form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $cls)
                                <option value="{{ $cls->id }}" {{ (string)$filters['classId'] === (string)$cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Session</label>
                        <select name="sessionId" class="form-select form-control">
                            <option value="">Select</option>
                            @foreach($sessions as $sess)
                                <option value="{{ $sess->id }}" {{ (string)$filters['sessionId'] === (string)$sess->id ? 'selected' : '' }}>{{ $sess->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Section / Group</label>
                        <select name="sectionId" class="form-select form-control">
                            <option value="">Select</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec->id }}" {{ (string)$filters['sectionId'] === (string)$sec->id ? 'selected' : '' }}>{{ $sec->section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Student ID</label>
                        <input type="number" name="studentId" class="form-control" value="{{ $filters['studentId'] }}" placeholder="Exact ID">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Student Name</label>
                        <input type="text" name="studentName" class="form-control" value="{{ $filters['studentName'] }}" placeholder="Partial name">
                    </div>
                    <div class="col-12 mb-3">
                        <button type="submit" class="btn btn-primary">Load Report</button>
                        <a href="{{ route('attendanceIndex') }}" class="btn btn-secondary">Mark Attendance</a>
                    </div>
                </form>
                <script>
                    function setTodayAndSubmit(){
                        const today = new Date().toISOString().substring(0,10);
                        const dateInput = document.querySelector('input[name="date"]');
                        if(dateInput){ dateInput.value = today; }
                        document.querySelector('form[action="{{ route('attendanceReport') }}"]').submit();
                    }
                </script>

                @php
                    // Map statuses to codes similar to monthly view
                    $statusMap = ['Present' => 'P', 'Absent' => 'A', 'Late' => 'T', 'Excused' => 'E'];
                    $totals = ['P'=>0,'A'=>0,'T'=>0,'E'=>0];
                    foreach($records as $rr){
                        $code = $statusMap[$rr->status] ?? substr($rr->status,0,1);
                        if(isset($totals[$code])){ $totals[$code]++; }
                    }
                @endphp
                <div class="att-daily-container">
                <div class="att-daily-wrapper">
                    <div class="table-responsive">
                    <table class="table table-bordered table-sm att-daily">
                        <thead>
                            <tr>
                                <th style="width:60px;">Sl</th>
                                <th style="white-space:nowrap;">Class Roll</th>
                                <th style="white-space:nowrap;">Student</th>
                                <th style="width:80px;text-align:center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $idx => $r)
                                @php $code = $statusMap[$r->status] ?? substr($r->status,0,1); @endphp
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $r->student && $r->student->rollNumber ? $r->student->rollNumber : '' }}</td>
                                    <td style="white-space:nowrap;">
                                        @php $nm = $r->student ? trim(($r->student->fullName ?? '').' '.($r->student->sureName ?? '')) : ''; @endphp
                                        {{ $nm }}
                                        <div class="small-meta">ID: {{ $r->student_id }}</div>
                                    </td>
                                    <td class="status-cell" style="text-align:center;">{{ $code }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">{{ empty($filters['classId']) ? 'Select filters to view report.' : 'No attendance found for selection.' }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    <div class="legend"><strong>Legend:</strong> Present = P, Absent = A, Tardy = T, Excused = E</div>
                    @if($records->count())
                    <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm att-daily-summary">
                        <thead>
                            <tr>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Excused</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center fw-semibold">{{ $totals['P'] }}</td>
                                <td class="text-center fw-semibold">{{ $totals['A'] }}</td>
                                <td class="text-center fw-semibold">{{ $totals['T'] }}</td>
                                <td class="text-center fw-semibold">{{ $totals['E'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    @endif
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

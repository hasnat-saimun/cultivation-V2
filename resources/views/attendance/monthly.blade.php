@extends('cultivation.include')
@section('backTitle') Monthly Attendance @endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Monthly Attendance</h5>
                <div class="d-flex gap-2">
                    @if(!empty($filters['classId']))
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('attendanceMonthlyPrint', array_filter($filters)) }}">Print View</a>
                        <a class="btn btn-sm btn-success" href="{{ route('attendanceMonthlyExport', array_filter($filters)) }}">Export CSV</a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @include('attendance._printHeader')
                <style>
                    @media print{
                        .no-print{display:none !important;}
                        .table{font-size:10px;}
                    }
                    .weekend{background:#f7f7f7;}
                    .weekday-row th{font-weight:400;color:#555;background:#fafafa;}
                </style>
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                <form method="GET" action="{{ route('attendanceMonthly') }}" class="row align-items-end no-print">
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-select form-control">
                            @for($m=1;$m<=12;$m++)
                                <option value="{{ $m }}" {{ (int)$filters['month'] === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" value="{{ $filters['year'] ?? date('Y') }}" class="form-control" />
                    </div>
                    <div class="col-md-2 mb-3">
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
                    <div class="col-12 mb-3">
                        <button type="submit" class="btn btn-primary">Load</button>
                    </div>
                </form>
                @if(!empty($filters['classId']))
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th style="white-space:nowrap;">Student</th>
                                @for($d=1;$d<=$daysInMonth;$d++)
                                    @php $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk,['Fri','Sat']); @endphp
                                    <th style="width:26px;text-align:center;" class="{{ $isWknd ? 'weekend' : '' }}">{{ $d }}</th>
                                @endfor
                                <th style="white-space:nowrap;">Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Excused</th>
                            </tr>
                            <tr class="weekday-row">
                                <th></th>
                                @for($d=1;$d<=$daysInMonth;$d++)
                                    @php $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk,['Fri','Sat']); @endphp
                                    <th class="text-center {{ $isWknd ? 'weekend' : '' }}">{{ $wk }}</th>
                                @endfor
                                <th colspan="4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $st)
                                <tr>
                                    <td style="white-space:nowrap;">
                                        {{ $st->rollNumber ? $st->rollNumber.'. ' : '' }}
                                        {{ trim(($st->fullName ?? '').' '.($st->sureName ?? '')) }}
                                    </td>
                                    @for($d=1;$d<=$daysInMonth;$d++)
                                        @php $cell = $matrix[$st->id][$d] ?? ''; $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk,['Fri','Sat']); @endphp
                                        <td style="text-align:center;" class="{{ $isWknd ? 'weekend' : '' }}">{{ $cell }}</td>
                                    @endfor
                                    @php $s = $summary[$st->id] ?? ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0]; @endphp
                                    <td>{{ $s['present'] }}</td>
                                    <td>{{ $s['absent'] }}</td>
                                    <td>{{ $s['late'] }}</td>
                                    <td>{{ $s['excused'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $daysInMonth + 5 }}" class="text-center">No students found for selection.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 small">
                    <strong>Legend:</strong> Present = P, Absent = A, Tardy = T, Excused = E
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Totals</th>
                                @for($d=1;$d<=$daysInMonth;$d++)
                                    @php $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk,['Fri','Sat']); @endphp
                                    <th class="text-center {{ $isWknd ? 'weekend' : '' }}">{{ $d }}</th>
                                @endfor
                                <th colspan="4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Present</strong></td>
                                @for($d=1;$d<=$daysInMonth;$d++)
                                    <td class="text-center">{{ $dayTotals['P'][$d] ?? 0 }}</td>
                                @endfor
                                <td colspan="4"></td>
                            </tr>
                            <tr>
                                <td><strong>Absent</strong></td>
                                @for($d=1;$d<=$daysInMonth;$d++)
                                    <td class="text-center">{{ $dayTotals['A'][$d] ?? 0 }}</td>
                                @endfor
                                <td colspan="4"></td>
                            </tr>
                            <tr>
                                <td><strong>Late</strong></td>
                                @for($d=1;$d<=$daysInMonth;$d++)
                                    <td class="text-center">{{ $dayTotals['T'][$d] ?? 0 }}</td>
                                @endfor
                                <td colspan="4"></td>
                            </tr>
                            <tr>
                                <td><strong>Excused</strong></td>
                                @for($d=1;$d<=$daysInMonth;$d++)
                                    <td class="text-center">{{ $dayTotals['E'][$d] ?? 0 }}</td>
                                @endfor
                                <td colspan="4"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row mt-5">
                    <div class="col-6 text-center">
                        <div style="border-top:1px solid #333; display:inline-block; padding-top:4px; min-width:220px;">Class Teacher</div>
                    </div>
                    <div class="col-6 text-center">
                        <div style="border-top:1px solid #333; display:inline-block; padding-top:4px; min-width:220px;">Principal</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

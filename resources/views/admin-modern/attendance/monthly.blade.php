@extends('admin-modern.layouts.app')

@section('title', 'Monthly Attendance')

@section('content')
    <x-admin-modern.page-header
        title="Monthly Attendance"
        subtitle="Modern parallel wrapper for monthly attendance matrix using existing ERP monthly flow"
        :breadcrumb="['Home', 'Attendance', 'Monthly']"
    />

    <div class="am-btn-row" style="justify-content:flex-start; margin-bottom:0.75rem; gap:0.5rem; flex-wrap:wrap;" aria-label="Attendance quick links">
        <a href="{{ route('adminModernAttendanceIndex') }}" class="am-btn-outline" title="Open mark attendance">Mark Attendance</a>
        <a href="{{ route('adminModernAttendanceReport') }}" class="am-btn-outline" title="Open attendance report">Attendance Report</a>
        <a href="{{ route('adminModernAttendanceMonthly') }}" class="am-btn-primary" aria-current="page" title="Open monthly attendance">Monthly Sheet</a>
    </div>

    <x-admin-modern.table-shell title="Monthly Attendance">
        <div class="am-btn-row" style="justify-content:space-between; margin-bottom:0.8rem; flex-wrap:wrap; gap:0.6rem;">
            <div></div>
            @if(!empty($filters['classId']))
                <div class="am-btn-row" style="gap:0.5rem;">
                    <a class="am-btn-outline" target="_blank" href="{{ route('attendanceMonthlyPrint', array_filter($filters)) }}">Print View</a>
                    <a class="am-btn-primary" href="{{ route('attendanceMonthlyExport', array_filter($filters)) }}">Export CSV</a>
                </div>
            @endif
        </div>

        <form method="GET" action="{{ route('attendanceMonthly') }}" style="margin-bottom:0.9rem;">
            <div class="am-grid am-grid-3" style="margin-bottom:0.7rem;">
                <div>
                    <label for="monthlyMonth" style="display:block; font-weight:600; margin-bottom:0.35rem;">Month</label>
                    <select id="monthlyMonth" name="month" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (int)$filters['month'] === $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label for="monthlyYear" style="display:block; font-weight:600; margin-bottom:0.35rem;">Year</label>
                    <input id="monthlyYear" type="number" name="year" value="{{ $filters['year'] ?? date('Y') }}" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                </div>

                <div>
                    <label for="monthlyClassId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Class *</label>
                    <select id="monthlyClassId" name="classId" required style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select</option>
                        @foreach($classes as $cls)
                            <option value="{{ $cls->id }}" {{ (string)$filters['classId'] === (string)$cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="monthlySessionId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Session</label>
                    <select id="monthlySessionId" name="sessionId" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select</option>
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ (string)$filters['sessionId'] === (string)$sess->id ? 'selected' : '' }}>{{ $sess->session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="monthlySectionId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Section / Group</label>
                    <select id="monthlySectionId" name="sectionId" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}" {{ (string)$filters['sectionId'] === (string)$sec->id ? 'selected' : '' }}>{{ $sec->section }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="am-btn-row">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Load</button>
            </div>
        </form>

        @if(!empty($filters['classId']))
            <div style="overflow:auto; border:1px solid var(--am-border); border-radius:12px;">
                <table class="am-table" style="min-width:1100px;">
                    <thead>
                        <tr>
                            <th style="white-space:nowrap;">Student</th>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk, ['Fri', 'Sat']); @endphp
                                <th style="text-align:center; background:{{ $isWknd ? '#f7f8fa' : 'transparent' }};">{{ $d }}</th>
                            @endfor
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Excused</th>
                        </tr>
                        <tr>
                            <th></th>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk, ['Fri', 'Sat']); @endphp
                                <th style="text-align:center; font-weight:500; color:#667085; background:{{ $isWknd ? '#f7f8fa' : '#fafafa' }};">{{ $wk }}</th>
                            @endfor
                            <th colspan="4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $st)
                            <tr>
                                <td style="white-space:nowrap;">
                                    {{ $st->rollNumber ? $st->rollNumber . '. ' : '' }}{{ trim(($st->fullName ?? '') . ' ' . ($st->sureName ?? '')) }}
                                </td>
                                @for($d = 1; $d <= $daysInMonth; $d++)
                                    @php $cell = $matrix[$st->id][$d] ?? ''; $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk, ['Fri', 'Sat']); @endphp
                                    <td style="text-align:center; background:{{ $isWknd ? '#f7f8fa' : 'transparent' }};">{{ $cell }}</td>
                                @endfor
                                @php $s = $summary[$st->id] ?? ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0]; @endphp
                                <td>{{ $s['present'] }}</td>
                                <td>{{ $s['absent'] }}</td>
                                <td>{{ $s['late'] }}</td>
                                <td>{{ $s['excused'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $daysInMonth + 5 }}" style="text-align:center;">No students found for selection.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:0.7rem; font-size:0.88rem;"><strong>Legend:</strong> Present = P, Absent = A, Tardy = T, Excused = E</div>

            <div style="overflow:auto; border:1px solid var(--am-border); border-radius:12px; margin-top:0.8rem;">
                <table class="am-table" style="min-width:980px;">
                    <thead>
                        <tr>
                            <th>Totals</th>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk, ['Fri', 'Sat']); @endphp
                                <th style="text-align:center; background:{{ $isWknd ? '#f7f8fa' : 'transparent' }};">{{ $d }}</th>
                            @endfor
                            <th colspan="4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Present</strong></td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                <td style="text-align:center;">{{ $dayTotals['P'][$d] ?? 0 }}</td>
                            @endfor
                            <td colspan="4"></td>
                        </tr>
                        <tr>
                            <td><strong>Absent</strong></td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                <td style="text-align:center;">{{ $dayTotals['A'][$d] ?? 0 }}</td>
                            @endfor
                            <td colspan="4"></td>
                        </tr>
                        <tr>
                            <td><strong>Late</strong></td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                <td style="text-align:center;">{{ $dayTotals['T'][$d] ?? 0 }}</td>
                            @endfor
                            <td colspan="4"></td>
                        </tr>
                        <tr>
                            <td><strong>Excused</strong></td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                <td style="text-align:center;">{{ $dayTotals['E'][$d] ?? 0 }}</td>
                            @endfor
                            <td colspan="4"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin-modern.table-shell>
@endsection

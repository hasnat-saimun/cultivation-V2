@extends('admin-modern.layouts.app')

@section('title', 'Attendance Report')

@section('content')
    <x-admin-modern.page-header
        title="Attendance Report"
        subtitle="Modern parallel wrapper for attendance reporting using existing ERP report flow"
        :breadcrumb="['Home', 'Attendance', 'Report']"
    />

    <div class="am-btn-row" style="justify-content:flex-start; margin-bottom:0.75rem; gap:0.5rem; flex-wrap:wrap;" aria-label="Attendance quick links">
        <a href="{{ route('adminModernAttendanceIndex') }}" class="am-btn-outline" title="Open mark attendance">Mark Attendance</a>
        <a href="{{ route('adminModernAttendanceReport') }}" class="am-btn-primary" aria-current="page" title="Open attendance report">Attendance Report</a>
        <a href="{{ route('adminModernAttendanceMonthly') }}" class="am-btn-outline" title="Open monthly attendance">Monthly Sheet</a>
    </div>

    <x-admin-modern.table-shell title="Attendance Report">
        <div class="am-btn-row" style="justify-content:space-between; margin-bottom:0.8rem; flex-wrap:wrap; gap:0.6rem;">
            <button type="button" class="am-btn-outline" onclick="setTodayAndSubmit()">Today</button>
            @if(!empty($filters['classId']))
                <div class="am-btn-row" style="gap:0.5rem;">
                    <a class="am-btn-primary" href="{{ route('attendanceExport', array_filter($filters)) }}">Export CSV</a>
                    <a class="am-btn-outline" target="_blank" href="{{ route('attendancePrint', array_filter($filters)) }}">Print / Save PDF</a>
                </div>
            @endif
        </div>

        <form method="GET" action="{{ route('attendanceReport') }}" style="margin-bottom:0.9rem;">
            <div class="am-grid am-grid-3" style="margin-bottom:0.7rem;">
                <div>
                    <label for="reportDate" style="display:block; font-weight:600; margin-bottom:0.35rem;">Date</label>
                    <input id="reportDate" type="date" name="date" value="{{ $filters['date'] }}" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                </div>

                <div>
                    <label for="reportClassId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Class *</label>
                    <select id="reportClassId" name="classId" required style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select</option>
                        @foreach($classes as $cls)
                            <option value="{{ $cls->id }}" {{ (string)$filters['classId'] === (string)$cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reportSessionId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Session</label>
                    <select id="reportSessionId" name="sessionId" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select</option>
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ (string)$filters['sessionId'] === (string)$sess->id ? 'selected' : '' }}>{{ $sess->session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reportSectionId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Section / Group</label>
                    <select id="reportSectionId" name="sectionId" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}" {{ (string)$filters['sectionId'] === (string)$sec->id ? 'selected' : '' }}>{{ $sec->section }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reportStudentId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Student ID</label>
                    <input id="reportStudentId" type="number" name="studentId" value="{{ $filters['studentId'] }}" placeholder="Exact ID" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                </div>

                <div>
                    <label for="reportStudentName" style="display:block; font-weight:600; margin-bottom:0.35rem;">Student Name</label>
                    <input id="reportStudentName" type="text" name="studentName" value="{{ $filters['studentName'] }}" placeholder="Partial name" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                </div>
            </div>

            <div class="am-btn-row">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Load Report</button>
                <a href="{{ route('adminModernAttendanceIndex') }}" class="am-btn-outline">Mark Attendance</a>
            </div>
        </form>

        @php
            $statusMap = ['Present' => 'P', 'Absent' => 'A', 'Late' => 'T', 'Excused' => 'E'];
            $totals = ['P' => 0, 'A' => 0, 'T' => 0, 'E' => 0];
            foreach ($records as $rr) {
                $code = $statusMap[$rr->status] ?? substr($rr->status, 0, 1);
                if (isset($totals[$code])) {
                    $totals[$code]++;
                }
            }
        @endphp

        <table class="am-table">
            <thead>
                <tr>
                    <th>Sl</th>
                    <th>Class Roll</th>
                    <th>Student</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $idx => $r)
                    @php $code = $statusMap[$r->status] ?? substr($r->status, 0, 1); @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $r->student && $r->student->rollNumber ? $r->student->rollNumber : '' }}</td>
                        <td>
                            @php $nm = $r->student ? trim(($r->student->fullName ?? '') . ' ' . ($r->student->sureName ?? '')) : ''; @endphp
                            {{ $nm }}
                            <div style="opacity:0.72; font-size:0.82rem;">ID: {{ $r->student_id }}</div>
                        </td>
                        <td style="text-align:center; font-weight:600;">{{ $code }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;">{{ empty($filters['classId']) ? 'Select filters to view report.' : 'No attendance found for selection.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:0.7rem; font-size:0.88rem;"><strong>Legend:</strong> Present = P, Absent = A, Tardy = T, Excused = E</div>

        @if($records->count())
            <table class="am-table" style="margin-top:0.7rem; max-width:560px;">
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
                        <td style="text-align:center; font-weight:600;">{{ $totals['P'] }}</td>
                        <td style="text-align:center; font-weight:600;">{{ $totals['A'] }}</td>
                        <td style="text-align:center; font-weight:600;">{{ $totals['T'] }}</td>
                        <td style="text-align:center; font-weight:600;">{{ $totals['E'] }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </x-admin-modern.table-shell>
@endsection

@push('scripts')
<script>
    function setTodayAndSubmit() {
        const today = new Date().toISOString().substring(0, 10);
        const dateInput = document.querySelector('input[name="date"]');
        if (dateInput) {
            dateInput.value = today;
        }
        document.querySelector('form[action="{{ route('attendanceReport') }}"]').submit();
    }
</script>
@endpush

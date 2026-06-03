@extends('admin-modern.layouts.app')

@section('title', 'Mark Attendance')

@section('content')
    <x-admin-modern.page-header
        title="Mark Attendance"
        subtitle="Modern parallel wrapper for attendance filter selection using existing ERP attendance flow"
        :breadcrumb="['Home', 'Attendance', 'Mark Attendance']"
    />

    <div class="am-btn-row" style="justify-content:flex-start; margin-bottom:0.75rem; gap:0.5rem; flex-wrap:wrap;" aria-label="Attendance quick links">
        <a href="{{ route('adminModernAttendanceIndex') }}" class="am-btn-primary" aria-current="page" title="Open mark attendance">Mark Attendance</a>
        <a href="{{ route('adminModernAttendanceReport') }}" class="am-btn-outline" title="Open attendance report">Attendance Report</a>
        <a href="{{ route('adminModernAttendanceMonthly') }}" class="am-btn-outline" title="Open monthly attendance">Monthly Sheet</a>
    </div>

    <x-admin-modern.table-shell title="Attendance - Select Filters">
        <form method="POST" action="{{ route('adminModernAttendanceFetch') }}">
            @csrf

            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="attendanceDate" style="display:block; font-weight:600; margin-bottom:0.35rem;">Date *</label>
                    <input id="attendanceDate" type="date" name="date" value="{{ date('Y-m-d') }}" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>

                <div>
                    <label for="attendanceClassId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Class *</label>
                    <select id="attendanceClassId" name="classId" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                        <option value="">Select</option>
                        @foreach($classes as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="attendanceSessionId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Session</label>
                    <select id="attendanceSessionId" name="sessionId" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select</option>
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="attendanceSectionId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Section / Group</label>
                    <select id="attendanceSectionId" name="sectionId" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->section }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="am-btn-row">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Load Students</button>
            </div>
        </form>
    </x-admin-modern.table-shell>
@endsection
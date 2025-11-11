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

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Session</th>
                                <th>Status</th>
                                <th>Teacher</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $r)
                                <tr>
                                    <td>{{ $r->attendance_date }}</td>
                                    <td>{{ $r->student_id }}</td>
                                    <td>{{ $r->student ? trim(($r->student->fullName ?? '').' '.($r->student->sureName ?? '')) : '' }}</td>
                                    <td>{{ $r->class ? $r->class->className : $r->class_id }}</td>
                                    <td>{{ $r->section ? $r->section->section : $r->section_id }}</td>
                                    <td>{{ $r->session ? $r->session->session : $r->session_id }}</td>
                                    <td>{{ $r->status }}</td>
                                    <td>{{ $r->teacher_id }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">{{ empty($filters['classId']) ? 'Select filters to view report.' : 'No attendance found for selection.' }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

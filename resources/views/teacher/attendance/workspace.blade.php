@extends('layouts.teacher')
@section('title', 'Mark Attendance')
@section('content')
<div class="tp-page-head"><div><p class="tp-eyebrow">Attendance workspace</p><h1>Mark Attendance</h1>
<p>{{ $assignment['class']->className }} · {{ $assignment['section']->section }} · {{ $session->session }} · {{ $date }}</p></div>
<a class="tp-btn" href="{{ route('teacher.attendance.index') }}">Change date/session</a></div>
@if(session('success'))<div class="tp-alert tp-alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="tp-alert tp-alert-error">{{ session('error') }}</div>@endif
<section class="tp-card"><form method="POST" action="{{ route('teacher.attendance.save') }}">
@csrf
<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="sessionId" value="{{ $session->id }}">
<div class="tp-table-wrap"><table class="tp-table"><thead><tr><th>Roll</th><th>Student ID</th><th>Student name</th><th>Status</th></tr></thead><tbody>
@forelse($population as $student)
@php($record = $existing->get($student->id))
<tr><td>{{ $student->rollNumber ?: '—' }}</td><td>{{ $student->stdId ?: '—' }}</td>
<td>{{ trim($student->fullName.' '.$student->sureName) }}<input type="hidden" name="studentId[]" value="{{ $student->id }}"></td>
<td><select class="tp-control" name="status[]" aria-label="Attendance status for {{ trim($student->fullName.' '.$student->sureName) }}">
@foreach(\App\Services\AttendanceSaveService::STATUSES as $attendanceStatus)
<option value="{{ $attendanceStatus }}" @selected(old('status.'.$loop->parent->index, $record?->status ?? 'Present') === $attendanceStatus)>{{ $attendanceStatus }}</option>
@endforeach
</select></td></tr>
@empty<tr><td colspan="4">No students belong to this session, class and section.</td></tr>@endforelse
</tbody></table></div>
@if($population->isNotEmpty())<div class="tp-form-actions"><button class="tp-btn tp-btn-primary" type="submit">Save Attendance</button></div>@endif
</form></section>
@endsection

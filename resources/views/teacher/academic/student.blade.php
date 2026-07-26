@extends('layouts.teacher')
@section('title','Student Profile')
@section('content')
<div class="tp-section-head"><div><h1>{{ $student->student_name }}</h1><p class="tp-card-label">Read-only student profile</p></div><a href="{{ route('teacher.students.index') }}">Back to students</a></div>
<div class="tp-panel-grid"><section class="tp-card">
@if($student->avatar)<img src="{{ asset('upload/image/student/'.$student->avatar) }}" alt="" width="100" height="100" style="border-radius:50%;object-fit:cover">@endif
<h2>Academic profile</h2><p>Student ID: {{ $student->stdId ?: '—' }}</p><p>Roll: {{ $student->rollNumber ?: '—' }}</p>
<p>Session: {{ $student->sessionInfo?->session ?: $student->sessName }}</p><p>Class: {{ $student->classInfo?->className ?: '—' }}</p>
<p>Section: {{ $student->sectionInfo?->section ?: '—' }}</p><p>Department: {{ $student->departmentInfo?->departmentName ?: '—' }}</p>
<p>Status: {{ $student->status ?: '—' }}</p><p>Gender: {{ $student->gender === '1' ? 'Male' : ($student->gender === '2' ? 'Female' : 'Other') }}</p>
</section><section class="tp-card"><h2>Attendance summary</h2>
@forelse($attendance as $status=>$total)<p>{{ $status }}: <strong>{{ $total }}</strong></p>@empty<p>No attendance records.</p>@endforelse
</section></div>
<section class="tp-card tp-section"><h2>Assigned-subject result summary</h2><div class="tp-table-wrap"><table class="tp-table"><thead><tr><th>Exam</th><th>Subject</th><th>Marks</th><th>Grade</th><th>Point</th></tr></thead><tbody>
@forelse($results as $result)<tr><td>{{ $result->exam }}</td><td>{{ $result->subject }}</td><td>{{ $result->marks ?? 'Incomplete' }}</td><td>{{ $result->grade }}</td><td>{{ number_format($result->point,2) }}</td></tr>
@empty<tr><td colspan="5">No authorized result records.</td></tr>@endforelse
</tbody></table></div></section>
@endsection

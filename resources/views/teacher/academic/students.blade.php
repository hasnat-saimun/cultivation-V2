@extends('layouts.teacher')
@section('title','My Students')
@section('content')
<div class="tp-section-head"><div><h1>My Students</h1><p class="tp-card-label">Students within your assigned academic scopes</p></div></div>
<section class="tp-card"><form class="tp-form-grid" method="GET" action="{{ route('teacher.students.index') }}"><div class="tp-field"><label for="student-search">Search</label>
<input class="tp-control" id="student-search" name="search" value="{{ $search }}" maxlength="100" placeholder="Student ID, roll or name"></div>
<div class="tp-form-actions"><button class="tp-btn tp-btn-primary" type="submit">Search</button> @if($search)<a class="tp-btn" href="{{ route('teacher.students.index') }}">Clear</a>@endif</div></form></section>
<section class="tp-card tp-section"><div class="tp-table-wrap"><table class="tp-table"><thead><tr><th>Photo</th><th>Student ID</th><th>Roll</th><th>Name</th><th>Class</th><th>Section</th><th>Department</th><th>Status</th></tr></thead><tbody>
@forelse($students as $student)<tr><td>@if($student->avatar)<img src="{{ asset('upload/image/student/'.$student->avatar) }}" alt="" width="38" height="38" style="border-radius:50%;object-fit:cover">@else—@endif</td>
<td>{{ $student->stdId ?: '—' }}</td><td>{{ $student->rollNumber ?: '—' }}</td><td><a href="{{ route('teacher.students.show',$student->id) }}">{{ $student->student_name }}</a></td>
<td>{{ $student->classInfo?->className ?: '—' }}</td><td>{{ $student->sectionInfo?->section ?: '—' }}</td><td>{{ $student->departmentInfo?->departmentName ?: '—' }}</td><td>{{ $student->status ?: '—' }}</td></tr>
@empty<tr><td colspan="8" class="tp-empty">No authorized students found.</td></tr>@endforelse
</tbody></table></div>{{ $students->links() }}</section>
@endsection

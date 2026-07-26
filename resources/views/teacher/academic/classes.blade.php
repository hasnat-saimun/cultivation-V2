@extends('layouts.teacher')
@section('title','My Classes')
@section('content')
<div class="tp-section-head"><div><h1>My Classes</h1><p class="tp-card-label">Your subject assignment contexts</p></div></div>
<div class="tp-grid">
@forelse($classes as $item)
<article class="tp-card"><h2>{{ $item->class_name }}</h2>
<p>{{ $item->session_name }} · {{ $item->section_name ?: 'All sections' }} · {{ $item->department_name ?: 'All departments' }}</p>
<p><strong>{{ $item->subject_name }}</strong></p><span class="tp-card-value">{{ $item->student_count }}</span><span class="tp-card-label">authorized students</span>
<div style="margin-top:1rem"><a href="{{ route('teacher.students.index') }}">View students</a>
@if((int)$teacher->primary_class_id===(int)$item->class_id && (int)$teacher->primary_section_id===(int)$item->section_id) · <a href="{{ route('teacher.attendance.index') }}">Attendance</a>@endif
 · <a href="{{ route('teacher.results.index') }}">Results</a></div></article>
@empty<div class="tp-card tp-empty">No academic assignments are available.</div>@endforelse
</div>
@endsection

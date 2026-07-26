@extends('layouts.teacher')
@section('title', 'Attendance')
@section('content')
<div class="tp-page-head"><div><p class="tp-eyebrow">Class teacher workspace</p><h1>Attendance</h1><p>Take attendance only for your assigned primary class and section.</p></div></div>
@if(session('error'))<div class="tp-alert tp-alert-error">{{ session('error') }}</div>@endif
@if($unavailable)
    <section class="tp-card"><h2>Attendance unavailable</h2><p>{{ $unavailable }}</p></section>
@else
    <section class="tp-card">
        <h2>{{ $assignment['class']->className }} · {{ $assignment['section']->section }}</h2>
        <form method="POST" action="{{ route('teacher.attendance.load') }}" class="tp-form-grid">
            @csrf
            <label>Date<input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}"></label>
            <label>Session<select name="sessionId" required><option value="">Select session</option>
                @foreach($sessions as $session)<option value="{{ $session->id }}">{{ $session->session }}</option>@endforeach
            </select></label>
            <div class="tp-form-actions"><button class="tp-btn tp-btn-primary" type="submit">Open Attendance</button></div>
        </form>
    </section>
    <section class="tp-card"><h2>Recent attendance</h2>
        @forelse($recent as $item)<p>{{ $item->attendance_date }} — {{ $item->present }}/{{ $item->total }} present</p>
        @empty<p>No recent attendance records.</p>@endforelse
    </section>
@endif
@endsection

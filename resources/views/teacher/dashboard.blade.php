@extends('layouts.teacher')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<section class="tp-welcome">
    <div>
        <h2>Welcome, {{ $teacher->adminName ?: 'Teacher' }}</h2>
        @if (filled($teacher->adminUser)) <p>Teacher ID: {{ $teacher->adminUser }}</p> @endif
        @if ($currentSession) <p>Academic session: {{ $currentSession }}</p> @endif
        <p>Your secure teaching workspace at a glance.</p>
    </div>
    <time class="tp-date" datetime="{{ now()->toDateString() }}">{{ now()->format('l, d F Y') }}</time>
</section>

<section class="tp-grid" aria-label="Assignment statistics">
    <x-teacher.stat-card label="Assigned Classes" :value="$statistics['classes']" />
    <x-teacher.stat-card label="Assigned Subjects" :value="$statistics['subjects']" />
    <x-teacher.stat-card label="Assigned Sections" :value="$statistics['sections']" />
    <x-teacher.stat-card label="Assigned Students" />
    <x-teacher.stat-card label="Pending Result Work" />
    <x-teacher.stat-card label="Pending Attendance" />
</section>

<section class="tp-section">
    <div class="tp-section-head"><h2>Quick Actions</h2></div>
    <div class="tp-actions">
        @foreach (['Take Attendance', 'Enter Results', 'View My Classes', 'View My Students', 'View Routine', 'View Profile'] as $action)
            <div class="tp-action" aria-disabled="true"><strong>{{ $action }}</strong><span>Coming Soon</span></div>
        @endforeach
    </div>
</section>

<div class="tp-panel-grid tp-section">
    <section class="tp-card" aria-labelledby="assignment-heading">
        <div class="tp-section-head"><h2 id="assignment-heading">Assignment Summary</h2><span class="tp-card-label">Up to 8 assignments</span></div>
        @if ($assignments->isEmpty())
            <x-teacher.empty-state message="No academic assignments are currently available." />
        @else
            <div class="tp-table-wrap">
                <table class="tp-table">
                    <thead><tr><th>Session</th><th>Class</th><th>Section</th><th>Department / Group</th><th>Subject</th><th>Gender</th></tr></thead>
                    <tbody>
                    @foreach ($assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->session_name ?: '—' }}</td>
                            <td>{{ $assignment->class_name ?: '—' }}</td>
                            <td>{{ $assignment->section_name ?: 'All / Not assigned' }}</td>
                            <td>{{ $assignment->department_label }}</td>
                            <td>{{ $assignment->subject_name ?: 'All / Not assigned' }}</td>
                            <td>{{ ucfirst($assignment->gender_scope ?: 'all') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="tp-card" aria-labelledby="activity-heading">
        <div class="tp-section-head"><h2 id="activity-heading">Recent Activity</h2></div>
        @if ($activities->isEmpty())
            <x-teacher.empty-state message="No recent teacher activity is available." />
        @else
            <div class="tp-activity">
                @foreach ($activities as $activity)
                    <div class="tp-activity-item">
                        <strong>{{ $activity['label'] }}</strong>
                        <time datetime="{{ \Illuminate\Support\Carbon::parse($activity['occurred_at'])->toIso8601String() }}">
                            {{ \Illuminate\Support\Carbon::parse($activity['occurred_at'])->diffForHumans() }}
                        </time>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

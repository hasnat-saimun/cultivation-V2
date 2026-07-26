@extends('layouts.teacher')

@section('title', 'Results')
@section('page-title', 'Results')

@section('content')
<section class="tp-welcome">
    <div>
        <h2>Teacher Result Workspace</h2>
        <p>Choose one of your verified assignments and a class-compatible exam.</p>
    </div>
</section>

<section class="tp-section">
    <div class="tp-section-head">
        <h2>My Result Assignments</h2>
        <span class="tp-card-label">{{ $assignments->count() }} available</span>
    </div>
    @if ($assignments->isEmpty())
        <div class="tp-card"><x-teacher.empty-state message="No valid result assignments with eligible exams are currently available." /></div>
    @else
        <div class="tp-grid">
            @foreach ($assignments as $assignment)
                <article class="tp-card">
                    <strong>{{ $assignment->subject_label }}</strong>
                    <p class="tp-card-label">
                        {{ $assignment->session_label }} · {{ $assignment->class_label }}
                        · {{ $assignment->section_label }}
                        · {{ $assignment->department_label }}
                        · {{ $assignment->gender_scope_label }}
                    </p>
                    <form method="POST" action="{{ route('teacher.results.load') }}">
                        @csrf
                        <input type="hidden" name="sessionId" value="{{ $assignment->session_id }}">
                        <input type="hidden" name="classId" value="{{ $assignment->class_id }}">
                        <input type="hidden" name="groupId" value="{{ $assignment->section_id }}">
                        <input type="hidden" name="optionalGroupId" value="{{ $assignment->group_id }}">
                        <input type="hidden" name="subjectId" value="{{ $assignment->subject_id }}">
                        <input type="hidden" name="gender" value="{{ $assignment->gender_scope }}">
                        <label for="exam-{{ $loop->index }}">Exam</label>
                        <select class="tp-control" id="exam-{{ $loop->index }}" name="examId" required style="margin:.4rem 0 .8rem">
                            @foreach ($assignment->exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->examName }}</option>
                            @endforeach
                        </select>
                        <button class="tp-btn tp-btn-primary" type="submit" style="width:100%">Open Workspace</button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
</section>

<section class="tp-card tp-section">
    <div class="tp-section-head"><h2>Recent Result Activity</h2></div>
    @if ($activities->isEmpty())
        <x-teacher.empty-state message="No recent result activity is available." />
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
@endsection

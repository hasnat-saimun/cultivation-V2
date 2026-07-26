@extends('layouts.teacher')

@section('title', 'Result Entry')
@section('page-title', 'Result Entry')

@section('content')
@php
    $legacyComponents = $subject->CQ === null && $subject->MCQ === null && $subject->Practical === null;
    $showCq = $legacyComponents || (float)($subject->CQ ?? 0) > 0;
    $showMcq = !$legacyComponents && (float)($subject->MCQ ?? 0) > 0;
    $showPractical = !$legacyComponents && (float)($subject->Practical ?? 0) > 0;
@endphp

<section class="tp-card" style="position:sticky;top:82px;z-index:20">
    <div class="tp-section-head">
        <div>
            <h2>{{ $labels['subject'] }} · {{ $labels['exam'] }}</h2>
            <span class="tp-card-label">
                Session {{ $labels['session'] }} · Class {{ $labels['class'] }}
                · Section {{ $labels['section'] }} · Department {{ $labels['department'] }}
                @if($scope['gender'] !== 'all') · Gender {{ $labels['gender'] }} @endif
            </span>
        </div>
        <strong style="padding:.4rem .7rem;border-radius:999px;background:#e5f5f2;color:#0f766e">{{ $status }}</strong>
    </div>
</section>

@if ($students->isEmpty())
    <section class="tp-card tp-section"><x-teacher.empty-state message="No students are available in this authorized scope." /></section>
@else
<form method="POST" action="{{ route('teacher.results.draft') }}" id="teacher-marks-form">
    @csrf
    @foreach (['sessionId','classId','groupId','optionalGroupId','subjectId','examId','gender'] as $field)
        <input type="hidden" name="{{ $field }}" value="{{ $scope[$field] }}">
    @endforeach
    <input type="hidden" name="scope_revision" value="{{ $revision }}">
    @foreach($scopeRevisions as $scopeKey => $scopeValue)
        <input type="hidden" name="scope_revisions[{{ $scopeKey }}]" value="{{ $scopeValue }}">
    @endforeach

    <section class="tp-card tp-section">
        <div class="tp-table-wrap">
            <table class="tp-table">
                <thead>
                    <tr>
                        <th>Roll</th><th>Student ID</th><th>Student Name</th>
                        @if($showCq)<th>Written / CQ</th>@endif
                        @if($showMcq)<th>MCQ</th>@endif
                        @if($showPractical)<th>Practical</th>@endif
                        <th>Total</th><th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($students as $student)
                    @php $mark = $marks->get((int)$student->id); @endphp
                    <tr>
                        <td>{{ $student->rollNumber ?: '—' }}</td>
                        <td>{{ $student->stdId ?: '—' }}</td>
                        <td>
                            {{ trim($student->fullName.' '.$student->sureName) }}
                            <input type="hidden" name="studentId[]" value="{{ $student->id }}">
                        </td>
                        @if($showCq)
                            <td><input class="tp-control tp-mark-control" aria-label="Written marks for {{ trim($student->fullName.' '.$student->sureName) }}" type="number" step="0.01" min="0" max="{{ $legacyComponents ? 100 : (float)$subject->CQ }}" name="cqMarks[]" value="{{ old('cqMarks.'.$loop->index, $mark?->subjectMarks) }}" {{ $editable ? '' : 'disabled' }}></td>
                        @else <input type="hidden" name="cqMarks[]" value=""> @endif
                        @if($showMcq)
                            <td><input class="tp-control tp-mark-control" aria-label="MCQ marks for {{ trim($student->fullName.' '.$student->sureName) }}" type="number" step="0.01" min="0" max="{{ (float)$subject->MCQ }}" name="mcqMarks[]" value="{{ old('mcqMarks.'.$loop->index, $mark?->objectMarks) }}" {{ $editable ? '' : 'disabled' }}></td>
                        @else <input type="hidden" name="mcqMarks[]" value=""> @endif
                        @if($showPractical)
                            <td><input class="tp-control tp-mark-control" aria-label="Practical marks for {{ trim($student->fullName.' '.$student->sureName) }}" type="number" step="0.01" min="0" max="{{ (float)$subject->Practical }}" name="practical[]" value="{{ old('practical.'.$loop->index, $mark?->practicalMarks) }}" {{ $editable ? '' : 'disabled' }}></td>
                        @else <input type="hidden" name="practical[]" value=""> @endif
                        <td>{{ $mark?->totalMarks ?? '—' }}</td>
                        <td>{{ $mark?->laterGrade ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="tp-card tp-section" style="position:sticky;bottom:.5rem;display:flex;justify-content:space-between;gap:.8rem;align-items:center">
        <span class="tp-card-label">Revision {{ $revision }} · Server calculations remain authoritative.</span>
        <div style="display:flex;gap:.6rem">
            <a href="{{ route('teacher.results.index') }}" style="padding:.7rem;color:var(--tp-brand)">Back</a>
            @if($editable)
                <button class="tp-btn tp-btn-primary" type="submit" onclick="this.disabled=true;this.form.submit()">Save Draft</button>
            @endif
        </div>
    </section>
</form>

@if($confirmable)
    <form method="POST" action="{{ route('teacher.results.confirm') }}" onsubmit="return confirm('Confirm this complete subject result? Confirmed marks become read-only.')">
        @csrf
        @foreach (['sessionId','classId','groupId','optionalGroupId','subjectId','examId','gender'] as $field)
            <input type="hidden" name="{{ $field }}" value="{{ $scope[$field] }}">
        @endforeach
        <input type="hidden" name="scope_revision" value="{{ $revision }}">
        <section class="tp-alert tp-section">
            Confirm only after reviewing every student and required component.
            <button class="tp-btn tp-btn-danger" type="submit" style="float:right">Confirm Result</button>
        </section>
    </form>
@endif
@endif

@if($editable)
@push('scripts')
<script>
(() => {
    const form = document.getElementById('teacher-marks-form');
    if (!form) return;
    let dirty = false;
    form.addEventListener('input', () => dirty = true);
    form.addEventListener('submit', () => dirty = false);
    window.addEventListener('beforeunload', event => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
})();
</script>
@endpush
@endif
@endsection

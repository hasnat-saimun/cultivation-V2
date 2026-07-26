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
    <input type="hidden" name="submission_action" id="teacher_submission_action" value="">
    <input type="hidden" name="confirm_blank_marks" id="teacher_confirm_blank_marks" value="0">
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
                <button class="tp-btn tp-btn-primary" type="submit">Save Draft</button>
                @if($confirmable)
                    <button class="tp-btn tp-btn-danger js-teacher-confirm" type="submit" formaction="{{ route('teacher.results.confirm') }}">Confirm Result</button>
                @endif
            @endif
        </div>
    </section>
</form>

@if($confirmable)
    <section class="tp-alert tp-section">
        Confirm only after reviewing every student and required component.
    </section>

    <div class="modal fade" id="teacherBlankMarksModal" tabindex="-1" aria-labelledby="teacherBlankMarksModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="teacherBlankMarksModalLabel">Blank marks detected</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="teacherBlankMarksSummary" class="mb-2"></p>
                    <p class="text-muted mb-0">Some mark fields are still blank. You may confirm the marks anyway, save the current entries as a draft, or return to complete the missing fields.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="teacherBlankGoBack">Go Back</button>
                    <button type="button" class="btn btn-success" id="teacherBlankSaveDraft">Save as Draft</button>
                    <button type="button" class="btn btn-warning" id="teacherBlankConfirmAnyway">Confirm Anyway</button>
                </div>
            </div>
        </div>
    </div>
@endif
@endif

@if($editable)
@push('scripts')
<script>
(() => {
    const form = document.getElementById('teacher-marks-form');
    if (!form) return;
    const draftRoute = @json(route('teacher.results.draft'));
    const confirmRoute = @json(route('teacher.results.confirm'));
    const actionInput = document.getElementById('teacher_submission_action');
    const blankOverrideInput = document.getElementById('teacher_confirm_blank_marks');
    const confirmButton = document.querySelector('.js-teacher-confirm');
    const modalElement = document.getElementById('teacherBlankMarksModal');
    const summaryElement = document.getElementById('teacherBlankMarksSummary');
    const confirmAnywayBtn = document.getElementById('teacherBlankConfirmAnyway');
    const saveDraftBtn = document.getElementById('teacherBlankSaveDraft');
    const goBackBtn = document.getElementById('teacherBlankGoBack');
    const modalInstance = (window.jQuery && modalElement) ? window.jQuery(modalElement) : null;

    if (modalInstance) {
        modalInstance.modal({backdrop: 'static', show: false});
    }

    let dirty = false;
    let submitInProgress = false;

    function resetIntent() {
        if (actionInput) actionInput.value = '';
        if (blankOverrideInput) blankOverrideInput.value = '0';
    }

    function scanBlankMarks() {
        const rows = Array.from(form.querySelectorAll('tbody tr'));
        let blankFieldCount = 0;
        let blankStudentCount = 0;

        rows.forEach((row) => {
            const markInputs = Array.from(row.querySelectorAll('input[name="cqMarks[]"], input[name="mcqMarks[]"], input[name="practical[]"]'))
                .filter((input) => input.type !== 'hidden' && !input.disabled);

            if (!markInputs.length) return;

            let rowBlank = 0;
            markInputs.forEach((input) => {
                if (String(input.value ?? '').trim() === '') {
                    rowBlank++;
                }
            });

            if (rowBlank > 0) {
                blankStudentCount++;
                blankFieldCount += rowBlank;
            }
        });

        return {blankFieldCount, blankStudentCount};
    }

    function submitWithIntent(action, route) {
        if (submitInProgress) return;
        submitInProgress = true;
        if (actionInput) actionInput.value = action;
        if (blankOverrideInput) blankOverrideInput.value = action === 'confirm_with_blanks' ? '1' : '0';
        form.setAttribute('action', route);
        dirty = false;
        form.submit();
    }

    function openModal(summary) {
        if (!modalInstance) {
            submitWithIntent('confirm_with_blanks', confirmRoute);
            return;
        }
        if (summaryElement) summaryElement.textContent = summary;
        modalInstance.modal('show');
    }

    form.addEventListener('input', () => dirty = true);
    form.addEventListener('submit', (event) => {
        if (submitInProgress) {
            return;
        }

        const submitter = event.submitter || document.activeElement;
        const isConfirm = !!(submitter && submitter.classList && submitter.classList.contains('js-teacher-confirm'));

        if (!isConfirm) {
            if (actionInput) actionInput.value = 'draft';
            if (blankOverrideInput) blankOverrideInput.value = '0';
            form.setAttribute('action', draftRoute);
            dirty = false;
            submitInProgress = true;
            return;
        }

        const blankStats = scanBlankMarks();
        if (blankStats.blankFieldCount <= 0) {
            if (actionInput) actionInput.value = 'confirm';
            if (blankOverrideInput) blankOverrideInput.value = '0';
            form.setAttribute('action', confirmRoute);
            dirty = false;
            submitInProgress = true;
            return;
        }

        event.preventDefault();
        const summary = `${blankStats.blankFieldCount} mark fields for ${blankStats.blankStudentCount} students are still blank. Confirming may treat these records according to the system's blank-mark policy.`;
        openModal(summary);
    });

    if (confirmAnywayBtn) {
        confirmAnywayBtn.addEventListener('click', () => submitWithIntent('confirm_with_blanks', confirmRoute));
    }

    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', () => submitWithIntent('draft', draftRoute));
    }

    if (goBackBtn) {
        goBackBtn.addEventListener('click', () => {
            submitInProgress = false;
            resetIntent();
        });
    }

    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', () => {
            submitInProgress = false;
            resetIntent();
        });
    }

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

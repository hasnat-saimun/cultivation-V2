@extends('result.include')
@section('backTitle')
Get Mark
@endsection
@php
    $classIdSafe = request()->input('classId', $classId ?? null);
    $groupIdSafe = request()->input('groupId', $groupId ?? null);
    $optionalGroupIdSafe = request()->input('optionalGroupId', $optionalGroupId ?? null);
    $sessionIdSafe = request()->input('sessionId', $sessionId ?? null);
    $examIdSafe = request()->input('examId', $examId ?? null);
    $subjectIdSafe = request()->input('subjectId', $subjectId ?? null);

    $classData = $classData ?? null;
    $sectionData = $sectionData ?? null;
    $optionalGroupData = $optionalGroupData ?? null;
    $sessionData = $sessionData ?? null;
    $examData = $examData ?? null;
    $subjectData = $subjectData ?? null;
    if($classData):
        $className = $classData->className;
    else:
        $className = "-";
    endif;
    if($sectionData):
        $sectionName = $sectionData->section;
    else:
        $sectionName = "-";
    endif;
    if($optionalGroupData):
        $optionalGroupName = $optionalGroupData->departmentName;
    else:
        $optionalGroupName = "-";
    endif;
    if($sessionData):
        $session_name = $sessionData->session;
    else:
        $session_name = "-";
    endif;
    if($examData):
        $examName = $examData->examName;
    else:
        $examName = "-";
    endif;
    if($subjectData):
        $subjectName = $subjectData->subjectName;
    else:
        $subjectName = "-";
    endif;
@endphp
@section('backIndex')
    <style>
        .marks-table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .marks-entry-table {
            min-width: 900px;
        }

        .marks-entry-table input.form-control {
            min-width: 120px;
        }

        @media (max-width: 767px) {
            .marks-entry-table {
                min-width: 780px;
            }

            .marks-entry-table th,
            .marks-entry-table td {
                white-space: nowrap;
                font-size: 13px;
                padding: 0.45rem;
            }

            .marks-entry-table input.form-control {
                min-width: 100px;
                font-size: 12px;
                padding: 0.3rem 0.45rem;
            }
        }
    </style>
    @if($studentList->count()>0)
        <form method="POST" class="card-body form form-group" action="{{ route('marks.draft.save') }}">
                <div class="row">
                    <div class="col-6 col-md-4 mb-2"><b>Group/Section:</b>  {{ $sectionName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Group (Optional):</b>  {{ $optionalGroupName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Class:</b>  {{ $className }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Session:</b> {{ $session_name }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Exam:</b> {{ $examName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Subject:</b> {{ $subjectName }}</div>
                </div>
                @csrf
                <input type="hidden" name="submission_action" id="submission_action" value="">
                <input type="hidden" name="confirm_blank_marks" id="confirm_blank_marks" value="0">
                @php
                    $isReadOnly = !empty($isFinalPublished) || !empty($hasConfirmedScope);
                    $singleScopeKey = count($scopeStatuses ?? []) === 1 ? array_key_first($scopeStatuses) : null;
                    $singleScopeStatus = $singleScopeKey ? ($scopeStatuses[$singleScopeKey] ?? 'draft') : null;
                    $lifecycleGroupId = $singleScopeKey && str_starts_with($singleScopeKey, 'section:')
                        ? substr($singleScopeKey, strlen('section:'))
                        : null;
                @endphp
                <div class="mt-2">
                    @foreach(($scopeStatuses ?? []) as $scopeKey => $scopeStatus)
                        <span class="badge {{ $scopeStatus === 'confirmed' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $scopeKey }}: {{ ucfirst($scopeStatus) }} (Revision {{ $scopeRevisions[$scopeKey] ?? 1 }})
                        </span>
                    @endforeach
                </div>
                @if($isReadOnly)
                    <div class="alert alert-warning mt-2">
                        {{ !empty($isFinalPublished) ? 'Final result is published.' : 'This subject scope is Confirmed.' }}
                        Marks entry is read-only.
                    </div>
                @endif
                <div class="alert alert-info mt-2">
                    <strong>Note:</strong> Marks entry is restricted to classes/sections/subjects assigned to the teacher via Admin Assign. The Primary Class/Section setting is used only for Attendance.
                </div>
                <div class="marks-table-wrap">
                <table class="table table-bordered marks-entry-table">
                @php
                    // Get available features for the subject
                    $showCQ         = optional($subjectData)->CQ;
                    $showMCQ        = optional($subjectData)->MCQ;
                    $showPractical  = optional($subjectData)->Practical;
                    $showAll        = optional($subjectData)->Practical == null && optional($subjectData)->MCQ == null && optional($subjectData)->CQ == null;
                @endphp
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Roll</th>
                            <th>Student Name</th>
                            @if($showCQ)<th>CQ</th>@endif
                            @if($showMCQ)<th>MCQ</th>@endif
                            @if($showPractical)<th>Practical</th>@endif
                            @if($showAll)
                            <th>CQ</th>
                            <th>MCQ</th>
                            <th>Practical</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if($studentList->count()>0)
                        <input type="hidden" name="sessionId" value="{{ $sessionIdSafe }}">
                        <input type="hidden" name="classId" value="{{ $classIdSafe }}">
                        <input type="hidden" name="examId" value="{{ $examIdSafe }}">
                        <input type="hidden" name="groupId" value="{{ $groupIdSafe }}">
                        <input type="hidden" name="optionalGroupId" value="{{ $optionalGroupIdSafe }}">
                        <input type="hidden" name="gender" value="{{ $gender ?? 'all' }}">
                        <input type="hidden" name="subjectId" value="{{ $subjectIdSafe }}">
                        @if($singleScopeRevision)
                            <input type="hidden" name="scope_revision" value="{{ $singleScopeRevision }}">
                        @endif
                        @foreach(($scopeRevisions ?? []) as $scopeKey => $scopeRevision)
                            <input type="hidden" name="scope_revisions[{{ $scopeKey }}]" value="{{ $scopeRevision }}">
                        @endforeach
                         @foreach($studentList as $std)
                        @php
                            $marksData = ($marksByStudent ?? collect())->get((int) $std->id);
                            $subjectMarks = $marksData ? $marksData->subjectMarks : "";
                            $objectMarks = $marksData ? $marksData->objectMarks : "";
                            $practicalMarks = $marksData ? $marksData->practicalMarks : "";
                            $currentUserId = session('cultivationAdmin');
                            $readonlyByOther = (!empty($isTeacherAdmin) && $marksData && $marksData->teacher_id && $marksData->teacher_id != $currentUserId);
                            $readonly = $readonlyByOther || $isReadOnly;
                            $enteredById = $marksData ? ($marksData->entered_by ?? $marksData->teacher_id) : null;
                            $enteredBy = $enteredById ? ($actorNames[$enteredById] ?? null) : null;
                            $enteredRole = $marksData->entered_by_role ?? ($marksData && $marksData->teacher_id ? 'teacher' : null);
                            $updatedBy = ($marksData && $marksData->updated_by) ? ($actorNames[$marksData->updated_by] ?? null) : null;
                            $updatedRole = $marksData->updated_by_role ?? null;
                        @endphp
                        <input type="hidden" name="studentId[]" value="{{ $std->id }}">
                        <tr>
                            <td>{{ $std->stdId }}</td>
                            <td>{{ $std->rollNumber }}</td>
                            <td>{{ $std->fullName.' '.$std->sureName }}</td>
                            @if($showCQ)
                                <td>
                                    <input type="text" class="form-control" name="cqMarks[]" value="{{ $subjectMarks }}" placeholder="Enter CQ Marks" {{ $readonly ? 'readonly' : '' }}>
                                    @if($readonlyByOther)
                                        <small class="text-muted">Entered by: {{ $enteredBy ?? 'Another teacher' }}{{ $enteredRole ? ' ('.ucfirst($enteredRole).')' : '' }}</small>
                                        @if($updatedBy)
                                            <br><small class="text-muted">Last updated: {{ $updatedBy }}{{ $updatedRole ? ' ('.ucfirst($updatedRole).')' : '' }}</small>
                                        @endif
                                    @endif
                                </td>
                            @endif
                            @if($showMCQ)
                                <td>
                                    <input type="text" class="form-control" name="mcqMarks[]" value="{{ $objectMarks }}" placeholder="Enter MCQ Marks" {{ $readonly ? 'readonly' : '' }}>
                                    @if($readonlyByOther)
                                        <small class="text-muted">Entered by: {{ $enteredBy ?? 'Another teacher' }}{{ $enteredRole ? ' ('.ucfirst($enteredRole).')' : '' }}</small>
                                        @if($updatedBy)
                                            <br><small class="text-muted">Last updated: {{ $updatedBy }}{{ $updatedRole ? ' ('.ucfirst($updatedRole).')' : '' }}</small>
                                        @endif
                                    @endif
                                </td>
                            @endif
                            @if($showPractical)
                                <td>
                                    <input type="text" class="form-control" name="practical[]" value="{{ $practicalMarks }}" placeholder="Enter Practical Marks" {{ $readonly ? 'readonly' : '' }}>
                                    @if($readonlyByOther)
                                        <small class="text-muted">Entered by: {{ $enteredBy ?? 'Another teacher' }}{{ $enteredRole ? ' ('.ucfirst($enteredRole).')' : '' }}</small>
                                        @if($updatedBy)
                                            <br><small class="text-muted">Last updated: {{ $updatedBy }}{{ $updatedRole ? ' ('.ucfirst($updatedRole).')' : '' }}</small>
                                        @endif
                                    @endif
                                </td>
                            @endif
                            
                            @if($showAll)
                            <td>
                                <input type="text" class="form-control" name="cqMarks[]" value="{{ $subjectMarks }}" placeholder="Enter CQ Marks" {{ $readonly ? 'readonly' : '' }}>
                                @if($readonlyByOther)
                                    <small class="text-muted">Entered by: {{ $enteredBy ?? 'Another teacher' }}{{ $enteredRole ? ' ('.ucfirst($enteredRole).')' : '' }}</small>
                                    @if($updatedBy)
                                        <br><small class="text-muted">Last updated: {{ $updatedBy }}{{ $updatedRole ? ' ('.ucfirst($updatedRole).')' : '' }}</small>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <input type="text" class="form-control" name="mcqMarks[]" value="{{ $objectMarks }}" placeholder="Enter MCQ Marks" {{ $readonly ? 'readonly' : '' }}>
                                @if($readonlyByOther)
                                    <small class="text-muted">Entered by: {{ $enteredBy ?? 'Another teacher' }}{{ $enteredRole ? ' ('.ucfirst($enteredRole).')' : '' }}</small>
                                    @if($updatedBy)
                                        <br><small class="text-muted">Last updated: {{ $updatedBy }}{{ $updatedRole ? ' ('.ucfirst($updatedRole).')' : '' }}</small>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <input type="text" class="form-control" name="practical[]" value="{{ $practicalMarks }}" placeholder="Enter Practical Marks" {{ $readonly ? 'readonly' : '' }}>
                                @if($readonlyByOther)
                                    <small class="text-muted">Entered by: {{ $enteredBy ?? 'Another teacher' }}{{ $enteredRole ? ' ('.ucfirst($enteredRole).')' : '' }}</small>
                                    @if($updatedBy)
                                        <br><small class="text-muted">Last updated: {{ $updatedBy }}{{ $updatedRole ? ' ('.ucfirst($updatedRole).')' : '' }}</small>
                                    @endif
                                @endif
                            </td>
                            @endif
                        </tr>
                        @endforeach
                        <div class="mb-4">
                            @if(!$isReadOnly)
                                <input type="submit" value="Save Draft" class="btn btn-success">
                                @if($singleScopeStatus === 'draft')
                                    <button type="submit"
                                            formaction="{{ route('marks.subject.confirm') }}"
                                            name="groupId"
                                            value="{{ $lifecycleGroupId }}"
                                            class="btn btn-warning js-confirm-subject">
                                        Confirm Subject
                                    </button>
                                @endif
                            @elseif($singleScopeStatus === 'confirmed' && !empty($canReopenMarks) && empty($isFinalPublished))
                                <input type="text" name="reason" maxlength="500" class="form-control d-inline-block w-auto"
                                    placeholder="Mandatory reopen reason" required>
                                <button type="submit"
                                    formaction="{{ route('marks.subject.reopen') }}"
                                    name="groupId"
                                    value="{{ $lifecycleGroupId }}"
                                    class="btn btn-warning">
                                    Reopen as Draft
                                </button>
                            @endif
                            <a href="{{ route('addMarks') }}" class="btn btn-primary">Back</a>
                        </div>
                        @else
                        <tr>
                            <td colspan="5">Sorry! No data found</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                </div>
            </form>

        <div class="modal fade" id="blankMarksModal" tabindex="-1" aria-labelledby="blankMarksModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="blankMarksModalLabel">Blank marks detected</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p id="blankMarksSummary" class="mb-2"></p>
                        <p class="text-muted mb-0">Some mark fields are still blank. You may confirm the marks anyway, save the current entries as a draft, or return to complete the missing fields.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="blankMarksGoBack">Go Back</button>
                        <button type="button" class="btn btn-success" id="blankMarksSaveDraft">Save as Draft</button>
                        <button type="button" class="btn btn-warning" id="blankMarksConfirmAnyway">Confirm Anyway</button>
                    </div>
                </div>
            </div>
        </div>
    @else
    <div class="alert alert-info">
        Sorry! No data found
    </div>
    <div class="mb-4"> <a href="{{ route('addMarks') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
    @endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    var maxCQ = {{ $subjectData->CQ ?? 0 }};
    var maxMCQ = {{ $subjectData->MCQ ?? 0 }};
    var maxPractical = {{ $subjectData->Practical ?? 0 }};

    function showError(input, max) {
        let errorDiv = input.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) errorDiv.remove();

        input.classList.add('is-invalid');
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.innerText = 'Value cannot be greater than ' + max;
        input.parentNode.appendChild(errorDiv);

        // Show alert
        alert('Value for this field cannot be greater than ' + max);
    }

    function removeError(input) {
        input.classList.remove('is-invalid');
        let errorDiv = input.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) errorDiv.remove();
    }

    function limitInput(input, max) {
        input.addEventListener('input', function() {
            var val = parseFloat(input.value);
            if (!isNaN(val) && val > max) {
                input.value = max;
                showError(input, max);
            } else {
                removeError(input);
            }
            if (!isNaN(val) && val < 0) {
                input.value = 0;
            }
        });
    }

    document.querySelectorAll('input[name="cqMarks[]"]').forEach(function(input) {
        limitInput(input, maxCQ);
    });
    document.querySelectorAll('input[name="mcqMarks[]"]').forEach(function(input) {
        limitInput(input, maxMCQ);
    });
    document.querySelectorAll('input[name="practical[]"]').forEach(function(input) {
        limitInput(input, maxPractical);
    });

    const draftRoute = @json(route('marks.draft.save'));
    const confirmRoute = @json(route('marks.subject.confirm'));
    const form = document.querySelector('form');
    const submissionActionInput = document.getElementById('submission_action');
    const confirmBlankMarksInput = document.getElementById('confirm_blank_marks');
    const confirmButton = document.querySelector('.js-confirm-subject');
    const modalElement = document.getElementById('blankMarksModal');
    const blankSummary = document.getElementById('blankMarksSummary');
    const modalConfirmAnyway = document.getElementById('blankMarksConfirmAnyway');
    const modalSaveDraft = document.getElementById('blankMarksSaveDraft');
    const modalGoBack = document.getElementById('blankMarksGoBack');
    const modalInstance = (window.jQuery && modalElement) ? window.jQuery(modalElement) : null;

    if (modalInstance) {
        modalInstance.modal({backdrop: 'static', show: false});
    }

    let submitInProgress = false;

    function resetSubmissionIntent() {
        if (submissionActionInput) submissionActionInput.value = '';
        if (confirmBlankMarksInput) confirmBlankMarksInput.value = '0';
    }

    function scanBlankMarks() {
        const rows = Array.from(document.querySelectorAll('.marks-entry-table tbody tr'));
        let blankFieldCount = 0;
        let blankStudentCount = 0;

        rows.forEach(function(row) {
            const markInputs = Array.from(row.querySelectorAll('input[name="cqMarks[]"], input[name="mcqMarks[]"], input[name="practical[]"]'))
                .filter(function(input) {
                    return input.type !== 'hidden' && !input.disabled;
                });

            if (!markInputs.length) return;

            let rowBlank = 0;
            markInputs.forEach(function(input) {
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

    function submitWithIntent(action, targetRoute) {
        if (!form || submitInProgress) return;
        submitInProgress = true;
        if (submissionActionInput) submissionActionInput.value = action;
        if (confirmBlankMarksInput) confirmBlankMarksInput.value = action === 'confirm_with_blanks' ? '1' : '0';
        form.setAttribute('action', targetRoute);
        form.submit();
    }

    function openBlankModal(summary) {
        if (!modalInstance) {
            return submitWithIntent('confirm_with_blanks', confirmRoute);
        }
        blankSummary.textContent = summary;
        modalInstance.modal('show');
    }

    // Prevent form submit if any value is greater than allowed
    if(form) {
        form.addEventListener('submit', function(e) {
            if (submitInProgress) {
                return;
            }

            const submitter = e.submitter || document.activeElement;
            const isConfirmSubmit = !!(submitter && submitter.classList && submitter.classList.contains('js-confirm-subject'));

            var error = false;
            document.querySelectorAll('input[name="cqMarks[]"]').forEach(function(input) {
                var val = parseFloat(input.value);
                if (!isNaN(val) && val > maxCQ) {
                    error = true;
                    showError(input, maxCQ);
                } else {
                    removeError(input);
                }
            });
            document.querySelectorAll('input[name="mcqMarks[]"]').forEach(function(input) {
                var val = parseFloat(input.value);
                if (!isNaN(val) && val > maxMCQ) {
                    error = true;
                    showError(input, maxMCQ);
                } else {
                    removeError(input);
                }
            });
            document.querySelectorAll('input[name="practical[]"]').forEach(function(input) {
                var val = parseFloat(input.value);
                if (!isNaN(val) && val > maxPractical) {
                    error = true;
                    showError(input, maxPractical);
                } else {
                    removeError(input);
                }
            });
            if(error) {
                alert('One or more marks fields exceed the allowed maximum value.');
                e.preventDefault();
                submitInProgress = false;
                return;
            }

            if (!isConfirmSubmit) {
                if (submissionActionInput) submissionActionInput.value = 'draft';
                if (confirmBlankMarksInput) confirmBlankMarksInput.value = '0';
                form.setAttribute('action', draftRoute);
                return;
            }

            const blankStats = scanBlankMarks();
            if (blankStats.blankFieldCount <= 0) {
                if (submissionActionInput) submissionActionInput.value = 'confirm';
                if (confirmBlankMarksInput) confirmBlankMarksInput.value = '0';
                form.setAttribute('action', confirmRoute);
                return;
            }

            e.preventDefault();
            const summary = `${blankStats.blankFieldCount} mark fields for ${blankStats.blankStudentCount} students are still blank. Confirming may treat these records according to the system's blank-mark policy.`;
            openBlankModal(summary);
        });
    }

    if (modalConfirmAnyway) {
        modalConfirmAnyway.addEventListener('click', function() {
            submitWithIntent('confirm_with_blanks', confirmRoute);
        });
    }

    if (modalSaveDraft) {
        modalSaveDraft.addEventListener('click', function() {
            submitWithIntent('draft', draftRoute);
        });
    }

    if (modalGoBack) {
        modalGoBack.addEventListener('click', function() {
            resetSubmissionIntent();
        });
    }

    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function() {
            submitInProgress = false;
            resetSubmissionIntent();
        });
    }
});
</script>
@endsection

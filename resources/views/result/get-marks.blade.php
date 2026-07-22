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

    $classData = \App\Models\classManage::find($classIdSafe);
    $sectionData = \App\Models\sectionManage::find($groupIdSafe);
    $optionalGroupData = !empty($optionalGroupIdSafe) ? \App\Models\Department::find($optionalGroupIdSafe) : null;
    $sessionData = \App\Models\sessionManage::find($sessionIdSafe);
    $examData = \App\Models\Exam::find($examIdSafe);
    $subjectData = \App\Models\Subject::find($subjectIdSafe);
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
        <form method="POST" class="card-body form form-group" action="{{ route('confirmMarks') }}">
                <div class="row">
                    <div class="col-6 col-md-4 mb-2"><b>Group/Section:</b>  {{ $sectionName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Group (Optional):</b>  {{ $optionalGroupName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Class:</b>  {{ $className }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Session:</b> {{ $session_name }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Exam:</b> {{ $examName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Subject:</b> {{ $subjectName }}</div>
                </div>
                @csrf
                @php
                    $isReadOnly = !empty($isFinalPublished) && !empty($isTeacherAdmin);
                @endphp
                @if($isReadOnly)
                    <div class="alert alert-warning mt-2">
                        Final result is published for this exam/class/session. Marks entry is locked for teachers.
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
                         @foreach($studentList as $std)
                        @php
                            $sessionTextForMark = optional(\App\Models\sessionManage::find((int)$sessionIdSafe))->session;
                            $stdSectionId = (int)($std->sectionName ?? 0);
                            $marksDataQuery = \App\Models\Marksheet::where('classId', $classIdSafe)
                                ->where('studentId', $std->id)
                                ->where('examId', $examIdSafe)
                                ->where('subjectId', $subjectIdSafe);

                            if(!empty($sessionIdSafe) || !empty($sessionTextForMark)){
                                $marksDataQuery->orderByRaw(
                                    'CASE WHEN sessionId = ? THEN 0 '.(!empty($sessionTextForMark) ? 'WHEN sessionId = ? THEN 1 ' : '').'ELSE 2 END',
                                    !empty($sessionTextForMark)
                                        ? [(string)$sessionIdSafe, (string)$sessionTextForMark]
                                        : [(string)$sessionIdSafe]
                                );
                            }

                            if(!empty($groupIdSafe)){
                                $marksDataQuery->orderByRaw('CASE WHEN groupId = ? THEN 0 ELSE 1 END', [$groupIdSafe]);
                            } elseif($stdSectionId > 0) {
                                $marksDataQuery->orderByRaw('CASE WHEN groupId = ? THEN 0 WHEN groupId IS NULL OR groupId = "" THEN 1 ELSE 2 END', [$stdSectionId]);
                            }

                            $marksData = $marksDataQuery
                                ->orderByDesc('id')
                                ->first();
                            $subjectMarks = $marksData ? $marksData->subjectMarks : "";
                            $objectMarks = $marksData ? $marksData->objectMarks : "";
                            $practicalMarks = $marksData ? $marksData->practicalMarks : "";
                            $currentUserId = session('cultivationAdmin');
                            $readonlyByOther = (!empty($isTeacherAdmin) && $marksData && $marksData->teacher_id && $marksData->teacher_id != $currentUserId);
                            $readonly = $readonlyByOther || $isReadOnly;
                            $enteredById = $marksData ? ($marksData->entered_by ?? $marksData->teacher_id) : null;
                            $enteredBy = $enteredById ? optional(\App\Models\CultivationAdmin::find($enteredById))->adminName : null;
                            $enteredRole = $marksData->entered_by_role ?? ($marksData && $marksData->teacher_id ? 'teacher' : null);
                            $updatedBy = ($marksData && $marksData->updated_by) ? optional(\App\Models\CultivationAdmin::find($marksData->updated_by))->adminName : null;
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
                                <input type="submit" value="Save" class="btn btn-success">
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

    // Prevent form submit if any value is greater than allowed
    var form = document.querySelector('form');
    if(form) {
        form.addEventListener('submit', function(e) {
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
            }
        });
    }
});
</script>
@endsection
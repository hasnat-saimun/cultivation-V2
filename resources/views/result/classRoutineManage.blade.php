@extends('result.include')
@section('backTitle')
Class Routine Management
@endsection
@section('backIndex')
@php
    $lookup = $lookup ?? [];
    $lookupClasses = $lookup['classes'] ?? collect();
    $lookupSections = $lookup['sections'] ?? collect();
    $lookupDepartments = $lookup['departments'] ?? collect();
    $lookupSessions = $lookup['sessions'] ?? collect();

    $itemId = $itemId ?? null;
    $title = 'Class Routine';
    $assignClass = '';
    $assignSection = '';
    $assignDepartment = '';
    $assignSession = '';
    $routineEntries = collect();

    if(!empty($itemId)):
        $items = \App\Models\ClassRoutine::with('entries')->find($itemId);
        if(!empty($items)):
            $title = $items->title;
            $assignClass = $items->assignClass;
            $assignSection = $items->assignSection;
            $assignDepartment = $items->assignDepartment;
            $assignSession = $items->assignSession;
            $routineEntries = $items->entries ?? collect();
        endif;
    endif;

    if($routineEntries->count() === 0) {
        $routineEntries = collect([(object)[
            'class_day' => '',
            'start_time' => '',
            'end_time' => '',
            'subject_id' => null,
            'subject_name' => '',
        ]]);
    }

    $subjectList = \App\Models\Subject::orderBy('subjectName', 'ASC')->get(['id', 'subjectName', 'assign_class']);
    $subjectListForJs = $subjectList->map(function ($subject) {
        return [
            'id' => (int) $subject->id,
            'name' => (string) $subject->subjectName,
            'assignClass' => (string) ($subject->assign_class ?? ''),
        ];
    })->values()->toArray();

    $dayOptions = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

    $teacherAssignmentScope = $teacherAssignmentScope ?? [
        'session_id' => null,
        'class_id' => null,
        'section_id' => null,
        'group_id' => null,
    ];

    $teacherAssignmentData = $teacherAssignmentData ?? [
        'teachers' => collect(),
        'assignments' => collect(),
    ];

    $taAssignSession = old('ta_assignSession', $teacherAssignmentScope['session_id'] ?? $assignSession);
    $taAssignClass = old('ta_assignClass', $teacherAssignmentScope['class_id'] ?? $assignClass);
    $taAssignSection = old('ta_assignSection', $teacherAssignmentScope['section_id'] ?? $assignSection);
    $taAssignDepartment = old('ta_assignDepartment', $teacherAssignmentScope['group_id'] ?? $assignDepartment);

    $teacherList = $teacherAssignmentData['teachers'] ?? collect();
    $teacherAssignments = $teacherAssignmentData['assignments'] ?? collect();

    if ($teacherAssignments->count() === 0) {
        $teacherAssignments = collect([(object)[
            'teacher_id' => '',
            'subject_id' => '',
        ]]);
    }
@endphp

<div class="row gutters-20 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">Class Routine Management</div>
            <div class="card-body cultivation">
                <style>
                    .routine-builder {
                        border: 2px solid #36b8a9;
                        border-radius: 8px;
                        overflow: hidden;
                    }
                    .routine-builder-head {
                        background: #36c6b6;
                        color: #fff;
                        padding: 10px 14px;
                        font-weight: 700;
                        letter-spacing: .3px;
                    }
                    .routine-builder-body {
                        padding: 14px;
                        background: #fbfdfd;
                    }
                    .field-label {
                        font-size: 13px;
                        font-weight: 700;
                        margin-bottom: 5px;
                    }
                    .routine-grid-input {
                        min-width: 120px;
                    }
                    .routine-entry-head {
                        background: #2fc3b2;
                        color: #fff;
                        border: 0;
                    }
                    #routine-row-table {
                        margin-bottom: 0;
                    }
                    #routine-row-table th,
                    #routine-row-table td {
                        vertical-align: middle;
                        padding: 7px 8px;
                    }
                    .routine-help {
                        font-size: 12px;
                        color: #4a4a4a;
                        margin-top: 8px;
                    }
                    .assign-grid-table th,
                    .assign-grid-table td {
                        vertical-align: middle;
                        padding: 7px 8px;
                    }

                    .form-check-label {
                        padding: 5px;
                    }
                    .form-check input[type="checkbox"] {
                        cursor: pointer;
                        position: absolute;
                        /* color: #000; */
                        width: 10px;
                        height: 10px;
                        top: 6px;
                        left: -9px;
                        margin: 5px;
                        z-index: 1;
                        opacity: 1;
                    }
                </style>

                <form action="{{ route('saveResultClassRoutine') }}" method="POST">
                    @csrf
                    <input type="hidden" name="itemId" value="{{ $itemId }}">

                    <div class="routine-builder mb-3">
                        <div class="routine-builder-head">Routine Setup</div>
                        <div class="routine-builder-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Title *</label>
                                    <input type="text" name="title" class="form-control" value="{{ $title }}" placeholder="Example: Class Routine" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Class *</label>
                                    <select name="assignClass" id="routine-assign-class" class="form-select" required>
                                        <option value="">Select Class</option>
                                        @php $classes = \App\Models\classManage::orderBy('id','DESC')->get(); @endphp
                                        @foreach($classes as $cls)
                                            <option value="{{ $cls->id }}" {{ (string)$assignClass === (string)$cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Session *</label>
                                    <select name="assignSession" class="form-select" required>
                                        <option value="">Select Session</option>
                                        @php $sessions = \App\Models\sessionManage::orderBy('id','DESC')->get(); @endphp
                                        @foreach($sessions as $sess)
                                            <option value="{{ $sess->id }}" {{ (string)$assignSession === (string)$sess->id ? 'selected' : '' }}>{{ $sess->session }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Section/Group</label>
                                    <select name="assignSection" class="form-select">
                                        <option value="">All</option>
                                        @php $sections = \App\Models\sectionManage::orderBy('id','DESC')->get(); @endphp
                                        @foreach($sections as $section)
                                            <option value="{{ $section->id }}" {{ (string)$assignSection === (string)$section->id ? 'selected' : '' }}>{{ $section->section }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Department</label>
                                    <select name="assignDepartment" class="form-select">
                                        <option value="">All</option>
                                        @php $departments = \App\Models\Department::orderBy('id','ASC')->get(); @endphp
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ (string)$assignDepartment === (string)$dept->id ? 'selected' : '' }}>{{ $dept->departmentName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="routine-builder mb-3">
                        <div class="routine-builder-head">Routine Rows</div>
                        <div class="routine-builder-body">
                            <div class="d-flex flex-wrap align-items-center mb-3" style="gap:8px;">
                                <span class="fw-bold">Auto Tiffin Time:</span>
                                <input type="time" id="auto-break-start" class="form-control form-control-sm" style="width:130px;" placeholder="Start">
                                <span class="text-muted">to</span>
                                <input type="time" id="auto-break-end" class="form-control form-control-sm" style="width:130px;" placeholder="End">
                                <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                                    @foreach($dayOptions as $day)
                                        <label class="mb-0"><input type="checkbox" name="auto_break_days[]" value="{{ $day }}" checked> {{ $day }}</label>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-sm btn-dark" onclick="setAutoTiffinTime()">Set Tiffin Automatically</button>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="mb-0 fw-bold">Day, Time, Subject</label>
                                <div class="d-flex align-items-center" style="gap:8px;">
                                    <select id="copy-from-day" class="form-select form-select-sm" style="min-width:130px;">
                                        <option value="">Copy From</option>
                                        @foreach($dayOptions as $day)
                                            <option value="{{ $day }}">{{ $day }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted">to</span>
                                    <select id="copy-to-day" class="form-select form-select-sm" style="min-width:130px;">
                                        <option value="">Copy To</option>
                                        @foreach($dayOptions as $day)
                                            <option value="{{ $day }}">{{ $day }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="copyDayRoutineRows()">Copy Day</button>
                                    <button type="button" class="btn btn-sm btn-info" onclick="addRoutineRow()">+ Add Row</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="routine-row-table">
                                    <thead>
                                        <tr class="routine-entry-head">
                                            <th style="width:20%">Day</th>
                                            <th style="width:16%">Start Time</th>
                                            <th style="width:16%">End Time</th>
                                            <th>Subject</th>
                                            <th style="width:60px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="routine-row-body">
                                        @foreach($routineEntries as $entry)
                                            @php
                                                $existingStart = !empty($entry->start_time) ? substr((string)$entry->start_time, 0, 5) : '';
                                                $existingEnd = !empty($entry->end_time) ? substr((string)$entry->end_time, 0, 5) : '';
                                            @endphp
                                            <tr>
                                                @php
                                                    $isBreakEntry = strtolower(trim((string)($entry->subject_name ?? ''))) === 'break/tiffin time';
                                                    $entrySubjectValue = $isBreakEntry ? '__BREAK__' : (string)($entry->subject_id ?? '');
                                                @endphp
                                                <td>
                                                    <select name="entry_day[]" class="form-select routine-grid-input">
                                                        <option value="">Select Day</option>
                                                        @foreach($dayOptions as $day)
                                                            <option value="{{ $day }}" {{ strtolower((string)($entry->class_day ?? '')) === strtolower($day) ? 'selected' : '' }}>{{ $day }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="time" name="entry_start_time[]" class="form-control routine-grid-input" value="{{ $existingStart }}"></td>
                                                <td><input type="time" name="entry_end_time[]" class="form-control routine-grid-input" value="{{ $existingEnd }}"></td>
                                                <td>
                                                    <select name="entry_subject_id[]" class="form-select subject-select" data-existing-subject-id="{{ $entrySubjectValue }}" data-existing-subject-name="{{ $entry->subject_name ?? '' }}">
                                                        <option value="">Select Subject</option>
                                                        <option value="__BREAK__" {{ $isBreakEntry ? 'selected' : '' }}>Break/Tiffin Time</option>
                                                        @foreach($subjectList as $subject)
                                                            <option value="{{ $subject->id }}" {{ (string)($entry->subject_id ?? '') === (string)$subject->id || ((empty($entry->subject_id) && ($entry->subject_name ?? '') === $subject->subjectName) ? 'selected' : '') }}>{{ $subject->subjectName }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeRoutineRow(this)">X</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="routine-help">Select class first to auto-filter matching subjects.</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success btn-lg mx-2" type="submit">Save</button>
                        <a class="btn btn-primary btn-lg mx-2" href="{{ route('resultClassRoutineManage') }}">Create New</a>
                    </div>
                </form>

                <hr class="my-4">

                <div class="routine-builder mb-3">
                    <div class="routine-builder-head">Teacher Wise Subject Assignment</div>
                    <div class="routine-builder-body">
                        <form action="{{ route('saveResultClassRoutineTeacherAssignments') }}" method="POST" id="teacher-assignment-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Session *</label>
                                    <select name="ta_assignSession" id="ta-assign-session" class="form-select" required>
                                        <option value="">Select Session</option>
                                        @php $taSessions = \App\Models\sessionManage::orderBy('id','DESC')->get(); @endphp
                                        @foreach($taSessions as $sess)
                                            <option value="{{ $sess->id }}" {{ (string)$taAssignSession === (string)$sess->id ? 'selected' : '' }}>{{ $sess->session }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Class *</label>
                                    <select name="ta_assignClass" id="ta-assign-class" class="form-select" required>
                                        <option value="">Select Class</option>
                                        @php $taClasses = \App\Models\classManage::orderBy('id','DESC')->get(); @endphp
                                        @foreach($taClasses as $cls)
                                            <option value="{{ $cls->id }}" {{ (string)$taAssignClass === (string)$cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Section/Group</label>
                                    <select name="ta_assignSection" id="ta-assign-section" class="form-select">
                                        <option value="">All/None</option>
                                        @php $taSections = \App\Models\sectionManage::orderBy('id','DESC')->get(); @endphp
                                        @foreach($taSections as $section)
                                            <option value="{{ $section->id }}" {{ (string)$taAssignSection === (string)$section->id ? 'selected' : '' }}>{{ $section->section }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Department</label>
                                    <select name="ta_assignDepartment" id="ta-assign-department" class="form-select">
                                        <option value="">All/None</option>
                                        @php $taDepartments = \App\Models\Department::orderBy('id','ASC')->get(); @endphp
                                        @foreach($taDepartments as $dept)
                                            <option value="{{ $dept->id }}" {{ (string)$taAssignDepartment === (string)$dept->id ? 'selected' : '' }}>{{ $dept->departmentName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="mb-0 fw-bold">Teacher + Subject</label>
                                <div class="d-flex align-items-center" style="gap:8px;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="reloadTeacherAssignmentScope()">Load Scope</button>
                                    <button type="button" class="btn btn-sm btn-info" onclick="addTeacherAssignmentRow()">+ Add Row</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered assign-grid-table" id="teacher-assignment-table">
                                    <thead>
                                        <tr class="routine-entry-head">
                                            <th style="width:30%">Teacher</th>
                                            <th style="width:25%">Subject</th>
                                            <th style="width:35%">Assign Days</th>
                                            <th style="width:50px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="teacher-assignment-body">
                                        @foreach($teacherAssignments as $ta)
                                            @php
                                                $assignedDays = [];
                                                if (!empty($ta->assigned_days)) {
                                                    $decoded = json_decode($ta->assigned_days, true);
                                                    if (is_array($decoded)) {
                                                        $assignedDays = $decoded;
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <select name="ta_teacher_id[]" class="form-select ta-teacher-select">
                                                        <option value="">Select Teacher</option>
                                                        @foreach($teacherList as $teacher)
                                                            <option value="{{ $teacher->id }}" {{ (string)($ta->teacher_id ?? '') === (string)$teacher->id ? 'selected' : '' }}>{{ $teacher->adminName }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="ta_subject_id[]" class="form-select ta-subject-select" data-existing-subject-id="{{ $ta->subject_id ?? '' }}">
                                                        <option value="">Select Subject</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-2" style="gap:6px;">
                                                        @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $day)
                                                            <label class="form-check" style="margin-bottom:0; white-space:nowrap;">
                                                                <input type="checkbox" class="form-check-input ta-day-checkbox" value="{{ $day }}" {{ in_array($day, $assignedDays) ? 'checked' : '' }} />
                                                                <span class="form-check-label" style="margin-left:4px;">{{ substr($day, 0, 3) }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    <input type="hidden" name="ta_assigned_days[]" class="ta-assigned-days-input" value="{{ json_encode($assignedDays) }}" />
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeTeacherAssignmentRow(this)">X</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <p class="routine-help mb-2">This assignment is used by Teacher Wise Routine format.</p>
                            <button type="submit" class="btn btn-warning">Save Teacher Assignment</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-header">Existing Class Routine</div>
            <div class="card-body cultivation">
                <table id="myTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Title</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Department</th>
                            <th>Session</th>
                            <th>Rows</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($routineList) && $routineList->count() > 0)
                            @php $x = 1; @endphp
                            @foreach($routineList as $item)
                                <tr>
                                    <td>{{ $x }}</td>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ optional($lookupClasses->get($item->assignClass))->className ?? '-' }}</td>
                                    <td>{{ optional($lookupSections->get($item->assignSection))->section ?? 'All' }}</td>
                                    <td>{{ optional($lookupDepartments->get($item->assignDepartment))->departmentName ?? 'All' }}</td>
                                    <td>{{ optional($lookupSessions->get($item->assignSession))->session ?? '-' }}</td>
                                    <td>{{ $item->entries_count ?? 0 }}</td>
                                    <td>
                                        <a href="{{ route('downloadResultClassRoutinePdf',['id'=>$item->id]) }}" title="Download PDF"><i class="fa-solid fa-file-pdf mx-2" style="color: #b9102b;"></i></a>
                                        <a href="{{ route('viewResultClassRoutine',['id'=>$item->id]) }}" title="View"><i class="fa-solid fa-eye mx-2" style="color: #12805c;"></i></a>
                                        <a href="{{ route('viewResultClassRoutineTeacherWise',['id'=>$item->id]) }}" title="Teacher Wise View"><i class="fa-solid fa-chalkboard-user mx-2" style="color: #0b7285;"></i></a>
                                        <a href="{{ route('downloadResultClassRoutineTeacherWisePdf',['id'=>$item->id]) }}" title="Teacher Wise PDF"><i class="fa-solid fa-file-circle-check mx-2" style="color: #7c2d12;"></i></a>
                                        <a href="{{ route('printResultClassRoutine',['id'=>$item->id]) }}" target="_blank" title="Print"><i class="fa-solid fa-print mx-2" style="color: #1666c1;"></i></a>
                                        <a href="{{ route('editResultClassRoutine',['id'=>$item->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                        <a href="{{ route('delResultClassRoutine',['id'=>$item->id]) }}" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                                    </td>
                                </tr>
                                @php $x++; @endphp
                            @endforeach
                        @else
                            <tr>
                                <td>1</td>
                                <td>Sorry! No data found</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>0</td>
                                <td>-</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var subjectList = {!! json_encode($subjectListForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    var flashSuccessMessage = @json(session('success'));
    var flashErrorMessage = @json(session('error'));
    var flashValidationErrors = @json($errors->all());

    function subjectPriority(name) {
        var n = String(name || '').toLowerCase();

        if (n.indexOf('ict') !== -1 || n.indexOf('information') !== -1) return 90;
        if (n.indexOf('bangla') !== -1 || n.indexOf('bengali') !== -1) return 10;
        if (n.indexOf('english') !== -1) return 20;
        if (n.indexOf('math') !== -1 || n.indexOf('mathematics') !== -1) return 30;
        if (n.indexOf('general science') !== -1 || n.indexOf('science') !== -1) return 40;
        if (n.indexOf('social science') !== -1 || n.indexOf('global studies') !== -1 || n.indexOf('social') !== -1) return 50;
        if (n.indexOf('relig') !== -1 || n.indexOf('islam') !== -1 || n.indexOf('hindu') !== -1 || n.indexOf('budd') !== -1 || n.indexOf('christ') !== -1) return 60;
        return 70;
    }

    function getOrderedSubjectList() {
        return subjectList.slice().sort(function(a, b) {
            var pa = subjectPriority(a.name);
            var pb = subjectPriority(b.name);
            if (pa !== pb) return pa - pb;
            return String(a.name).localeCompare(String(b.name));
        });
    }

    function parseClassIds(assignClass) {
        if (!assignClass || assignClass === '0') {
            return [];
        }

        var matches = String(assignClass).match(/\d+/g);
        if (!matches) {
            return [];
        }

        return matches.map(function(v) { return parseInt(v, 10); }).filter(function(v){ return !Number.isNaN(v); });
    }

    function isSubjectAllowedForClass(subject, classId) {
        if (!classId) {
            return true;
        }

        var assignClass = String(subject.assignClass || '').trim();
        if (assignClass === '' || assignClass === '0') {
            return true;
        }

        if (/^\d+$/.test(assignClass)) {
            return parseInt(assignClass, 10) === classId;
        }

        var classIds = parseClassIds(assignClass);
        if (!classIds.length) {
            return true;
        }

        return classIds.indexOf(classId) !== -1;
    }

    function normalizeDay(dayValue) {
        return String(dayValue || '').trim().toLowerCase();
    }

    function showValidationWarning(message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'warning',
                title: 'Validation Warning',
                text: message,
                confirmButtonColor: '#1f8b7b'
            });
            return;
        }

        alert(message);
    }

    function isSubjectAlreadyUsedOnDay(dayValue, subjectId, currentRow) {
        var dayKey = normalizeDay(dayValue);
        if (!dayKey || !subjectId) {
            return false;
        }

        var rows = Array.prototype.slice.call(document.querySelectorAll('#routine-row-body tr'));
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            if (row === currentRow) {
                continue;
            }

            var daySelect = row.querySelector('select[name="entry_day[]"]');
            var subjectSelect = row.querySelector('select[name="entry_subject_id[]"]');

            var otherDay = normalizeDay(daySelect ? daySelect.value : '');
            var otherSubjectId = subjectSelect ? String(subjectSelect.value || '') : '';

            if (otherDay === dayKey && otherSubjectId !== '' && otherSubjectId === String(subjectId)) {
                return true;
            }
        }

        return false;
    }

    function parseTimeToMinutes(timeValue) {
        var value = String(timeValue || '').trim();
        if (!value) {
            return null;
        }

        var parts = value.split(':');
        if (parts.length < 2) {
            return null;
        }

        var hour = parseInt(parts[0], 10);
        var minute = parseInt(parts[1], 10);
        if (Number.isNaN(hour) || Number.isNaN(minute)) {
            return null;
        }

        return (hour * 60) + minute;
    }

    function isTimeOverlapOnDay(dayValue, startTime, endTime, currentRow) {
        var dayKey = normalizeDay(dayValue);
        var startMin = parseTimeToMinutes(startTime);
        var endMin = parseTimeToMinutes(endTime);

        if (!dayKey || startMin === null || endMin === null) {
            return false;
        }

        var rows = Array.prototype.slice.call(document.querySelectorAll('#routine-row-body tr'));
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            if (row === currentRow) {
                continue;
            }

            var daySelect = row.querySelector('select[name="entry_day[]"]');
            var startInput = row.querySelector('input[name="entry_start_time[]"]');
            var endInput = row.querySelector('input[name="entry_end_time[]"]');

            var otherDay = normalizeDay(daySelect ? daySelect.value : '');
            if (otherDay !== dayKey) {
                continue;
            }

            var otherStart = parseTimeToMinutes(startInput ? startInput.value : '');
            var otherEnd = parseTimeToMinutes(endInput ? endInput.value : '');
            if (otherStart === null || otherEnd === null) {
                continue;
            }

            if (startMin < otherEnd && otherStart < endMin) {
                return true;
            }
        }

        return false;
    }

    function buildSubjectOptions(currentValue) {
        var classSelect = document.getElementById('routine-assign-class');
        var classId = classSelect && classSelect.value ? parseInt(classSelect.value, 10) : 0;

        var options = ['<option value="">Select Subject</option>'];
        var breakSelected = String(currentValue || '') === '__BREAK__' ? ' selected' : '';
        options.push('<option value="__BREAK__"' + breakSelected + '>Break/Tiffin Time</option>');
        var orderedList = getOrderedSubjectList();

        orderedList.forEach(function(subject) {
            if (!isSubjectAllowedForClass(subject, classId)) {
                return;
            }
            var selected = String(currentValue || '') === String(subject.id) ? ' selected' : '';
            options.push('<option value="' + subject.id + '"' + selected + '>' + subject.name + '</option>');
        });

        return options.join('');
    }

    function refreshSubjectDropdowns() {
        var selects = Array.prototype.slice.call(document.querySelectorAll('select.subject-select'));
        selects.forEach(function(sel) {
            var row = sel.closest('tr');
            var daySelect = row ? row.querySelector('select[name="entry_day[]"]') : null;
            var selectedDay = daySelect ? daySelect.value : '';
            var currentValue = sel.value || (sel.getAttribute('data-existing-subject-id') || '');

            sel.innerHTML = buildSubjectOptions('');

            var options = Array.prototype.slice.call(sel.options);
            options.forEach(function(opt) {
                if (!opt.value) {
                    return;
                }

                if (isSubjectAlreadyUsedOnDay(selectedDay, opt.value, row)) {
                    opt.remove();
                }
            });

            if (currentValue && sel.querySelector('option[value="' + currentValue + '"]')) {
                sel.value = currentValue;
            } else {
                sel.value = '';
                sel.setAttribute('data-existing-subject-id', '');
                sel.setAttribute('data-existing-subject-name', '');
            }

            sel.removeAttribute('data-existing-subject-id');
        });
    }

    function buildTeacherAssignmentSubjectOptions(currentValue) {
        var classSelect = document.getElementById('ta-assign-class');
        var classId = classSelect && classSelect.value ? parseInt(classSelect.value, 10) : 0;
        var options = ['<option value="">Select Subject</option>'];
        var orderedList = getOrderedSubjectList();

        orderedList.forEach(function(subject) {
            if (!isSubjectAllowedForClass(subject, classId)) {
                return;
            }

            var selected = String(currentValue || '') === String(subject.id) ? ' selected' : '';
            options.push('<option value="' + subject.id + '"' + selected + '>' + subject.name + '</option>');
        });

        return options.join('');
    }

    function refreshTeacherAssignmentSubjectDropdowns() {
        var selects = Array.prototype.slice.call(document.querySelectorAll('select.ta-subject-select'));
        selects.forEach(function(sel) {
            var currentValue = sel.value || (sel.getAttribute('data-existing-subject-id') || '');
            sel.innerHTML = buildTeacherAssignmentSubjectOptions(currentValue);
            if (currentValue && sel.querySelector('option[value="' + currentValue + '"]')) {
                sel.value = currentValue;
            } else {
                sel.value = '';
            }
            sel.removeAttribute('data-existing-subject-id');
        });
    }

    function addTeacherAssignmentRow() {
        var teacherOptions = ['<option value="">Select Teacher</option>'];
        var teacherSelects = document.querySelectorAll('.ta-teacher-select option');
        if (teacherSelects.length > 0) {
            teacherSelects.forEach(function(option, idx) {
                if (idx === 0) {
                    return;
                }
                teacherOptions.push('<option value="' + option.value + '">' + option.text + '</option>');
            });
        }

        var daysHtml = '';
        var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        days.forEach(function(day) {
            daysHtml += '<label class="form-check" style="margin-bottom:0; white-space:nowrap;">' +
                '<input type="checkbox" class="form-check-input ta-day-checkbox" value="' + day + '" />' +
                '<span class="form-check-label" style="margin-left:4px;">' + day.substring(0, 3) + '</span>' +
                '</label> ';
        });

        var html = '' +
            '<tr>' +
                '<td><select name="ta_teacher_id[]" class="form-select ta-teacher-select">' + teacherOptions.join('') + '</select></td>' +
                '<td><select name="ta_subject_id[]" class="form-select ta-subject-select" data-existing-subject-id=""><option value="">Select Subject</option></select></td>' +
                '<td><div class="d-flex flex-wrap gap-2" style="gap:6px;">' + daysHtml + '</div><input type="hidden" name="ta_assigned_days[]" class="ta-assigned-days-input" value="[]" /></td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeTeacherAssignmentRow(this)">X</button></td>' +
            '</tr>';

        document.getElementById('teacher-assignment-body').insertAdjacentHTML('beforeend', html);
        refreshTeacherAssignmentSubjectDropdowns();
        attachDayCheckboxListeners();
    }

    function removeTeacherAssignmentRow(btn) {
        var tbody = document.getElementById('teacher-assignment-body');
        if (!tbody) {
            return;
        }

        if (tbody.querySelectorAll('tr').length <= 1) {
            return;
        }

        btn.closest('tr').remove();
    }

    function attachDayCheckboxListeners() {
        var dayCheckboxes = document.querySelectorAll('.ta-day-checkbox');
        dayCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var row = this.closest('tr');
                if (!row) return;
                
                var selectedDays = Array.prototype.filter.call(row.querySelectorAll('.ta-day-checkbox'), function(cb) { return cb.checked; })
                    .map(function(cb) { return cb.value; });
                var hiddenInput = row.querySelector('.ta-assigned-days-input');
                if (hiddenInput) {
                    hiddenInput.value = JSON.stringify(selectedDays);
                }
            });
        });
    }

    function reloadTeacherAssignmentScope() {
        var sessionSel = document.getElementById('ta-assign-session');
        var classSel = document.getElementById('ta-assign-class');
        var sectionSel = document.getElementById('ta-assign-section');
        var groupSel = document.getElementById('ta-assign-department');

        var sessionId = sessionSel ? String(sessionSel.value || '').trim() : '';
        var classId = classSel ? String(classSel.value || '').trim() : '';
        var sectionId = sectionSel ? String(sectionSel.value || '').trim() : '';
        var groupId = groupSel ? String(groupSel.value || '').trim() : '';

        if (!sessionId) {
            showValidationWarning('Please select session before loading assignment scope.');
            return;
        }

        if (!classId) {
            showValidationWarning('Please select class before loading assignment scope.');
            return;
        }

        var baseUrl = '{{ route('resultClassRoutineManage') }}';
        var params = new URLSearchParams();
        params.set('ta_session', sessionId);
        params.set('ta_class', classId);
        if (sectionId) {
            params.set('ta_section', sectionId);
        }
        if (groupId) {
            params.set('ta_group', groupId);
        }

        window.location.href = baseUrl + '?' + params.toString();
    }

    function addRoutineRow() {
        var html = '' +
            '<tr>' +
                '<td>' +
                    '<select name="entry_day[]" class="form-select routine-grid-input day-select">' +
                        '<option value="">Select Day</option>' +
                        '<option value="Sunday">Sunday</option>' +
                        '<option value="Monday">Monday</option>' +
                        '<option value="Tuesday">Tuesday</option>' +
                        '<option value="Wednesday">Wednesday</option>' +
                        '<option value="Thursday">Thursday</option>' +
                    '</select>' +
                '</td>' +
                '<td><input type="time" name="entry_start_time[]" class="form-control routine-grid-input"></td>' +
                '<td><input type="time" name="entry_end_time[]" class="form-control routine-grid-input"></td>' +
                '<td><select name="entry_subject_id[]" class="form-select subject-select" data-existing-subject-id="" data-existing-subject-name=""></select></td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeRoutineRow(this)">X</button></td>' +
            '</tr>';

        document.getElementById('routine-row-body').insertAdjacentHTML('beforeend', html);
        attachRowEvents(document.querySelector('#routine-row-body tr:last-child'));
        refreshSubjectDropdowns();
    }

    function addRoutineRowWithData(day, startTime, endTime, subjectId) {
        addRoutineRow();
        var row = document.querySelector('#routine-row-body tr:last-child');
        if (!row) {
            return;
        }

        var daySelect = row.querySelector('select[name="entry_day[]"]');
        var startInput = row.querySelector('input[name="entry_start_time[]"]');
        var endInput = row.querySelector('input[name="entry_end_time[]"]');
        var subjectSelect = row.querySelector('select[name="entry_subject_id[]"]');

        if (daySelect) daySelect.value = day || '';
        if (startInput) startInput.value = startTime || '';
        if (endInput) endInput.value = endTime || '';
        if (subjectSelect && subjectId) {
            subjectSelect.setAttribute('data-existing-subject-id', String(subjectId));
            subjectSelect.value = String(subjectId);
        }

        refreshSubjectDropdowns();

        if (subjectSelect && subjectId) {
            subjectSelect.value = String(subjectId);
            var selectedOption = subjectSelect.options[subjectSelect.selectedIndex];
            subjectSelect.setAttribute('data-existing-subject-id', subjectSelect.value || '');
            subjectSelect.setAttribute('data-existing-subject-name', selectedOption ? selectedOption.text : '');
        }
    }

    function removeRoutineRow(btn) {
        var tbody = document.getElementById('routine-row-body');
        if (!tbody) {
            return;
        }

        if (tbody.querySelectorAll('tr').length <= 1) {
            return;
        }

        btn.closest('tr').remove();
        refreshSubjectDropdowns();
    }

    function getDayRows(dayName) {
        var rows = Array.prototype.slice.call(document.querySelectorAll('#routine-row-body tr'));
        return rows.filter(function(row) {
            var daySelect = row.querySelector('select[name="entry_day[]"]');
            return normalizeDay(daySelect ? daySelect.value : '') === normalizeDay(dayName);
        });
    }

    function isBreakSubjectValue(value) {
        return String(value || '').trim() === '__BREAK__';
    }

    function removeBreakRowsForDay(dayName) {
        var dayRows = getDayRows(dayName);
        dayRows.forEach(function(row) {
            var subjectSelect = row.querySelector('select[name="entry_subject_id[]"]');
            var subjectValue = subjectSelect ? String(subjectSelect.value || '').trim() : '';
            var existingName = subjectSelect ? String(subjectSelect.getAttribute('data-existing-subject-name') || '').trim().toLowerCase() : '';

            if (isBreakSubjectValue(subjectValue) || existingName === 'break/tiffin time') {
                row.remove();
            }
        });
    }

    function setAutoTiffinTime() {
        var startInput = document.getElementById('auto-break-start');
        var endInput = document.getElementById('auto-break-end');
        var startValue = startInput ? String(startInput.value || '').trim() : '';
        var endValue = endInput ? String(endInput.value || '').trim() : '';

        if (!startValue || !endValue) {
            showValidationWarning('Please select both Tiffin start and end time.');
            return;
        }

        var startMin = parseTimeToMinutes(startValue);
        var endMin = parseTimeToMinutes(endValue);
        if (startMin === null || endMin === null) {
            showValidationWarning('Invalid Tiffin time format.');
            return;
        }

        if (startMin >= endMin) {
            showValidationWarning('Tiffin Start Time must be earlier than End Time.');
            return;
        }

        var dayChecks = Array.prototype.slice.call(document.querySelectorAll('input[name="auto_break_days[]"]:checked'));
        var selectedDays = dayChecks.map(function(item){ return String(item.value || '').trim(); }).filter(Boolean);

        if (!selectedDays.length) {
            showValidationWarning('Please select at least one day for auto tiffin.');
            return;
        }

        selectedDays.forEach(function(dayName) {
            removeBreakRowsForDay(dayName);
        });

        var conflictDays = [];
        var createdCount = 0;

        selectedDays.forEach(function(dayName) {
            if (isTimeOverlapOnDay(dayName, startValue, endValue, null)) {
                conflictDays.push(dayName);
                return;
            }

            addRoutineRowWithData(dayName, startValue, endValue, '__BREAK__');
            createdCount++;
        });

        refreshSubjectDropdowns();

        if (createdCount > 0 && conflictDays.length === 0) {
            Swal.fire({
                icon: 'success',
                title: 'Tiffin Time Set',
                text: 'Tiffin time added for ' + createdCount + ' day(s).',
                confirmButtonColor: '#1f8b7b'
            });
            return;
        }

        if (createdCount > 0 && conflictDays.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Partial Update',
                html: 'Tiffin time added for ' + createdCount + ' day(s).<br>Skipped day(s) due to time overlap: <b>' + conflictDays.join(', ') + '</b>.',
                confirmButtonColor: '#f0ad4e'
            });
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'No Day Updated',
            html: 'Selected day(s) have overlapping class times for this slot: <b>' + conflictDays.join(', ') + '</b>.',
            confirmButtonColor: '#f0ad4e'
        });
    }

    function copyDayRoutineRows() {
        var fromDaySelect = document.getElementById('copy-from-day');
        var toDaySelect = document.getElementById('copy-to-day');
        var fromDay = fromDaySelect ? String(fromDaySelect.value || '').trim() : '';
        var toDay = toDaySelect ? String(toDaySelect.value || '').trim() : '';

        if (!fromDay || !toDay) {
            showValidationWarning('Please select both Copy From and Copy To days.');
            return;
        }

        if (normalizeDay(fromDay) === normalizeDay(toDay)) {
            showValidationWarning('Copy From and Copy To day cannot be the same.');
            return;
        }

        var sourceRows = getDayRows(fromDay);
        if (!sourceRows.length) {
            showValidationWarning('No rows found on ' + fromDay + ' to copy.');
            return;
        }

        var rowsToCopy = [];
        sourceRows.forEach(function(row) {
            var startInput = row.querySelector('input[name="entry_start_time[]"]');
            var endInput = row.querySelector('input[name="entry_end_time[]"]');
            var subjectSelect = row.querySelector('select[name="entry_subject_id[]"]');

            var startValue = startInput ? String(startInput.value || '').trim() : '';
            var endValue = endInput ? String(endInput.value || '').trim() : '';
            var subjectId = subjectSelect ? String(subjectSelect.value || '').trim() : '';

            if (startValue && endValue && subjectId) {
                rowsToCopy.push({
                    start: startValue,
                    end: endValue,
                    subjectId: subjectId
                });
            }
        });

        if (!rowsToCopy.length) {
            showValidationWarning('No complete rows found on ' + fromDay + ' to copy.');
            return;
        }

        var targetRows = getDayRows(toDay);
        var confirmText = targetRows.length
            ? ('This will replace ' + targetRows.length + ' existing row(s) on ' + toDay + '.')
            : ('This will create ' + rowsToCopy.length + ' row(s) on ' + toDay + '.');

        var runCopy = function() {
            targetRows.forEach(function(row) {
                row.remove();
            });

            rowsToCopy.forEach(function(item) {
                addRoutineRowWithData(toDay, item.start, item.end, item.subjectId);
            });

            refreshSubjectDropdowns();

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Copied',
                    text: 'Copied ' + rowsToCopy.length + ' row(s) from ' + fromDay + ' to ' + toDay + '.',
                    confirmButtonColor: '#1f8b7b'
                });
            }
        };

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'question',
                title: 'Copy Day Routine?',
                text: confirmText,
                showCancelButton: true,
                confirmButtonText: 'Yes, Copy',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#1f8b7b'
            }).then(function(result) {
                if (result.isConfirmed) {
                    runCopy();
                }
            });
            return;
        }

        if (confirm(confirmText)) {
            runCopy();
        }
    }

    function attachRowEvents(row) {
        if (!row) {
            return;
        }

        var daySelect = row.querySelector('select[name="entry_day[]"]');
        if (daySelect) {
            daySelect.addEventListener('change', function() {
                var subjectSelect = row.querySelector('select[name="entry_subject_id[]"]');
                var selectedSubjectId = subjectSelect ? subjectSelect.value : '';

                if (selectedSubjectId && isSubjectAlreadyUsedOnDay(this.value, selectedSubjectId, row)) {
                    if (subjectSelect) {
                        subjectSelect.value = '';
                    }
                    showValidationWarning('This subject is already added on ' + this.value + '. Please choose another subject.');
                }

                refreshSubjectDropdowns();
            });
        }

        var subjectSelect = row.querySelector('select[name="entry_subject_id[]"]');
        if (subjectSelect) {
            subjectSelect.addEventListener('change', function() {
                var rowDaySelect = row.querySelector('select[name="entry_day[]"]');
                var rowDay = rowDaySelect ? rowDaySelect.value : '';

                if (this.value && !rowDay) {
                    this.value = '';
                    showValidationWarning('Please select day first.');
                    refreshSubjectDropdowns();
                    return;
                }

                if (this.value && isSubjectAlreadyUsedOnDay(rowDay, this.value, row)) {
                    this.value = '';
                    showValidationWarning('This subject is already added on ' + rowDay + '.');
                    refreshSubjectDropdowns();
                    return;
                }

                var selectedOption = this.options[this.selectedIndex];
                this.setAttribute('data-existing-subject-id', this.value || '');
                this.setAttribute('data-existing-subject-name', selectedOption ? selectedOption.text : '');
                refreshSubjectDropdowns();
            });
        }

        var startInput = row.querySelector('input[name="entry_start_time[]"]');
        var endInput = row.querySelector('input[name="entry_end_time[]"]');

        function validateTimeRow(changedInput) {
            var rowDaySelect = row.querySelector('select[name="entry_day[]"]');
            var rowDay = rowDaySelect ? rowDaySelect.value : '';
            var startValue = startInput ? String(startInput.value || '').trim() : '';
            var endValue = endInput ? String(endInput.value || '').trim() : '';

            if (!startValue || !endValue) {
                return;
            }

            var startMin = parseTimeToMinutes(startValue);
            var endMin = parseTimeToMinutes(endValue);

            if (startMin === null || endMin === null) {
                return;
            }

            if (startMin >= endMin) {
                if (changedInput) {
                    changedInput.value = '';
                }
                showValidationWarning('Start Time must be earlier than End Time.');
                return;
            }

            if (rowDay && isTimeOverlapOnDay(rowDay, startValue, endValue, row)) {
                if (changedInput) {
                    changedInput.value = '';
                }
                showValidationWarning('Overlapping time range found on ' + rowDay + '. Please keep separate time slots.');
            }
        }

        if (startInput) {
            startInput.addEventListener('change', function() {
                validateTimeRow(this);
            });
        }

        if (endInput) {
            endInput.addEventListener('change', function() {
                validateTimeRow(this);
            });
        }
    }

    function hasDuplicateDaySubjectInForm() {
        var rows = Array.prototype.slice.call(document.querySelectorAll('#routine-row-body tr'));
        var daySubjectMap = {};

        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var daySelect = row.querySelector('select[name="entry_day[]"]');
            var subjectSelect = row.querySelector('select[name="entry_subject_id[]"]');
            var dayText = daySelect ? String(daySelect.value || '').trim() : '';
            var dayKey = normalizeDay(dayText);
            var subjectId = subjectSelect ? String(subjectSelect.value || '') : '';

            if (!dayKey || !subjectId) {
                continue;
            }

            var key = dayKey + '|' + subjectId;
            if (daySubjectMap[key]) {
                return dayText;
            }

            daySubjectMap[key] = true;
        }

        return '';
    }

    function getRoutineFormValidationError() {
        var rows = Array.prototype.slice.call(document.querySelectorAll('#routine-row-body tr'));
        var dayTimeRanges = {};

        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var daySelect = row.querySelector('select[name="entry_day[]"]');
            var subjectSelect = row.querySelector('select[name="entry_subject_id[]"]');
            var startInput = row.querySelector('input[name="entry_start_time[]"]');
            var endInput = row.querySelector('input[name="entry_end_time[]"]');

            var dayText = daySelect ? String(daySelect.value || '').trim() : '';
            var dayKey = normalizeDay(dayText);
            var subjectId = subjectSelect ? String(subjectSelect.value || '').trim() : '';
            var startValue = startInput ? String(startInput.value || '').trim() : '';
            var endValue = endInput ? String(endInput.value || '').trim() : '';

            if (!dayText && !subjectId && !startValue && !endValue) {
                continue;
            }

            if (!dayText) {
                return 'Day is required for each routine row.';
            }

            if (!subjectId) {
                return 'Subject is required for each routine row.';
            }

            if (!startValue || !endValue) {
                return 'Start Time and End Time are required for each routine row.';
            }

            var startMin = parseTimeToMinutes(startValue);
            var endMin = parseTimeToMinutes(endValue);
            if (startMin === null || endMin === null) {
                return 'Invalid time format in routine rows.';
            }

            if (startMin >= endMin) {
                return 'Start Time must be earlier than End Time for ' + dayText + '.';
            }

            if (!dayTimeRanges[dayKey]) {
                dayTimeRanges[dayKey] = [];
            }

            for (var j = 0; j < dayTimeRanges[dayKey].length; j++) {
                var range = dayTimeRanges[dayKey][j];
                if (startMin < range.end && range.start < endMin) {
                    return 'Overlapping time range found on ' + dayText + '. Please keep separate time slots.';
                }
            }

            dayTimeRanges[dayKey].push({
                start: startMin,
                end: endMin
            });
        }

        return '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (flashSuccessMessage) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: flashSuccessMessage,
                confirmButtonColor: '#1f8b7b'
            });
        }

        if (flashErrorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: flashErrorMessage,
                confirmButtonColor: '#d33'
            });
        }

        if (Array.isArray(flashValidationErrors) && flashValidationErrors.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                html: flashValidationErrors.join('<br>'),
                confirmButtonColor: '#f0ad4e'
            });
        }

        var rows = Array.prototype.slice.call(document.querySelectorAll('#routine-row-body tr'));
        rows.forEach(function(row) {
            attachRowEvents(row);
        });

        var classSelect = document.getElementById('routine-assign-class');
        if (classSelect) {
            classSelect.addEventListener('change', refreshSubjectDropdowns);
        }

        var routineForm = document.querySelector('form[action="{{ route('saveResultClassRoutine') }}"]');
        if (routineForm) {
            routineForm.addEventListener('submit', function(e) {
                var rowError = getRoutineFormValidationError();
                if (rowError) {
                    e.preventDefault();
                    showValidationWarning(rowError);
                    return;
                }

                var duplicateDay = hasDuplicateDaySubjectInForm();
                if (duplicateDay) {
                    e.preventDefault();
                    showValidationWarning('Duplicate subject found on ' + duplicateDay + '. Please keep unique subjects per day.');
                }
            });
        }

        var taClassSelect = document.getElementById('ta-assign-class');
        if (taClassSelect) {
            taClassSelect.addEventListener('change', refreshTeacherAssignmentSubjectDropdowns);
        }

        var taForm = document.getElementById('teacher-assignment-form');
        if (taForm) {
            taForm.addEventListener('submit', function(e) {
                var taRows = Array.prototype.slice.call(document.querySelectorAll('#teacher-assignment-body tr'));
                var hasAnyRow = false;

                for (var i = 0; i < taRows.length; i++) {
                    var row = taRows[i];
                    var teacher = row.querySelector('select[name="ta_teacher_id[]"]');
                    var subject = row.querySelector('select[name="ta_subject_id[]"]');
                    var teacherVal = teacher ? String(teacher.value || '').trim() : '';
                    var subjectVal = subject ? String(subject.value || '').trim() : '';

                    if (!teacherVal && !subjectVal) {
                        continue;
                    }

                    hasAnyRow = true;
                    if (!teacherVal || !subjectVal) {
                        e.preventDefault();
                        showValidationWarning('Teacher and Subject are required for each assignment row.');
                        return;
                    }

                    // Validate at least one day is selected
                    var dayCheckboxes = row.querySelectorAll('.ta-day-checkbox');
                    var anyDayChecked = Array.prototype.some.call(dayCheckboxes, function(cb) { return cb.checked; });
                    if (!anyDayChecked) {
                        e.preventDefault();
                        showValidationWarning('Please select at least one day for each teacher-subject assignment.');
                        return;
                    }

                    // Update the hidden input with selected days
                    var selectedDays = Array.prototype.filter.call(dayCheckboxes, function(cb) { return cb.checked; })
                        .map(function(cb) { return cb.value; });
                    var hiddenInput = row.querySelector('.ta-assigned-days-input');
                    if (hiddenInput) {
                        hiddenInput.value = JSON.stringify(selectedDays);
                    }
                }

                if (!hasAnyRow) {
                    e.preventDefault();
                    showValidationWarning('Please add at least one teacher assignment row.');
                }
            });
        }

        refreshSubjectDropdowns();
        refreshTeacherAssignmentSubjectDropdowns();
        attachDayCheckboxListeners();
    });
</script>
@endsection

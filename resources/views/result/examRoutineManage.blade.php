@extends('result.include')
@section('backTitle')
Exam Routine Management
@endsection
@section('backIndex')
@php
    $lookup = $lookup ?? [];
    $lookupExams = $lookup['exams'] ?? collect();
    $lookupClasses = $lookup['classes'] ?? collect();
    $lookupSections = $lookup['sections'] ?? collect();
    $lookupDepartments = $lookup['departments'] ?? collect();
    $lookupSessions = $lookup['sessions'] ?? collect();

    if(!empty($itemId)):
        $items = \App\Models\ExamRoutine::with('entries.subject')->where('status', 'result_routine')->find($itemId);
        if(!empty($items)):
            $title = $items->title;
            $assignClass = $items->assignClass;
            $assignSection = $items->assignSection;
            $assignDepartment = $items->assignDepartment;
            $assignSession = $items->assignSession;
            $assignExam = $items->assignExam;
            $routineEntries = $items->entries ?? collect();
        endif;
    else:
        $itemId = null;
        $title = '';
        $assignClass = '';
        $assignSection = '';
        $assignDepartment = '';
        $assignSession = '';
        $assignExam = '';
        $routineEntries = collect();
    endif;

    if($routineEntries->count() === 0) {
        $routineEntries = collect([(object)[
            'exam_date' => null,
            'exam_day' => '',
            'start_time' => '',
            'end_time' => '',
            'exam_time' => '',
            'subject_id' => null,
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
@endphp

<div class="row gutters-20 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">Exam Routine (Result Management)</div>
            <div class="card-body cultivation">
                <div class="row">
                    <div class="col-12">
                        @if(session()->has('success'))
                            <div class="alert alert-success w-100">{{ session()->get('success') }}</div>
                        @endif
                        @if(session()->has('error'))
                            <div class="alert alert-danger w-100">{{ session()->get('error') }}</div>
                        @endif
                    </div>
                </div>

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
                        min-width: 130px;
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
                </style>

                <form action="{{ route('saveResultExamRoutine') }}" method="POST">
                    @csrf
                    <input type="hidden" name="itemId" value="{{ $itemId }}">

                    <div class="routine-builder mb-3">
                        <div class="routine-builder-head">Routine Setup</div>
                        <div class="routine-builder-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Exam *</label>
                                    <select name="assignExam" class="form-select" required>
                                        <option value="">Select Exam</option>
                                        @php $examList = \App\Models\Exam::orderBy('id','DESC')->get(); @endphp
                                        @foreach($examList as $exam)
                                            <option value="{{ $exam->id }}" {{ (string)$assignExam === (string)$exam->id ? 'selected' : '' }}>{{ $exam->examName }}</option>
                                        @endforeach
                                    </select>
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
                                <div class="col-md-8 mb-3">
                                    <label class="field-label">Title</label>
                                    <input type="text" class="form-control" value="Auto generated from selected exam (Example: Annual Exam Routine)" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="routine-builder mb-3">
                        <div class="routine-builder-head">Routine Rows</div>
                        <div class="routine-builder-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0 fw-bold">Date, Day, Time, Subject</label>
                            <button type="button" class="btn btn-sm btn-info" onclick="addRoutineRow()">+ Add Row</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="routine-row-table">
                                <thead>
                                    <tr class="routine-entry-head">
                                        <th style="width:16%">Date</th>
                                        <th style="width:14%">Day</th>
                                        <th style="width:14%">Start Time</th>
                                        <th style="width:14%">End Time</th>
                                        <th>Subject</th>
                                        <th style="width:60px">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="routine-row-body">
                                    @foreach($routineEntries as $entry)
                                        @php
                                            $existingStart = !empty($entry->start_time) ? substr((string)$entry->start_time, 0, 5) : '';
                                            $existingEnd = !empty($entry->end_time) ? substr((string)$entry->end_time, 0, 5) : '';

                                            if ($existingStart === '' && !empty($entry->exam_time) && str_contains($entry->exam_time, '-')) {
                                                $parts = explode('-', $entry->exam_time);
                                                if (count($parts) === 2) {
                                                    try {
                                                        $existingStart = \Carbon\Carbon::parse(trim($parts[0]))->format('H:i');
                                                        $existingEnd = \Carbon\Carbon::parse(trim($parts[1]))->format('H:i');
                                                    } catch (\Throwable $e) {
                                                        $existingStart = '';
                                                        $existingEnd = '';
                                                    }
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td><input type="date" name="entry_date[]" class="form-control routine-grid-input" value="{{ !empty($entry->exam_date) ? \Carbon\Carbon::parse($entry->exam_date)->format('Y-m-d') : '' }}"></td>
                                            <td><input type="text" name="entry_day[]" class="form-control routine-grid-input" value="{{ $entry->exam_day }}" placeholder="Sunday" readonly></td>
                                            <td><input type="time" name="entry_start_time[]" class="form-control routine-grid-input" value="{{ $existingStart }}"></td>
                                            <td><input type="time" name="entry_end_time[]" class="form-control routine-grid-input" value="{{ $existingEnd }}"></td>
                                            <td>
                                                <select name="entry_subject_id[]" class="form-select subject-select" data-existing-subject-id="{{ $entry->subject_id ?? '' }}" data-existing-subject-name="{{ data_get($entry, 'subject.subjectName', '') }}">
                                                    <option value="">Select Subject</option>
                                                    @foreach($subjectList as $subject)
                                                        <option value="{{ $subject->id }}" {{ (string)($entry->subject_id ?? '') === (string)$subject->id ? 'selected' : '' }}>{{ $subject->subjectName }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeRoutineRow(this)">X</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="routine-help">Day is auto-filled from Date. Time range is generated from Start Time and End Time.</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success btn-lg mx-2" type="submit">Save</button>
                        <a class="btn btn-primary btn-lg mx-2" href="{{ route('resultExamRoutineManage') }}">Create New</a>
                    </div>
                </form>
            </div>

            <div class="card-header">Existing Result Exam Routine</div>
            <div class="card-body cultivation">
                <table id="myTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Title</th>
                            <th>Exam</th>
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
                                    <td>{{ optional($lookupExams->get($item->assignExam))->examName ?? '-' }}</td>
                                    <td>{{ optional($lookupClasses->get($item->assignClass))->className ?? '-' }}</td>
                                    <td>{{ optional($lookupSections->get($item->assignSection))->section ?? 'All' }}</td>
                                    <td>{{ optional($lookupDepartments->get($item->assignDepartment))->departmentName ?? 'All' }}</td>
                                    <td>{{ optional($lookupSessions->get($item->assignSession))->session ?? '-' }}</td>
                                    <td>{{ $item->entries_count ?? 0 }}</td>
                                    <td>
                                        <a href="{{ route('editResultExamRoutine',['id'=>$item->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                        <x-delete-action :action="route('delResultExamRoutine',['id'=>$item->id])"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></x-delete-action>
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

    function refreshSubjectDropdowns() {
        var classSelect = document.getElementById('routine-assign-class');
        var classId = classSelect && classSelect.value ? parseInt(classSelect.value, 10) : 0;
        var selects = Array.prototype.slice.call(document.querySelectorAll('select.subject-select'));

        function getEffectiveCurrentValue(sel) {
            if (sel.value) {
                return String(sel.value);
            }
            var existingId = sel.getAttribute('data-existing-subject-id') || '';
            return existingId ? String(existingId) : '';
        }

        var selectedMap = {};
        selects.forEach(function(sel){
            var currentValue = getEffectiveCurrentValue(sel);
            if (currentValue) {
                selectedMap[currentValue] = (selectedMap[currentValue] || 0) + 1;
            }
        });

        selects.forEach(function(sel){
            var current = getEffectiveCurrentValue(sel);
            var currentInt = current ? parseInt(current, 10) : null;
            var optionsHtml = '<option value="">Select Subject</option>';

            getOrderedSubjectList().forEach(function(subject){
                var idStr = String(subject.id);

                if (idStr !== current && !isSubjectAllowedForClass(subject, classId)) {
                    return;
                }

                var alreadyUsedElsewhere = !!selectedMap[idStr] && (!current || idStr !== current);
                if (alreadyUsedElsewhere) {
                    return;
                }

                var selectedAttr = (currentInt !== null && currentInt === subject.id) ? ' selected' : '';
                optionsHtml += '<option value="' + subject.id + '"' + selectedAttr + '>' + subject.name + '</option>';
            });

            sel.innerHTML = optionsHtml;

            if (current && !sel.querySelector('option[value="' + current + '"]')) {
                var existingName = sel.getAttribute('data-existing-subject-name') || 'Existing Subject';
                sel.innerHTML += '<option value="' + current + '" selected>' + existingName + ' (Existing)</option>';
            }

            if (current) {
                sel.value = current;
            }
        });
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

    function isDuplicateDate(dateValue, currentInput) {
        if (!dateValue) return false;
        var dateInputs = document.querySelectorAll('input[name="entry_date[]"]');
        for (var i = 0; i < dateInputs.length; i++) {
            var input = dateInputs[i];
            if (input === currentInput) continue;
            if (input.value === dateValue) {
                return true;
            }
        }
        return false;
    }

    function getDayName(dateString) {
        if (!dateString) return '';
        var date = new Date(dateString + 'T00:00:00');
        if (Number.isNaN(date.getTime())) return '';

        var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[date.getDay()] || '';
    }

    function attachDateDaySync(row) {
        var dateInput = row.querySelector('input[name="entry_date[]"]');
        var dayInput = row.querySelector('input[name="entry_day[]"]');
        if (!dateInput || !dayInput) return;

        dateInput.addEventListener('change', function() {
            if (isDuplicateDate(this.value, this)) {
                showValidationWarning('This date is already selected in another row. Please choose a different date.');
                this.value = '';
                dayInput.value = '';
                return;
            }

            var day = getDayName(this.value);
            if (day) {
                dayInput.value = day;
            } else {
                dayInput.value = '';
            }
        });
    }

    function addRoutineRow() {
        var body = document.getElementById('routine-row-body');
        if (!body) return;

        var row = document.createElement('tr');
        row.innerHTML = '<td><input type="date" name="entry_date[]" class="form-control routine-grid-input"></td>' +
            '<td><input type="text" name="entry_day[]" class="form-control routine-grid-input" placeholder="Sunday" readonly></td>' +
            '<td><input type="time" name="entry_start_time[]" class="form-control routine-grid-input"></td>' +
            '<td><input type="time" name="entry_end_time[]" class="form-control routine-grid-input"></td>' +
            '<td><select name="entry_subject_id[]" class="form-select subject-select" data-existing-subject-id="" data-existing-subject-name=""><option value="">Select Subject</option></select></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeRoutineRow(this)">X</button></td>';
        body.appendChild(row);
        attachDateDaySync(row);
        var subjectSelect = row.querySelector('select.subject-select');
        if (subjectSelect) {
            subjectSelect.addEventListener('change', function() {
                var selectedOption = this.options[this.selectedIndex];
                this.setAttribute('data-existing-subject-id', this.value || '');
                this.setAttribute('data-existing-subject-name', selectedOption ? selectedOption.text : '');
                refreshSubjectDropdowns();
            });
        }
        refreshSubjectDropdowns();
    }

    function removeRoutineRow(btn) {
        var body = document.getElementById('routine-row-body');
        if (!body) return;
        if (body.rows.length <= 1) return;

        var row = btn.closest('tr');
        if (row) row.remove();
        refreshSubjectDropdowns();
    }

    document.addEventListener('DOMContentLoaded', function() {
        var rows = document.querySelectorAll('#routine-row-body tr');
        rows.forEach(function(row) {
            attachDateDaySync(row);

            var dateInput = row.querySelector('input[name="entry_date[]"]');
            var dayInput = row.querySelector('input[name="entry_day[]"]');
            if (dateInput && dayInput && dateInput.value && !dayInput.value) {
                dayInput.value = getDayName(dateInput.value);
            }

            var subjectSelect = row.querySelector('select.subject-select');
            if (subjectSelect) {
                subjectSelect.addEventListener('change', function() {
                    var selectedOption = this.options[this.selectedIndex];
                    this.setAttribute('data-existing-subject-id', this.value || '');
                    this.setAttribute('data-existing-subject-name', selectedOption ? selectedOption.text : '');
                    refreshSubjectDropdowns();
                });
            }
        });

        var classSelect = document.getElementById('routine-assign-class');
        if (classSelect) {
            classSelect.addEventListener('change', function() {
                refreshSubjectDropdowns();
            });
        }

        refreshSubjectDropdowns();
    });
</script>
@endsection

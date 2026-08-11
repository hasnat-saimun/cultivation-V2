@extends('result.include')

@section('backTitle', 'Academic Attendance')

@section('backIndex')
<div class="card height-auto">
    <div class="card-body">
        <div class="heading-layout1"><div class="item-title"><h3>Academic Attendance</h3><p class="text-muted mb-0">Exam transcript attendance — independent from Daily Attendance.</p></div></div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

        <form method="GET" action="{{ route('academic-attendance.index') }}" class="row gutters-8 mb-4">
            @foreach([
                'exam_id' => ['Exam', $exams, 'examName'], 'session_id' => ['Session', $sessions, 'session'],
                'class_id' => ['Class', $classes, 'className'], 'section_id' => ['Section', $sections, 'section'],
                'department_id' => ['Department', $departments, 'departmentName'],
            ] as $name => [$label, $options, $field])
                <div class="col-lg-2 col-sm-4 form-group"><label>{{ $label }}{{ $name !== 'department_id' ? ' *' : '' }}</label>
                    <select name="{{ $name }}" class="form-control" {{ $name !== 'department_id' ? 'required' : '' }}>
                        <option value="">{{ $name === 'department_id' ? 'All Departments' : 'Select '.$label }}</option>
                        @foreach($options as $option)<option value="{{ $option->id }}" @selected((string)($scope[$name] ?? '') === (string)$option->id)>{{ $option->{$field} }}</option>@endforeach
                    </select>
                </div>
            @endforeach
            <div class="col-lg-2 col-sm-4 form-group"><label>Gender</label><select name="gender" class="form-control">
                @foreach($genderOptions as $value => $label)<option value="{{ $value }}" @selected(($scope['gender'] ?? 'all') === $value)>{{ $label }}</option>@endforeach
            </select></div>
            <div class="col-12"><button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Load Students</button> <a class="btn-fill-lg bg-blue-dark btn-hover-yellow" href="{{ route('academic-attendance.index') }}">Reset</a></div>
        </form>

        @if($students->isNotEmpty())
        <form method="POST" action="{{ route('academic-attendance.bulk.store') }}" id="academic-attendance-form">
            @csrf
            @foreach($scope as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
            <div class="row align-items-end mb-3">
                <div class="col-md-3 form-group mb-0"><label for="apply-working-days">Working Days</label><input id="apply-working-days" type="text" inputmode="numeric" pattern="[0-9]+" class="form-control" maxlength="3"></div>
                <div class="col-md-3"><button type="button" class="btn btn-secondary" id="apply-working-days-button">Apply to All</button></div>
                <div class="col-md-6 text-right"><button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Save All Attendance</button></div>
            </div>
            <div id="working-days-warning" class="alert alert-warning d-none"></div>
            <div class="table-responsive"><table id="academic-attendance-table" class="table display text-nowrap"><thead><tr><th>Roll</th><th>Student ID</th><th>Student Name</th><th>Working Days</th><th>Present</th><th>Absent</th><th>Single Update</th></tr></thead><tbody>
            @foreach($students as $student)
                @php($record = $records->get($student->id))
                <tr class="attendance-row"
                    data-student-id="{{ $student->id }}"
                    data-public-student-id="{{ $student->stdId }}"
                    data-gender-raw="{{ $student->getRawOriginal('gender') }}"
                    data-roll-raw="{{ $student->getRawOriginal('rollNumber') }}">
                    <td>{{ $student->rollNumber }}</td><td>{{ $student->stdId }}</td><td>{{ $student->student_name }}</td>
                    <td><input class="form-control attendance-working" type="text" inputmode="numeric" pattern="[0-9]+" name="students[{{ $loop->index }}][working_days]" value="{{ old('students.'.$loop->index.'.working_days', $record?->working_days) }}" required><input type="hidden" name="students[{{ $loop->index }}][student_id]" value="{{ $student->id }}"></td>
                    <td><input class="form-control attendance-present" type="text" inputmode="numeric" pattern="[0-9]+" name="students[{{ $loop->index }}][present_days]" value="{{ old('students.'.$loop->index.'.present_days', $record?->present_days) }}" required></td>
                    <td><input class="form-control attendance-absent" type="text" inputmode="numeric" pattern="[0-9]+" name="students[{{ $loop->index }}][absent_days]" value="{{ old('students.'.$loop->index.'.absent_days', $record?->absent_days) }}" required></td>
                    <td><button type="submit" name="single_student_id" value="{{ $student->id }}" formaction="{{ route('academic-attendance.single.store') }}" class="btn btn-sm btn-outline-primary">Save Student</button></td>
                </tr>
            @endforeach
            </tbody></table></div>
        </form>
        @elseif(collect(['exam_id','session_id','class_id','section_id'])->every(fn($key) => !empty($scope[$key])))
            <div class="alert alert-info">No students found for the selected academic scope.</div>
        @endif
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const integer = value => /^\d+$/.test(value);
    const tableElement = document.getElementById('academic-attendance-table');
    const attendanceState = new Map();
    let dataTable = null;

    function capture(row) {
        attendanceState.set(row.dataset.studentId, {
            working: row.querySelector('.attendance-working').value,
            present: row.querySelector('.attendance-present').value,
            absent: row.querySelector('.attendance-absent').value,
            warning: row.classList.contains('table-warning')
        });
    }

    function rehydrate(row) {
        const state = attendanceState.get(row.dataset.studentId);
        if (!state) return;
        row.querySelector('.attendance-working').value = state.working;
        row.querySelector('.attendance-present').value = state.present;
        row.querySelector('.attendance-absent').value = state.absent;
        row.classList.toggle('table-warning', state.warning);
    }

    const allRows = () => dataTable
        ? Array.from(dataTable.rows().nodes())
        : Array.from(tableElement?.querySelectorAll('.attendance-row') || []);

    function sync(row, edited) {
        const working = row.querySelector('.attendance-working');
        const present = row.querySelector('.attendance-present');
        const absent = row.querySelector('.attendance-absent');
        if (!integer(working.value) || Number(working.value) < 1) { capture(row); return; }
        const total = Number(working.value), source = edited === absent ? absent : present;
        if (!integer(source.value) || Number(source.value) > total) { row.classList.add('table-warning'); capture(row); return; }
        (edited === absent ? present : absent).value = total - Number(source.value);
        row.classList.remove('table-warning');
        capture(row);
    }

    allRows().forEach(capture);

    if (window.jQuery && jQuery.fn.DataTable && tableElement) {
        dataTable = jQuery(tableElement).DataTable({
            paging: true,
            searching: true,
            info: false,
            ordering: false,
            order: [],
            aaSorting: [],
            lengthChange: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100]
        });
        jQuery(tableElement).on('draw.dt', function () { allRows().forEach(rehydrate); });
    }

    tableElement?.addEventListener('input', function (event) {
        const row = event.target.closest('.attendance-row');
        if (!row) return;
        if (event.target.classList.contains('attendance-present') || event.target.classList.contains('attendance-absent')) sync(row, event.target);
        else capture(row);
    });

    document.getElementById('apply-working-days-button')?.addEventListener('click', function () {
        const input = document.getElementById('apply-working-days'), warning = document.getElementById('working-days-warning');
        if (!integer(input.value) || Number(input.value) < 1 || Number(input.value) > {{ $maxWorkingDays }}) { warning.textContent = 'Enter a valid positive Working Days value.'; warning.classList.remove('d-none'); return; }
        const total = Number(input.value); let conflicts = 0;
        allRows().forEach(row => {
            const working = row.querySelector('.attendance-working'), present = row.querySelector('.attendance-present'), absent = row.querySelector('.attendance-absent');
            if ((integer(present.value) && Number(present.value) > total) || (integer(absent.value) && Number(absent.value) > total)) { row.classList.add('table-warning'); conflicts++; capture(row); return; }
            working.value = total;
            if (integer(present.value)) absent.value = total - Number(present.value);
            else if (integer(absent.value)) present.value = total - Number(absent.value);
            row.classList.remove('table-warning');
            capture(row);
        });
        warning.textContent = conflicts ? `${conflicts} row(s) exceed the new Working Days. Correct them before saving.` : '';
        warning.classList.toggle('d-none', conflicts === 0);
    });

    document.getElementById('academic-attendance-form')?.addEventListener('submit', function () {
        if (dataTable) {
            allRows().forEach(rehydrate);
            dataTable.destroy();
            dataTable = null;
        }
    });
});
</script>
@endsection

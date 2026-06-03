@extends('admin-modern.layouts.app')

@section('title', 'Mark Attendance')

@section('content')
    <x-admin-modern.page-header
        title="Mark Attendance"
        subtitle="Modern parallel wrapper for attendance mark flow using existing ERP attendance data"
        :breadcrumb="['Home', 'Attendance', 'Mark Attendance']"
    />

    <div class="am-btn-row" style="justify-content:flex-start; margin-bottom:0.75rem; gap:0.5rem; flex-wrap:wrap;" aria-label="Attendance quick links">
        <a href="{{ route('adminModernAttendanceIndex') }}" class="am-btn-outline" title="Open mark attendance">Mark Attendance</a>
        <a href="{{ route('adminModernAttendanceReport') }}" class="am-btn-outline" title="Open attendance report">Attendance Report</a>
        <a href="{{ route('adminModernAttendanceMonthly') }}" class="am-btn-outline" title="Open monthly attendance">Monthly Sheet</a>
    </div>

    <x-admin-modern.table-shell title="Mark Attendance">
        <div class="am-flash is-info" style="margin-bottom:0.8rem;">
            <div>
                <strong>Note:</strong> Attendance should be taken for the Primary Class/Section assigned to a class-teacher. Other assignments (Admin Assign) are used for Marks Entry only.
            </div>
        </div>

        <form method="POST" action="{{ route('attendanceStore') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <input type="hidden" name="classId" value="{{ $classId }}">
            <input type="hidden" name="sessionId" value="{{ $sessionId }}">
            <input type="hidden" name="sectionId" value="{{ $sectionId }}">

            <div class="am-table-wrap">
                <table class="am-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Roll</th>
                            <th>Name</th>
                            <th class="text-center">Present?
                                <div>
                                    <input type="checkbox" id="toggle-all-present">
                                    <label for="toggle-all-present" class="mb-0 small">All</label>
                                </div>
                            </th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $i => $stu)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>{{ $stu->stdId }}</td>
                            <td>{{ $stu->rollNumber }}</td>
                            <td>{{ trim(($stu->fullName ?? '').' '.($stu->sureName ?? '')) }}</td>
                            <td>
                                @php $existingStatus = isset($existing[$stu->id]) ? $existing[$stu->id] : null; @endphp
                                <input type="hidden" name="studentId[]" value="{{ $stu->id }}">
                                <input type="checkbox" class="present-toggle" data-row="{{ $i }}" {{ ($existingStatus==='Present') ? 'checked' : '' }}>
                            </td>
                            <td>
                                <select name="status[]" class="form-select form-control">
                                    <option value="Present" {{ ($existingStatus==='Present') ? 'selected' : '' }}>Present</option>
                                    <option value="Absent" {{ ($existingStatus==='Absent') ? 'selected' : '' }}>Absent</option>
                                    <option value="Late" {{ ($existingStatus==='Late') ? 'selected' : '' }}>Late</option>
                                    <option value="Excused" {{ ($existingStatus==='Excused') ? 'selected' : '' }}>Excused</option>
                                </select>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center">No students found for the selection.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="am-btn-row" style="margin-top:0.9rem;">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Save Attendance</button>
                <a href="{{ route('adminModernAttendanceIndex') }}" class="am-btn-outline">Back</a>
            </div>
        </form>
    </x-admin-modern.table-shell>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const table = document.querySelector('table');
    const rowCheckboxes = Array.from(table.querySelectorAll('input.present-toggle'));
    const statusSelects = Array.from(table.querySelectorAll('select[name="status[]"]'));
    const toggleAll = document.getElementById('toggle-all-present');

    // Initial sync: if a select is Present, ensure its row checkbox is checked
    statusSelects.forEach((sel, idx) => {
        const isPresent = (sel.value === 'Present');
        if (rowCheckboxes[idx]) rowCheckboxes[idx].checked = isPresent;
    });

    // Row checkbox -> select sync
    rowCheckboxes.forEach((cb, idx) => {
        cb.addEventListener('change', () => {
            const sel = statusSelects[idx];
            if (!sel) return;
            if (cb.checked) {
                sel.value = 'Present';
            } else {
                // If it was Present and unchecked now, default to Absent for quick calling
                if (sel.value === 'Present') sel.value = 'Absent';
            }
        });
    });

    // Select -> checkbox sync (uncheck if not Present)
    statusSelects.forEach((sel, idx) => {
        sel.addEventListener('change', () => {
            const cb = rowCheckboxes[idx];
            if (!cb) return;
            cb.checked = (sel.value === 'Present');
        });
    });

    // Toggle all
    if (toggleAll) {
        toggleAll.addEventListener('change', () => {
            const makePresent = toggleAll.checked;
            rowCheckboxes.forEach((cb, idx) => {
                cb.checked = makePresent;
                const sel = statusSelects[idx];
                if (sel) sel.value = makePresent ? 'Present' : 'Absent';
            });
        });
    }
});
</script>
@endpush
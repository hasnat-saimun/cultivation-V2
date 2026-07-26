@extends('admin-modern.layouts.app')

@section('title', 'Student List')

@section('content')
    <x-admin-modern.page-header
        title="Student List"
        subtitle="Modern parallel student management list using existing ERP student data"
        :breadcrumb="['Home', 'Student List']"
    />

    <x-admin-modern.table-shell title="All Students">
        @php
            $exportParams = array_filter([
                'classId' => request()->get('classId'),
                'sessionId' => request()->get('sessionId'),
                'sectionId' => request()->get('sectionId'),
                'departmentId' => request()->get('departmentId'),
                'gender' => request()->get('gender'),
                'search' => request()->get('search'),
            ], function ($v) { return $v !== null && $v !== ''; });
        @endphp
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('student.export.pdf', $exportParams) }}" class="am-btn-outline">Export PDF</a>
            <a href="{{ route('student.export.excel', $exportParams) }}" class="am-btn-outline">Export Excel</a>
            <a href="{{ route('studentBulkUpdate') }}" class="am-btn-outline">Bulk Update</a>
            <a href="{{ route('admitStudent') }}" class="am-btn-primary">New Admission</a>
        </div>

        <form method="GET" class="am-btn-row" style="margin-bottom: 0.9rem; gap: 0.6rem; align-items: flex-end;">
            <div>
                <label style="display:block; margin-bottom:0.3rem; font-size:0.85rem;">Class</label>
                <select name="classId" class="form-control">
                    <option value="">All</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request()->get('classId') == $c->id ? 'selected' : '' }}>{{ $c->className }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display:block; margin-bottom:0.3rem; font-size:0.85rem;">Session</label>
                <select name="sessionId" class="form-control">
                    <option value="">All</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ request()->get('sessionId') == $s->id ? 'selected' : '' }}>{{ $s->session }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display:block; margin-bottom:0.3rem; font-size:0.85rem;">Section</label>
                <select name="sectionId" class="form-control">
                    <option value="">All</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec->id }}" {{ request()->get('sectionId') == $sec->id ? 'selected' : '' }}>{{ $sec->section }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display:block; margin-bottom:0.3rem; font-size:0.85rem;">Department</label>
                <select name="departmentId" class="form-control">
                    <option value="">All</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ request()->get('departmentId') == $d->id ? 'selected' : '' }}>{{ $d->departmentName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display:block; margin-bottom:0.3rem; font-size:0.85rem;">Gender</label>
                <select name="gender" class="form-control">
                    <option value="">All</option>
                    @foreach(($genderOptions ?? []) as $genderValue => $genderLabel)
                        <option value="{{ $genderValue }}" {{ request()->get('gender') === (string) $genderValue ? 'selected' : '' }}>{{ $genderLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display:block; margin-bottom:0.3rem; font-size:0.85rem;">Search</label>
                <input
                    name="search"
                    type="text"
                    class="form-control"
                    value="{{ request()->get('search') }}"
                    placeholder="Name / Student ID / Phone"
                >
            </div>

            <div class="am-btn-row" style="margin-bottom: 0; gap: 0.5rem;">
                <button type="submit" class="am-btn-primary">Filter</button>
                <a href="{{ route('adminModernStudentsIndex') }}" class="am-btn-outline">Reset</a>
            </div>
        </form>

        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <button type="button" class="am-btn-outline" id="bulkDeleteBtn" style="display:none;">Delete Selected (<span id="selectedCount">0</span>)</button>
        </div>

        <form id="bulkDeleteForm" method="POST" action="{{ route('studentBulkDelete') }}" style="display:none;">
            @csrf
            @method('POST')
            <input type="hidden" name="ids" id="deleteIds">
        </form>

        <table class="am-table">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">
                        <input type="checkbox" id="selectAll">
                    </th>
                    <th>Student ID</th>
                    <th>Roll</th>
                    <th>Name</th>
                    <th>Session</th>
                    <th>Class</th>
                    <th>Department</th>
                    <th>Section</th>
                    <th>Mobile</th>
                    <th style="text-align:center;">ID Card</th>
                    <th style="text-align:center;">Testimonial</th>
                    <th style="text-align:center;">TC</th>
                    <th style="width:150px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studentData as $std)
                    @php
                        $classNameText = optional($std->classInfo)->className;
                        $existingTestimonialId = $latestTestimonialIds[$std->id] ?? null;
                        $existingTcId = $latestTransferCertificateIds[$std->id] ?? null;
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="row-checkbox" value="{{ $std->id }}">
                        </td>
                        <td>{{ $std->stdId }}</td>
                        <td>{{ !empty($std->rollNumber) ? $std->rollNumber : '-' }}</td>
                        <td>{{ $std->student_name }}</td>
                        <td>{{ optional($std->sessionInfo)->session ?? '-' }}</td>
                        <td>{{ optional($std->classInfo)->className ?? '-' }}</td>
                        <td>{{ optional($std->departmentInfo)->departmentName ?? '-' }}</td>
                        <td>{{ optional($std->sectionInfo)->section ?? '-' }}</td>
                        <td>{{ $std->phone }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('stdIdCard', ['stdId' => $std->id]) }}" class="am-action-btn" style="display:inline-flex;">ID</a>
                        </td>

                        @php
                            $eligible = false;
                            if (!empty($classNameText)) {
                                $cn = strtolower(trim($classNameText));
                                $eligible = (
                                    $cn === 'five' || $cn === 'ten' || $cn === 'twelve' ||
                                    $cn === '5' || $cn === '10' || $cn === '12' ||
                                    strpos($cn, 'five') !== false || strpos($cn, 'ten') !== false || strpos($cn, 'twelve') !== false
                                );
                            }
                        @endphp
                        <td style="text-align:center; vertical-align:middle;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:0.35rem;">
                                @if($eligible)
                                    @if($existingTestimonialId)
                                        <span class="am-badge super" title="Testimonial already created">Created</span>
                                        <div class="am-action-group" style="justify-content:center;" aria-label="Testimonial actions">
                                            <a href="{{ route('testimonials.show', $existingTestimonialId) }}" class="am-action-btn" style="display:inline-flex;" title="View testimonial">View</a>
                                            <a href="{{ route('testimonials.print', $existingTestimonialId) }}" class="am-action-btn" target="_blank" style="display:inline-flex;" title="Print testimonial">Print</a>
                                        </div>
                                    @else
                                        <span class="am-badge cash" title="Testimonial not created yet">Not Created</span>
                                        <div class="am-action-group" style="justify-content:center;" aria-label="Testimonial actions">
                                            <a href="{{ route('testimonials.create', ['admission' => $std->id]) }}" class="am-action-btn" style="display:inline-flex;" title="Create testimonial">Create</a>
                                        </div>
                                    @endif
                                @else
                                    <span class="am-badge teacher" title="Testimonial is available only for Class Five, Ten, and Twelve">Not Eligible</span>
                                    <span style="font-size:0.8rem; color:#6b7280;">Class Five/Ten/Twelve only</span>
                                @endif
                            </div>
                        </td>

                        <td style="text-align:center; vertical-align:middle;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:0.35rem;">
                                @if($existingTcId)
                                    <span class="am-badge super" title="Transfer certificate already created">Created</span>
                                    <div class="am-action-group" style="justify-content:center;" aria-label="Transfer certificate actions">
                                        <a href="{{ route('tc.show', $existingTcId) }}" class="am-action-btn" style="display:inline-flex;" title="View transfer certificate">View</a>
                                        <a href="{{ route('tc.print', $existingTcId) }}" class="am-action-btn" target="_blank" style="display:inline-flex;" title="Print transfer certificate">Print</a>
                                    </div>
                                @else
                                    <span class="am-badge cash" title="Transfer certificate not created yet">Not Created</span>
                                    <div class="am-action-group" style="justify-content:center;" aria-label="Transfer certificate actions">
                                        <a href="{{ route('tc.create', ['admission' => $std->id]) }}" class="am-action-btn" style="display:inline-flex;" title="Create transfer certificate">Create</a>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <td style="text-align:center; vertical-align:middle;">
                            <div class="am-action-group" style="justify-content:center; gap:0.35rem; flex-wrap:wrap;" aria-label="Student row actions">
                                <a href="{{ route('viewAdmission', ['stdId' => $std->id]) }}" class="am-action-btn is-view" style="display:inline-flex;" title="View student profile">View</a>
                                <a href="{{ route('editStudent', ['stdId' => $std->id]) }}" class="am-action-btn is-edit" style="display:inline-flex;" title="Edit student profile">Edit</a>
                                <a href="#" class="am-action-btn is-delete delete-single" style="display:inline-flex;" data-id="{{ $std->id }}" data-name="{{ $std->fullName }}" title="Delete this student">Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center">No students found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');
    const deleteForm = document.getElementById('bulkDeleteForm');
    const deleteIds = document.getElementById('deleteIds');

    function updateDeleteButton() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        selectedCount.textContent = checkedCount;
        bulkDeleteBtn.style.display = checkedCount > 0 ? 'inline-flex' : 'none';
    }

    function updateSelectAll() {
        const totalCheckboxes = rowCheckboxes.length;
        const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked').length;
        selectAllCheckbox.checked = totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes;
    }

    selectAllCheckbox?.addEventListener('change', function () {
        rowCheckboxes.forEach(function (checkbox) {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateDeleteButton();
    });

    rowCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            updateDeleteButton();
            updateSelectAll();
        });
    });

    bulkDeleteBtn?.addEventListener('click', function () {
        const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(function (cb) {
            return cb.value;
        });

        if (selected.length === 0) {
            alert('Please select at least one record');
            return;
        }

        if (confirm('Are you sure you want to delete ' + selected.length + ' record(s)? This action cannot be undone.')) {
            deleteIds.value = JSON.stringify(selected);
            deleteForm.submit();
        }
    });

    document.querySelectorAll('.delete-single').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');

            if (confirm('Delete ' + name + '? This action cannot be undone.')) {
                deleteIds.value = JSON.stringify([id]);
                deleteForm.submit();
            }
        });
    });
});
</script>
@endpush

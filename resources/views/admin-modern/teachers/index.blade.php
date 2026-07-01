@extends('admin-modern.layouts.app')

@section('title', 'Teacher List')

@section('content')
    <x-admin-modern.page-header
        title="Teacher List"
        subtitle="Modern parallel teacher management list using existing ERP teacher data"
        :breadcrumb="['Home', 'Teacher List']"
    />

    <x-admin-modern.table-shell title="All Teachers">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('teacher.export.pdf') }}" class="am-btn-outline">Export PDF</a>
            <a href="{{ route('teacherBulkUpdate') }}" class="am-btn-outline">Bulk Update</a>
            <a href="{{ route('teacherBulkUpload') }}" class="am-btn-outline">Bulk Upload</a>
            <a href="{{ route('addTeacher') }}" class="am-btn-primary">Add Teacher</a>
        </div>

        <form method="GET" class="am-btn-row" style="margin-bottom: 0.9rem; gap: 0.6rem; align-items: flex-end;">
            <div>
                <label style="display:block; margin-bottom:0.3rem; font-size:0.85rem;">Search</label>
                <input
                    name="search"
                    type="text"
                    class="form-control"
                    value="{{ request()->query('search') }}"
                    placeholder="Name / Teacher ID / Mobile / Email"
                >
            </div>

            <div class="am-btn-row" style="margin-bottom: 0; gap: 0.5rem;">
                <button type="submit" class="am-btn-primary">Filter</button>
                <a href="{{ route('adminModernTeachersIndex') }}" class="am-btn-outline">Reset</a>
            </div>
        </form>

        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <button type="button" class="am-btn-outline" id="bulkDeleteBtn" style="display:none;">Delete Selected (<span id="selectedCount">0</span>)</button>
        </div>

        <form id="bulkDeleteForm" method="POST" action="{{ route('teacherBulkDelete') }}" style="display:none;">
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
                    <th>Teacher ID</th>
                    <th>Name</th>
                    <th>Join Date</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th style="width:120px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($profileData as $teacher)
                    <tr>
                        <td>
                            <input type="checkbox" class="row-checkbox" value="{{ $teacher->id }}">
                        </td>
                        <td>{{ $teacher->teacherId }}</td>
                        <td>{{ $teacher->firstName . ' ' . $teacher->lastName }}</td>
                        <td>{{ $teacher->joinDate }}</td>
                        <td>{{ $teacher->email }}</td>
                        <td>{{ $teacher->mobile }}</td>
                        <td>
                            <div class="am-action-group">
                                <a href="{{ route('viewTeacher', ['profileId' => $teacher->id]) }}" class="am-action-btn is-view">View</a>
                                <a href="{{ route('editTeacher', ['profileId' => $teacher->id]) }}" class="am-action-btn is-edit">Edit</a>
                                <a href="#" class="am-action-btn is-delete delete-single" data-id="{{ $teacher->id }}" data-name="{{ $teacher->firstName }} {{ $teacher->lastName }}">Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No teachers found.</td>
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

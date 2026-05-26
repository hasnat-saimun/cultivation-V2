@extends('admin-modern.layouts.app')

@section('title', 'Staff List')

@section('content')
    <x-admin-modern.page-header
        title="Staff List"
        subtitle="Modern parallel staff management list using existing ERP staff data"
        :breadcrumb="['Home', 'Staff List']"
    />

    <x-admin-modern.table-shell title="All Staff Members">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('staff.export.pdf') }}" class="am-btn-outline">Export PDF</a>
            <a href="{{ route('staffBulkUpdate') }}" class="am-btn-outline">Bulk Update</a>
            <a href="{{ route('staffBulkUpload') }}" class="am-btn-outline">Bulk Upload</a>
            <a href="{{ route('addStaff') }}" class="am-btn-primary">Add Staff</a>
        </div>

        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <button type="button" class="am-btn-outline" id="bulkDeleteBtn" style="display:none;">Delete Selected (<span id="selectedCount">0</span>)</button>
        </div>

        <form id="bulkDeleteForm" method="POST" action="{{ route('staffBulkDelete') }}" style="display:none;">
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
                    <th>Staff ID</th>
                    <th>Name</th>
                    <th>Join Date</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th style="width:120px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($profileData as $staff)
                    <tr>
                        <td>
                            <input type="checkbox" class="row-checkbox" value="{{ $staff->id }}">
                        </td>
                        <td>{{ $staff->staffId }}</td>
                        <td>{{ $staff->firstName }}</td>
                        <td>{{ $staff->joinDate }}</td>
                        <td>{{ $staff->email }}</td>
                        <td>{{ $staff->mobile }}</td>
                        <td>
                            <div class="am-action-group">
                                <a href="{{ route('viewStaff', ['profileId' => $staff->id]) }}" class="am-action-btn is-view">View</a>
                                <a href="{{ route('editStaff', ['profileId' => $staff->id]) }}" class="am-action-btn is-edit">Edit</a>
                                <a href="#" class="am-action-btn is-delete delete-single" data-id="{{ $staff->id }}" data-name="{{ $staff->firstName }}">Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No staff found.</td>
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

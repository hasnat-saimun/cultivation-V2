@extends('cultivation.include')
@section('backTitle')
Teacher List
@endsection
@section('backIndex')
                <!-- Social Media Start Here -->
                <div class="row gutters-20 mt-4">
                    <div class="col-12 col-md-10 mx-auto">
                        <div class="card card-default shadow-sm">
                            <div class="card-header bg-gradient-primary text-white">
                                <h3 class="mb-0"><i class="fa-solid fa-chalkboard-user"></i> All Teachers</h3>
                                <div class="d-flex gap-3 flex-wrap" style="margin-top: 0.5rem;">
                                    <a href="{{ route('teacher.export.pdf') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-file-pdf"></i> Export PDF</a>
                                    <a href="{{ route('teacherBulkUpdate') }}" class="btn btn-warning btn-sm"><i class="fa-solid fa-edit"></i> Bulk Update</a>
                                    <a href="{{ route('teacherBulkUpload') }}" class="btn btn-info btn-sm"><i class="fa-solid fa-upload"></i> Bulk Upload</a>
                                    <a href="{{ route('addTeacher') }}" class="btn btn-success btn-sm"><i class="fa-solid fa-plus"></i> Add Teacher</a>
                                </div>
                            </div>
                            <div class="card-body">
                                @if(session()->has('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fa-solid fa-check-circle me-2"></i>
                                        <strong>Success!</strong> {{ session()->get('success') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif
                                @if(session()->has('error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fa-solid fa-exclamation-circle me-2"></i>
                                        <strong>Error!</strong> {{ session()->get('error') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif
                                <div class="mb-3 d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display:none;">
                                        <i class="fa-solid fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                                    </button>
                                    <span id="selectionInfo" class="badge bg-info" style="display:none;"></span>
                                </div>
                                <form id="bulkDeleteForm" method="POST" action="{{ route('teacherBulkDelete') }}" style="display:none;">
                                    @csrf
                                    @method('POST')
                                    <input type="hidden" name="ids" id="deleteIds">
                                </form>
                                <table id="myTable" class="table table-hover table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 40px;" class="text-center">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>Teacher ID</th>
                                            <th>Name</th>
                                            <th>Join Date</th>
                                            <th>Email</th>
                                            <th>Mobile</th>
                                            <th style="width: 120px;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($profileData))
                                        @foreach($profileData as $teacher)
                                            <tr class="data-row">
                                                <td>
                                                    <input type="checkbox" class="row-checkbox form-check-input" value="{{ $teacher->id }}">
                                                </td>
                                                <td>{{ $teacher->teacherId }}</td>
                                                <td>{{ $teacher->firstName." ".$teacher->lastName }}</td>
                                                <td>{{ $teacher->joinDate }}</td>
                                                <td>{{ $teacher->email }}</td>
                                                <td>{{ $teacher->mobile }}</td>
                                                <td class="text-center">
                                                    <a href="{{ route('viewTeacher',  ['profileId'=>$teacher->id]) }}" class="btn btn-sm btn-info" title="View"><i class="fa-solid fa-eye"></i></a>
                                                    <a href="{{ route('editTeacher',['profileId'=>$teacher->id]) }}" class="btn btn-sm btn-primary" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                                    <a href="#" class="btn btn-sm btn-danger delete-single" data-id="{{ $teacher->id }}" data-name="{{ $teacher->firstName }} {{ $teacher->lastName }}" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @else
                                            <tr>
                                                <td>SBC02</td>
                                                <td>Rasek Khondokar</td>
                                                <td>2023-2024</td>
                                                <td>Science</td>
                                                <td>01234567890</td>
                                                <td>Edit</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Upload Modal removed - using dedicated page instead -->
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');
    const deleteForm = document.getElementById('bulkDeleteForm');
    const deleteIds = document.getElementById('deleteIds');

    // Select All functionality
    selectAllCheckbox?.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButton();
    });

    // Individual row checkbox
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateDeleteButton();
            updateSelectAll();
        });
    });

    // Update delete button visibility and count
    function updateDeleteButton() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        selectedCount.textContent = checkedCount;
        
        if (checkedCount > 0) {
            bulkDeleteBtn.style.display = 'inline-block';
        } else {
            bulkDeleteBtn.style.display = 'none';
        }
    }

    // Update select all checkbox state
    function updateSelectAll() {
        const totalCheckboxes = rowCheckboxes.length;
        const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked').length;
        selectAllCheckbox.checked = totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes;
    }

    // Bulk delete button click
    bulkDeleteBtn?.addEventListener('click', function() {
        const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
        
        if (selected.length === 0) {
            alert('Please select at least one record');
            return;
        }
        
        if (confirm('Are you sure you want to delete ' + selected.length + ' record(s)? This action cannot be undone.')) {
            deleteIds.value = JSON.stringify(selected);
            deleteForm.submit();
        }
    });

    // Single delete
    document.querySelectorAll('.delete-single').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            if (confirm('Delete ' + name + '? This action cannot be undone.')) {
                deleteIds.value = JSON.stringify([id]);
                deleteForm.submit();
            }
        });
    });

    // Auto-dismiss alerts after 4 seconds
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 4000);
});
</script>
@endpush

@push('styles')
<style>
/* Color Scheme */
:root {
    --primary-color: #667eea;
    --primary-dark: #764ba2;
    --text-primary: #2c3e50;
    --text-secondary: #7f8c8d;
    --text-muted: #95a5a6;
    --border-color: #ecf0f1;
    --bg-light: #f8f9fa;
}

/* Card Styling */
.card {
    border: none;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.card-header {
    padding: 1.5rem 2rem;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1.5rem 2rem;
    row-gap: 1rem;
}

.card-header h3 {
    font-weight: 700;
    font-size: 1.5rem;
    letter-spacing: 0.3px;
    line-height: 1.4;
    margin: 0;
    color: #ffffff;
}

.card-body {
    padding: 2rem;
}

/* Gradient Backgrounds */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Buttons */
.btn {
    padding: 0.6rem 1.3rem;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    letter-spacing: 0.3px;
    font-size: 0.95rem;
    text-transform: uppercase;
}

.btn-sm {
    padding: 0.5rem 1.1rem;
    font-size: 0.85rem;
    font-weight: 600;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.btn-sm:last-child {
    margin-right: 0;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-light {
    color: var(--text-primary);
    background-color: #ffffff;
    border: 1px solid #ffffff;
}

.btn-light:hover {
    background-color: #f0f0f0;
    border-color: #f0f0f0;
    color: var(--text-primary);
}

/* Alerts */
.alert {
    padding: 1.2rem 1.5rem;
    border: none;
    border-left: 4px solid;
    border-radius: 0.4rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    line-height: 1.6;
}

.alert-success {
    border-color: #27ae60;
    background-color: #f0fdf4;
    color: #1e7e34;
}

.alert-danger {
    border-color: #e74c3c;
    background-color: #fef2f2;
    color: #c0392b;
}

.alert strong {
    font-weight: 700;
    letter-spacing: 0.2px;
}

.alert i {
    margin-right: 0.5rem;
}

/* Tables */
.table {
    margin-bottom: 0;
    line-height: 1.6;
}

.table thead.table-dark {
    background-color: #1f3047 !important;
    color: #ffffff !important;
    font-weight: 700;
    letter-spacing: 0.45px;
}

.table thead th {
    padding: 1.2rem;
    vertical-align: middle;
    font-size: 0.95rem;
    border-color: #1b2739;
    color: #ffffff !important;
    font-weight: 800;
    letter-spacing: 0.45px;
    text-transform: capitalize;
    text-shadow: 0 1px 2px rgba(0,0,0,0.35);
}

/* Extra specificity to override DataTables/Bootstrap defaults */
table.dataTable thead th {
    color: #ffffff !important;
    background-color: #1f3047 !important;
}

/* DataTables sort icon color */
table.dataTable > thead .sorting:before,
table.dataTable > thead .sorting:after,
table.dataTable > thead .sorting_asc:before,
table.dataTable > thead .sorting_asc:after,
table.dataTable > thead .sorting_desc:before,
table.dataTable > thead .sorting_desc:after {
    color: #ffffff !important;
}

.table tbody td {
    padding: 1rem 1.2rem;
    vertical-align: middle;
    color: var(--text-primary);
    font-size: 0.95rem;
    line-height: 1.5;
    border-color: var(--border-color);
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table-hover tbody tr:hover {
    background-color: #f0f4ff;
    transform: scale(1.01);
}

/* Text Styling */
body {
    color: var(--text-primary);
    line-height: 1.6;
    letter-spacing: 0.2px;
    font-size: 0.95rem;
}

h1, h2, h3, h4, h5, h6 {
    color: var(--text-primary);
    font-weight: 700;
    letter-spacing: 0.3px;
    line-height: 1.4;
}

/* Badge */
.badge {
    padding: 0.5rem 1rem;
    font-weight: 600;
    letter-spacing: 0.2px;
    font-size: 0.85rem;
    border-radius: 0.3rem;
}

/* Bulk Action Area */
#bulkDeleteBtn {
    margin-bottom: 0;
    padding: 0.6rem 1.5rem;
}

/* Form Elements */
.form-check-input {
    margin-top: 0.35rem;
}

/* Spacing Utilities */
.shadow-sm {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
</style>
@endpush

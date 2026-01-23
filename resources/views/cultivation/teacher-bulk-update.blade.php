@extends('cultivation.include')
@section('backTitle')
Bulk Teacher Profile Update
@endsection
@section('backIndex')
<div class="row gutters-20 mt-4">
    <div class="col-12">
        <div class="card card-default">
            <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="fa-solid fa-edit"></i> Teacher Bulk Details Update</h3>
                <a href="{{ route('teacherList') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-exclamation-circle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Validation Error!</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="alert alert-info d-flex align-items-center">
                    <i class="fa-solid fa-info-circle me-2 fs-4"></i>
                    <div>
                        <strong>Instructions:</strong> Edit any field directly in the table below. Click "Save All Changes" when done.
                        <span class="badge bg-primary ms-2 text-white">{{ $teachers->count() }} Teachers</span>
                    </div>
                </div>

                @if($teachers->count() > 0)
                    <form method="post" action="{{ route('teacherBulkUpdateStore') }}" id="bulkUpdateForm">
                        @csrf
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-bordered table-hover editable-table" id="teacherTable">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th style="width: 100px;">Teacher ID</th>
                                        <th style="width: 150px;">First Name</th>
                                        <th style="width: 150px;">Last Name</th>
                                        <th style="width: 180px;">Designation</th>
                                        <th style="width: 200px;">Email</th>
                                        <th style="width: 130px;">Mobile</th>
                                        <th style="width: 100px;">Gender</th>
                                        <th style="width: 130px;">Date of Birth</th>
                                        <th style="width: 130px;">Join Date</th>
                                        <th style="width: 250px;">Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($teachers as $index => $teacher)
                                        <tr data-index="{{ $index }}">
                                            <td>
                                                <input type="hidden" name="teachers[{{ $index }}][id]" value="{{ $teacher->id }}">
                                                <span class="text-muted">{{ $teacher->teacherId }}</span>
                                            </td>
                                            <td>
                                                <input type="text" 
                                                    name="teachers[{{ $index }}][firstName]" 
                                                    value="{{ $teacher->firstName }}" 
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="First Name">
                                            </td>
                                            <td>
                                                <input type="text" 
                                                    name="teachers[{{ $index }}][lastName]" 
                                                    value="{{ $teacher->lastName }}" 
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="Last Name">
                                            </td>
                                            <td>
                                                <select name="teachers[{{ $index }}][designation]" class="form-select form-select-sm editable-input">
                                                    <option value="">-- Select --</option>
                                                    @foreach($designations as $desig)
                                                        <option value="{{ $desig->id }}" {{ $teacher->designation_id == $desig->id ? 'selected' : '' }}>
                                                            {{ $desig->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="email" 
                                                    name="teachers[{{ $index }}][email]" 
                                                    value="{{ $teacher->email }}" 
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="email@example.com">
                                            </td>
                                            <td>
                                                <input type="text" 
                                                    name="teachers[{{ $index }}][mobile]" 
                                                    value="{{ $teacher->mobile }}" 
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="Mobile">
                                            </td>
                                            <td>
                                                <select name="teachers[{{ $index }}][gender]" class="form-select form-select-sm editable-input">
                                                    <option value="">--</option>
                                                    <option value="Male" {{ $teacher->gender == 'Male' ? 'selected' : '' }}>Male</option>
                                                    <option value="Female" {{ $teacher->gender == 'Female' ? 'selected' : '' }}>Female</option>
                                                    <option value="Others" {{ $teacher->gender == 'Others' ? 'selected' : '' }}>Others</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="date" 
                                                    name="teachers[{{ $index }}][dob]" 
                                                    value="{{ $teacher->dob }}" 
                                                    class="form-control form-control-sm editable-input">
                                            </td>
                                            <td>
                                                <input type="date" 
                                                    name="teachers[{{ $index }}][joinDate]" 
                                                    value="{{ $teacher->joinDate }}" 
                                                    class="form-control form-control-sm editable-input">
                                            </td>
                                            <td>
                                                <textarea 
                                                    name="teachers[{{ $index }}][address]" 
                                                    class="form-control form-control-sm editable-input" 
                                                    rows="1"
                                                    placeholder="Address">{{ $teacher->address }}</textarea>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="action-buttons d-flex gap-3 justify-content-end mt-5 pt-4 border-top">
                            <a href="{{ route('teacherList') }}" class="btn btn-outline-secondary btn-md" id="cancelBtn">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-md" id="saveBtn">
                                <i class="fa-solid fa-check"></i> Save All Changes
                            </button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-exclamation-triangle"></i> No teachers found.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .editable-table {
        font-size: 0.9rem;
    }
    .editable-input {
        border: 2px solid #e0e0e0;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        background-color: #fafbfc;
    }
    .editable-input:hover {
        border-color: #c0c0c0;
        background-color: #ffffff;
    }
    .editable-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.3rem rgba(102, 126, 234, 0.15);
        outline: none;
    }
    .editable-input.border-warning {
        border-color: #ffc107;
        background-color: #fffef0;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.15);
    }
    .editable-table tbody tr:hover {
        background-color: #f0f4ff;
    }
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    textarea.editable-input {
        resize: vertical;
        min-height: 38px;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let hasChanges = false;
        const inputs = document.querySelectorAll('.editable-input');
        
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                hasChanges = true;
                this.classList.add('border-warning');
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        document.getElementById('bulkUpdateForm').addEventListener('submit', function() {
            hasChanges = false;
        });

        document.querySelectorAll('.alert-success').forEach(alert => {
            setTimeout(() => {
                new bootstrap.Alert(alert).close();
            }, 4000);
        });

        document.querySelectorAll('textarea.editable-input').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    });
</script>
<style>
/* Header & Title Styling */
.card-header {
    padding: 1.5rem 2rem;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.card-header h3 {
    font-weight: 700;
    font-size: 1.5rem;
    letter-spacing: 0.3px;
    line-height: 1.4;
    margin: 0;
    color: #ffffff;
    text-decoration: none;
}

.card-body {
    padding: 2rem;
}

/* Background Gradients */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Buttons */
.btn {
    padding: 0.65rem 1.5rem;
    font-weight: 700;
    border-radius: 0.5rem;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    letter-spacing: 0.4px;
    font-size: 0.9rem;
    text-transform: uppercase;
    border: 2px solid transparent;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    text-decoration: none;
    cursor: pointer;
}

.btn:focus {
    outline: none;
    text-decoration: none;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
}

.btn-md {
    padding: 0.7rem 1.8rem;
    font-size: 0.95rem;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-success {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    border-color: #27ae60;
    color: #ffffff;
    text-decoration: none;
}

.btn-success:hover {
    background: linear-gradient(135deg, #229954 0%, #1e8449 100%);
    border-color: #229954;
    color: #ffffff;
    text-decoration: none;
}

.btn-outline-secondary {
    background-color: transparent;
    color: #495057;
    border-color: #495057;
    font-weight: 700;
    text-decoration: none;
}

.btn-outline-secondary:hover {
    background-color: #495057;
    color: #fff;
    border-color: #495057;
    text-decoration: none;
}

.btn-light {
    color: #2c3e50;
    background-color: #ffffff;
    border: 2px solid #e0e0e0;
}

.btn-light:hover {
    background-color: #f5f5f5;
    border-color: #b0b0b0;
    color: #2c3e50;
}

/* Action Buttons */
.action-buttons {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.4rem;
    gap: 1.5rem !important;
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
}

.action-buttons .btn {
    min-width: auto;
    white-space: nowrap;
    text-decoration: none;
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

.alert-info {
    border-color: #3498db;
    background-color: #f0f8ff;
    color: #1e5a96;
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
    background-color: #2c3e50;
    color: #ffffff;
    font-weight: 600;
    letter-spacing: 0.4px;
}

.table thead th {
    padding: 1.2rem;
    vertical-align: middle;
    font-size: 0.95rem;
    border-color: #34495e;
}

.table tbody td {
    padding: 1rem 1.2rem;
    vertical-align: middle;
    color: #2c3e50;
    font-size: 0.95rem;
    line-height: 1.5;
    border-color: #ecf0f1;
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table-hover tbody tr:hover {
    background-color: #f0f4ff;
}

/* Form Inputs - Enhanced */
.editable-input {
    padding: 0.65rem 0.95rem;
    font-size: 0.9rem;
    line-height: 1.5;
    border: 2px solid #e0e0e0;
    border-radius: 0.35rem;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    background-color: #fafbfc;
}

.editable-input:hover {
    border-color: #c0c0c0;
    background-color: #ffffff;
}

.editable-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.3rem rgba(102, 126, 234, 0.15);
    background-color: #ffffff;
    outline: none;
}

.editable-input.border-warning {
    border-color: #ffc107;
    background-color: #fffef0;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.15);
}

/* Badges */
.badge {
    padding: 0.5rem 1rem;
    font-weight: 600;
    letter-spacing: 0.2px;
    font-size: 0.85rem;
    border-radius: 0.3rem;
}

/* Text Styling */
body {
    color: #2c3e50;
    line-height: 1.6;
    letter-spacing: 0.2px;
    font-size: 0.95rem;
}

h1, h2, h3, h4, h5, h6 {
    color: #2c3e50;
    font-weight: 700;
    letter-spacing: 0.3px;
    line-height: 1.4;
    margin-bottom: 1rem;
}

/* Spacing */
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Card */
.card {
    border: none;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
</style>
@endpush
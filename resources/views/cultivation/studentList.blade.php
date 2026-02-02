@extends('cultivation.include')
@section('backTitle')
Student List
@endsection
@section('backIndex')
                <!-- Social Media Start Here -->
                <div class="row gutters-20 mt-4">
                    <div class="col-12 col-md-12 mx-auto">
                        <div class="card card-default shadow-sm">
                            <div class="card-header bg-gradient-info text-white d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <h3 class="mb-0"><i class="fa-solid fa-graduation-cap"></i> Student List</h3>
                                <div class="d-flex gap-3 flex-wrap">
                                    <a href="{{ route('student.export.pdf') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-file-pdf"></i> Export PDF</a>
                                    <a href="{{route('admitStudent')}}" class="btn btn-success btn-sm"><i class="fa-solid fa-user-plus"></i> New Admission</a>
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
                                <div class="mb-3">
                                    <form method="GET" class="row g-2 align-items-end">
                                        @php
                                            $classes = \App\Models\classManage::orderBy('id')->get();
                                            $sessions = \App\Models\sessionManage::orderBy('id')->get();
                                            $sections = \App\Models\sectionManage::orderBy('id')->get();
                                            $departments = \App\Models\Department::orderBy('id')->get();
                                        @endphp
                                        <div class="col-auto">
                                            <label class="form-label">Class</label>
                                            <select name="classId" class="form-control">
                                                <option value="">All</option>
                                                @foreach($classes as $c)
                                                <option value="{{ $c->id }}" {{ request()->get('classId') == $c->id ? 'selected' : '' }}>{{ $c->className }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label">Session</label>
                                            <select name="sessionId" class="form-control">
                                                <option value="">All</option>
                                                @foreach($sessions as $s)
                                                <option value="{{ $s->id }}" {{ request()->get('sessionId') == $s->id ? 'selected' : '' }}>{{ $s->session }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label">Section</label>
                                            <select name="sectionId" class="form-control">
                                                <option value="">All</option>
                                                @foreach($sections as $sec)
                                                <option value="{{ $sec->id }}" {{ request()->get('sectionId') == $sec->id ? 'selected' : '' }}>{{ $sec->section }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label">Department</label>
                                            <select name="departmentId" class="form-control">
                                                <option value="">All</option>
                                                @foreach($departments as $d)
                                                <option value="{{ $d->id }}" {{ request()->get('departmentId') == $d->id ? 'selected' : '' }}>{{ $d->departmentName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label">Search</label>
                                            <input name="search" type="text" class="form-control" value="{{ request()->get('search') }}" placeholder="Name / Student ID / Phone">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="{{ route('studentList') }}" class="btn btn-light">Reset</a>
                                        </div>
                                    </form>
                                </div>
                                <div class="mb-3 d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display:none;">
                                        <i class="fa-solid fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                                    </button>
                                </div>
                                <form id="bulkDeleteForm" method="POST" action="{{ route('studentBulkDelete') }}" style="display:none;">
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
                                            <th>Student ID</th>
                                            <th>Roll</th>
                                            <th>Name</th>
                                            <th>Session</th>
                                            <th>Class</th>
                                            <th>Department</th>
                                            <th>Section</th>
                                            <th>Mobile</th>
                                            <th class="text-center">ID Card</th>
                                            <th class="text-center">Testimonial</th>
                                            <th class="text-center">TC</th>
                                            <th style="width: 120px;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($studentData))
                                        @foreach($studentData as $std)
                                        @php 
                                            $sessionDetails = \App\Models\sessionManage::all();
                                            $sessionData  = \App\Models\sessionManage::find($std->sessName);
                                            $classData  = \App\Models\classManage::find($std->className);
                                            $sectionData  = \App\Models\sectionManage::find($std->sectionName);
                                            $departmentData  = \App\Models\Department::find($std->departmentName);
                                        @endphp
                                        <tr class="data-row">
                                            <td>
                                                <input type="checkbox" class="row-checkbox form-check-input" value="{{ $std->id }}">
                                            </td>
                                            <td>{{ $std->stdId }}</td>
                                            @if(!empty($std->rollNumber))
                                            <td>{{ $std->rollNumber }}</td>
                                            @else
                                            <td>-</td>
                                            @endif
                                            <td>{{ $std->fullName." ".$std->sureName }}</td>
                                            @if(!empty($sessionData))
                                            <td>{{$sessionData->session}}</td>
                                            @else
                                            <td>-</td>
                                            @endif
                                            @if(!empty($classData))
                                            <td>{{$classData->className}}</td>
                                            @else
                                            <td>-</td>
                                            @endif
                                            @if(!empty($departmentData))
                                            <td>{{$departmentData->departmentName}}</td>
                                            @else
                                            <td>-</td>
                                            @endif
                                            @if(!empty($sectionData))
                                            <td>{{$sectionData->section}}</td>
                                            @else
                                            <td>-</td>
                                            @endif
                                            <td>{{ $std->phone }}</td>
                                            <td class="text-center"><a href="{{ route('stdIdCard',['stdId'=>$std->id]) }}" class="btn btn-sm btn-success" title="ID Card"><i class="fa-solid fa-id-card"></i></a></td>
                                            @php 
                                                $existingT = \App\Models\Testimonial::where('admission_id', $std->id)->latest('id')->first();
                                                $eligible = false;
                                                if(!empty($classData) && !empty($classData->className)){
                                                    $cn = strtolower(trim($classData->className));
                                                    $eligible = (
                                                        $cn === 'five' || $cn === 'ten' || $cn === 'twelve' ||
                                                        $cn === '5' || $cn === '10' || $cn === '12' ||
                                                        strpos($cn,'five') !== false || strpos($cn,'ten') !== false || strpos($cn,'twelve') !== false
                                                    );
                                                }
                                            @endphp
                                            <td class="text-center">
                                                @if($eligible)
                                                    @if($existingT)
                                                        <div class="badge bg-success mb-1">Created</div>
                                                        <div><a href="{{ route('testimonials.show', $existingT->id) }}" class="btn btn-sm btn-info" title="View Testimonial"><i class="fa-solid fa-certificate"></i></a>
                                                        <a href="{{ route('testimonials.print', $existingT->id) }}" class="btn btn-sm btn-primary" title="Print Testimonial" target="_blank"><i class="fa-solid fa-print"></i></a></div>
                                                    @else
                                                        <div class="badge bg-warning mb-1">Not Created</div>
                                                        <div><a href="{{ route('testimonials.create', ['admission' => $std->id]) }}" class="btn btn-sm btn-success" title="Create Testimonial"><i class="fa-solid fa-certificate"></i></a></div>
                                                    @endif
                                                @else
                                                    <div class="badge bg-secondary mb-1">Not Eligible</div>
                                                    <i class="fa-solid fa-circle-info" title="Testimonial available only for Class Five, Ten & Twelve" style="color:#9aa0a6;"></i>
                                                @endif
                                            </td>
                                            @php
                                                $existingTC = \App\Models\TransferCertificate::where('admission_id', $std->id)->latest('id')->first();
                                            @endphp
                                            <td class="text-center">
                                                @if($existingTC)
                                                    <div class="badge bg-success mb-1">Created</div>
                                                    <div><a href="{{ route('tc.show', $existingTC->id) }}" class="btn btn-sm btn-info" title="View TC"><i class="fa-solid fa-award"></i></a>
                                                    <a href="{{ route('tc.print', $existingTC->id) }}" class="btn btn-sm btn-primary" title="Print TC" target="_blank"><i class="fa-solid fa-print"></i></a></div>
                                                @else
                                                    <div class="badge bg-warning mb-1">Not Created</div>
                                                    <div><a href="{{ route('tc.create', ['admission' => $std->id]) }}" class="btn btn-sm btn-success" title="Create Transfer Certificate"><i class="fa-solid fa-award"></i></a></div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('viewAdmission',['stdId'=>$std->id]) }}" class="btn btn-sm btn-info" title="View Profile"><i class="fa-solid fa-eye"></i></a>
                                                <a href="{{ route('editStudent',['stdId'=>$std->id]) }}" class="btn btn-sm btn-primary" title="Edit Student"><i class="fa-solid fa-pen-to-square"></i></a>
                                                <a href="#" class="btn btn-sm btn-danger delete-single" data-id="{{ $std->id }}" data-name="{{ $std->fullName }}" title="Delete"><i class="fa-solid fa-trash"></i></a>
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
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
<style>
/* Color Scheme */
:root {
    --primary-color: #17a2b8;
    --primary-dark: #138496;
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
.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
    background-color: #f0f8ff;
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
    margin-bottom: 0.5rem;
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

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');
    const deleteForm = document.getElementById('bulkDeleteForm');
    const deleteIds = document.getElementById('deleteIds');

    selectAllCheckbox?.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButton();
    });

    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateDeleteButton();
            updateSelectAll();
        });
    });

    function updateDeleteButton() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        selectedCount.textContent = checkedCount;
        bulkDeleteBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
    }

    function updateSelectAll() {
        const totalCheckboxes = rowCheckboxes.length;
        const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked').length;
        selectAllCheckbox.checked = totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes;
    }

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

(function(){
    function initDT(){
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) { return false; }
        var $ = jQuery;
        var $tbl = $('#myTable');
        if($tbl.length && !$tbl.hasClass('dt-initialized')){
            $tbl.addClass('dt-initialized').DataTable({
                pageLength: 25,
                order: [[1,'asc']],
                lengthMenu: [10,25,50,100],
                language: { search: "Search:", lengthMenu: "Show _MENU_ entries" },
                responsive: true
            });
        }
        return true;
    }
    if(!initDT()){
        document.addEventListener('DOMContentLoaded', initDT);
    }
})();
</script>
@endpush

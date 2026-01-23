@extends('cultivation.include')
@section('backTitle')
Bulk Teacher Upload Photos
@endsection
@section('backIndex')
<div class="row gutters-20 mt-4">
    <div class="col-12 col-md-10 mx-auto">
        <div class="card card-default">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Teacher Bulk Upload</h3>
                <a href="{{ route('teacherList') }}" class="btn btn-secondary btn-sm">Back to list</a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="alert alert-info">
                    <strong>No fields are required!</strong> You can leave any column blank. Teacher ID will be auto-generated if not provided.
                    <ul class="mb-0 mt-2">
                        <li>Existing teachers are matched by Teacher ID or Email</li>
                        <li>Blank cells are ignored during updates</li>
                        <li>All fields are optional - fill only what you need</li>
                    </ul>
                </div>

                <div class="mb-3 d-flex gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('teacherBulkSample') }}">
                        <i class="fas fa-download"></i> Download Sample CSV (with data)
                    </a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('teacherTemplate') }}">
                        <i class="fas fa-file"></i> Download Empty Template
                    </a>
                </div>

                <form method="post" action="{{ route('teacherBulkUploadStore') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">CSV/Excel File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv,.xlsx,.xls,text/csv" required>
                            <small class="text-muted">Max 10MB. Supported formats: CSV, XLSX, XLS</small>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload &amp; Process
                        </button>
                    </div>
                </form>

                <hr>
                <h5>Supported Columns</h5>
                <p class="text-muted mb-2">All columns are <strong>optional</strong>. Use any of the headers below (case-insensitive). Extra columns are ignored.</p>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled small">
                            <li><span class="badge bg-secondary text-white">teacher_id</span> - Auto-generated if blank</li>
                            <li><span class="badge bg-secondary text-white">first_name</span> - Teacher's first name</li>
                            <li><span class="badge bg-secondary text-white">last_name</span> - Teacher's last name</li>
                            <li><span class="badge bg-secondary text-white">fathers_name</span> - Father's name</li>
                            <li><span class="badge bg-secondary text-white">mothers_name</span> - Mother's name</li>
                            <li><span class="badge bg-secondary text-white">gender</span> - Male, Female, Others</li>
                            <li><span class="badge bg-secondary text-white">dob</span> - Date of birth (YYYY-MM-DD)</li>
                            <li><span class="badge bg-secondary text-white">designation</span> - Job title</li>
                            <li><span class="badge bg-secondary text-white">blood_group</span> - A+, B+, O+, AB+, etc.</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled small">
                            <li><span class="badge bg-secondary text-white">religion</span> - Islam, Hindu, Christian, etc.</li>
                            <li><span class="badge bg-secondary text-white">email</span> - Email address</li>
                            <li><span class="badge bg-secondary text-white">join_date</span> - Joining date (YYYY-MM-DD)</li>
                            <li><span class="badge bg-secondary text-white">mobile</span> - Phone number</li>
                            <li><span class="badge bg-secondary text-white">address</span> - Full address</li>
                            <li><span class="badge bg-secondary text-white">mpo_index</span> - MPO index number</li>
                            <li><span class="badge bg-secondary text-white">pds_id</span> - PDS ID</li>
                            <li><span class="badge bg-secondary text-white">rank</span> - Teacher rank</li>
                        </ul>
                    </div>
                </div>

                <h6 class="mt-4 mb-2">Sample CSV Format</h6>
<pre class="bg-light p-3 border small">teacher_id,first_name,last_name,email,mobile,designation,gender
T202400001,Anika,Rahman,anika@example.com,01711111111,Senior Teacher,Female
,Rafiq,Hossain,rafiq@example.com,01722222222,Assistant Teacher,Male
,Sadia,Khan,,,Lecturer,Female</pre>
                <p class="text-muted mb-0">
                    <strong>Note:</strong> All fields are optional. Leave cells blank if you don't have the data. Teacher ID is auto-generated if not provided.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection


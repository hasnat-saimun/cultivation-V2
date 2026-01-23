@extends('cultivation.include')
@section('backTitle')
Bulk Staff Upload Photos
@endsection
@section('backIndex')
<div class="row gutters-20 mt-4">
    <div class="col-12 col-md-10 mx-auto">
        <div class="card card-default">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Staff Bulk Upload</h3>
                <a href="{{ route('staffList') }}" class="btn btn-secondary btn-sm">Back to list</a>
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

                <p class="text-muted">Upload a CSV or Excel file to add/update staff. Matching is automatic by Staff ID or Email.</p>
                <div class="mb-3">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('staffBulkSample') }}">Download sample CSV</a>
                </div>
                <form method="post" action="{{ route('staffBulkUploadStore') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv,.xlsx,.xls,text/csv" required>
                            <small class="text-muted">Max 10MB. Supports CSV, XLSX, XLS with a header row.</small>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">Upload &amp; Process</button>
                    </div>
                </form>

                <hr>
                <h5>Supported columns</h5>
                <p class="text-muted mb-2">Use any of the headers below (case-insensitive). Extra columns are ignored.</p>
                <div class="badge bg-light text-dark mb-3">staff_id, first_name, last_name, fathers_name, mothers_name, gender, dob, designation, blood_group, religion, email, join_date, mobile, address, rank</div>

                <h6 class="mb-2">Sample CSV</h6>
<pre class="bg-light p-3 border">staff_id,first_name,last_name,email,join_date,mobile,designation,gender,dob
S202400001,Karim,Ali,karim@example.com,2023-02-14,01733333333,Office Assistant,Male,1992-07-08
S202400002,Sadia,Khan,sadia@example.com,2022-11-20,01744444444,Accountant,Female,1989-09-30</pre>
                <p class="text-muted mb-0">If a match is found, the row is updated; otherwise a new record is created (requires staffId and firstName).</p>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('cultivation.include')
@section('backTitle')
Teacher Bulk Upload
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

                <p class="text-muted">Upload a CSV to add/update teachers. Existing rows are matched by the field you choose below.</p>
                <div class="mb-3">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('teacherBulkSample') }}">Download sample CSV</a>
                </div>
                <form method="post" action="{{ route('teacherBulkUploadStore') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Match existing by</label>
                            <select name="match_by" class="form-control" required>
                                @foreach($matchOptions as $val => $label)
                                    <option value="{{ $val }}" {{ old('match_by') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                            <small class="text-muted">Max 10MB. UTF-8 CSV with a header row.</small>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">Upload &amp; Process</button>
                    </div>
                </form>

                <hr>
                <h5>Supported columns</h5>
                <p class="text-muted mb-2">Use any of the headers below (case-insensitive). Extra columns are ignored.</p>
                <div class="badge bg-light text-dark mb-3">{{ implode(', ', $allowedColumns) }}</div>

                <h6 class="mb-2">Sample CSV</h6>
<pre class="bg-light p-3 border">teacherId,firstName,lastName,email,joinDate,mobile,designation,gender,dob
T-1001,Anika,Rahman,anika@example.com,2023-01-10,01711111111,Assistant Professor,Female,1990-05-12
T-1002,Rafiq,Hossain,rafiq@example.com,2022-08-01,01722222222,Lecturer,Male,1988-03-04</pre>
                <p class="text-muted mb-0">If a match is found, the row is updated; otherwise a new record is created (requires teacherId and firstName).</p>
            </div>
        </div>
    </div>
</div>
@endsection

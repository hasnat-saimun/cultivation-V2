@extends('cultivation.include')
@section('backTitle')
Teacher List
@endsection
@section('backIndex')
                <!-- Social Media Start Here -->
                <div class="row gutters-20 mt-4">
                    <div class="col-12 col-md-10 mx-auto">
                        <div class="card card-default">
                            <div class="card-header bg-light">
                                <h3>All Teacher</h3>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#bulkUploadModal">Bulk Upload</button>
                                    <a href="{{ route('addTeacher') }}" class="btn btn-success">Add Profile</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        @if(session()->has('success'))
                                            <div class="alert alert-success w-100">
                                                {{ session()->get('success') }}
                                            </div>
                                        @endif
                                        @if(session()->has('error'))
                                            <div class="alert alert-danger w-100">
                                                {{ session()->get('error') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <table id="myTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Join Date</th>
                                            <th>Email</th>
                                            <th>Mobile</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($profileData))
                                        @foreach($profileData as $teacher)
                                            <tr>
                                                <td>{{ $teacher->teacherId }}</td>
                                                <td>{{ $teacher->firstName." ".$teacher->lastName }}</td>
                                                <td>{{ $teacher->joinDate }}</td>
                                                <td>{{ $teacher->email }}</td>
                                                <td>{{ $teacher->mobile }}</td>
                                                <td>
                                                    <a href="{{ route('viewTeacher',  ['profileId'=>$teacher->id]) }}"><i class="fa-solid fa-eye mx-2" style="color:rgb(35 170 211);"></i></a>
                                                    <a href="{{ route('editTeacher',['profileId'=>$teacher->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                                    <a href="{{ route('delTeacher',['profileId'=>$teacher->id]) }}" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
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

                <!-- Bulk Upload Modal -->
                <div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="bulkUploadLabel">Teacher Bulk Upload</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                            <form id="bulkUploadForm" method="post" action="{{ route('teacherBulkUploadStore') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body">
                                    <div id="uploadMessage"></div>
                                    <p class="text-muted mb-3">Upload a CSV to add/update teachers. Existing rows are matched by the field you choose.</p>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Match existing by</label>
                                            <select name="match_by" class="form-control" required>
                                                <option value="">Select field</option>
                                                <option value="teacherId">Teacher ID</option>
                                                <option value="email">Email</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">CSV File</label>
                                            <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('teacherBulkSample') }}">Download sample CSV</a>
                                    </div>
                                    <hr>
                                    <small class="text-muted">Supported columns: teacherId, firstName, lastName, email, joinDate, mobile, designation, gender, dob, fathersName, mothersName, blGroup, religion, address, mpoIndex, pdsId, rank</small>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Upload &amp; Process</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
@endsection

@push('scripts')
<script>
    document.getElementById('bulkUploadForm')?.addEventListener('submit', function(e) {
        const msgDiv = document.getElementById('uploadMessage');
        msgDiv.innerHTML = '<div class="alert alert-info">Processing...</div>';
    });
</script>
@endpush
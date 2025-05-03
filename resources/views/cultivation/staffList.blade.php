@extends('cultivation.include')
@section('backTitle')
All Staffs
@endsection
@section('backIndex')
                <!-- Social Media Start Here -->
                <div class="row gutters-20 mt-4">
                    <div class="col-12  mx-auto">
                        <div class="card card-default">
                            <div class="card-header bg-light">
                                <h3>All Staff</h3>
                                <a href="{{ route('addStaff') }}" class="btn btn-success">Add Profile</a>
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
                                        @foreach($profileData as $staff)
                                            <tr>
                                                <td>{{ $staff->staffId }}</td>
                                                <td>{{ $staff->firstName}}</td>
                                                <td>{{ $staff->joinDate }}</td>
                                                <td>{{ $staff->email }}</td>
                                                <td>{{ $staff->mobile }}</td>
                                                <td>
                                                    <a href="{{ route('viewStaff',  ['profileId'=>$staff->id]) }}"><i class="fa-solid fa-eye mx-2" style="color:rgb(35 170 211);"></i></a>
                                                    <a href="{{ route('editStaff',['profileId'=>$staff->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                                    <a href="{{ route('delStaff',['profileId'=>$staff->id]) }}"onclick="return confirm('Are you sure you want to delete this item?');" title="Get Id Card" ><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
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
@endsection
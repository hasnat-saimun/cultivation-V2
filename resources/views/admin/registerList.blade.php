@extends('cultivation.include')
@section('backTitle')
Registered List
@endsection
@section('backIndex')
                <!-- Social Media Start Here -->
                <div class="row gutters-20 mt-4">
                    <div class="col-12  mx-auto">
                        <div class="card card-default">
                            <div class="card-header bg-light">
                                <a href="{{ route('registerForm') }}" class="btn btn-success">Add Profile</a>
                                <h3>Registered List</h3>
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
                                <table id="myTable" class="table table-striped table-responsive">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Institute Name</th>
                                            <th>Address</th>
                                            <th>Web Name</th>
                                            <th>Official Mobile</th>
                                            <th>Official Email</th>
                                            <th>Logo</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($regData))
                                        @foreach($regData as $allData)
                                            <tr>
                                                <td>{{ $allData->id }}</td>
                                                <td>{{ $allData->insName}}</td>
                                                <td>{{ $allData->insAddress }}</td>
                                                <td>{{ $allData->webName }}</td>
                                                <td>{{ $allData->officeMobile }}</td>
                                                <td>{{ $allData->officeMail }}</td><td><img class="w-50" src="{{ asset('public/upload/image/registerLogo/').'/'.$allData->insLogo}}" alt="{!! $allData->insName !!}" style="max-height:120px !important"></td>
                                                <td>
                                                <a href="{{ route('registerForm') }}" class="btn btn-success mb-2 w-100">Website</a><br>
                                                <a href="{{ route('registerForm') }}" class="btn btn-danger">Admin Access</a>
                                                    
                                                    <!-- <a href="{{ route('registerEdit',['regId'=>$allData->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                                    <a href="{{ route('registerDel',['regId'=>$allData->id]) }}" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a> -->
                                                </td>
                                            </tr>
                                        @endforeach
                                        @else
                                            <tr>
                                                <td>SBC02</td>
                                                <td>Rasek Khondokar</td>
                                                <td>2023-2024</td>
                                                <td>Science</td>
                                                <td>Edit</td>
                                                <td>Edit</td>
                                                <td>Edit</td>
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
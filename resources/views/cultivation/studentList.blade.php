@extends('cultivation.include')
@section('backTitle')
Student List
@endsection
@section('backIndex')
                <!-- Social Media Start Here -->
                <div class="row gutters-20 mt-4">
                    <div class="col-12 col-md-12 mx-auto">
                        <div class="card card-default">
                            <div class="card-header bg-light">
                                <a href="{{route('admitStudent')}}" class="btn btn-success">New Admission</a>
                            </div>
                            <div class="card-header bg-light">
                                <h3>Student List</h3>
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
                                            <th>Session</th>
                                            <th>Class</th>
                                            <th>Department</th>
                                            <th>Section</th>
                                            <th>Mobile</th>
                                            <th>ID Card</th>
                                            <th>Action</th>
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
                                        <tr>
                                            <td>{{ $std->stdId }}</td>
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
                                            <td class="text-center"><a href="{{ route('stdIdCard',['stdId'=>$std->id]) }}"><i class="fa-solid fa-id-card mx-2" style="color:#19761f;"></i></a></td>
                                            <td>
                                                <a href="{{ route('viewAdmission',['stdId'=>$std->id]) }}"><i class="fa-solid fa-eye mx-2" style="color:rgb(35 170 211);"></i></a>
                                                <a href="{{ route('editStudent',['stdId'=>$std->id]) }}" ><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                                <a href="{{ route('delStudent',['stdId'=>$std->id]) }}" onclick="return confirm('Are you sure you want to delete this item?');" title="Get Id Card"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
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
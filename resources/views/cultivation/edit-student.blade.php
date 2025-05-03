@extends('cultivation.include')
@section('backTitle')
Edit Student
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto">
                            <div class="card-header bg-light">
                                <a href="{{route('studentList')}}" class="btn btn-success">Student List</a>
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
                            <div class="heading-layout1">
                                <div class="item-title">
                                    <h3>Edit Student Profile</h3>
                                </div>
                                <div class="dropdown">
                                    <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown"
                                        aria-expanded="false">...</a>

                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="#"><i
                                                class="fas fa-times text-orange-red"></i>Close</a>
                                        <a class="dropdown-item" href="#"><i
                                                class="fas fa-cogs text-dark-pastel-green"></i>Edit</a>
                                        <a class="dropdown-item" href="#"><i
                                                class="fas fa-redo-alt text-orange-peel"></i>Refresh</a>
                                    </div>
                                </div>
                            </div>


                            @if(!empty($stdData))
                            <form action="{{ route('stdPhotoUpdate') }}" class="form" enctype="multipart/form-data" method="POST">
                                @csrf
                                <input type="hidden" value="{{ $stdData->id }}" name="stdId">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group mg-t-30">
                                        @if(!empty($stdData->avatar))
                                        <img class="w-75" src="{{ asset('/public/upload/image/student/') }}/{{$stdData->avatar}}" alt="$stdData->firstName.' '.$stdData->lastName"><br>
                                        <a href="{{route('delStudentPhoto',['stdId'=>$stdData->id])}}" class=" mt-3 w-75 btn btn-danger btn-lg">Remove</a>
                                        @else
                                        <label class="text-dark-medium">Avatar (150px X 150px)</label>
                                        <input type="file" name="avatar" class="form-control-file">
                                        <div class="mt-4"><input type="submit" value="Update" class="btn btn-primary"></div>
                                        @endif
                                    </div>
                                </div>
                            </form>
                            <form class="new-added-form" action="{{ route('updateAdmit') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" value="{{ $stdData->id }}" name="stdId">
                                    <div class="row mb-2">
                                        <h5 class="fw-semibold">Personal Information</h5>
                                    </div>
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Admission ID</label>
                                        <input type="text" class="form-control" value="{{ $stdData->stdId }}" readonly>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>First Name *</label>
                                        <input type="text" name="fullName" placeholder="Enter student first name" class="form-control" value="{{ $stdData->fullName }}" >
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Last Name *</label>
                                        <input type="text" name="sureName" placeholder="Enter student last name" class="form-control" value="{{ $stdData->sureName }}" >
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Father's Name *</label>
                                        <input type="text" name="fatherName" placeholder="Enter fathers name" class="form-control" value="{{$stdData->father}}" >
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Mother's Name *</label>
                                        <input type="text" name="motherName" placeholder="Enter mothers name" class="form-control" value="{{ $stdData->mother}}" >
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Gender *</label>
                                        <select class="select2" name="gender" >
                                            <option value="{{ $stdData->gender}}">
                                            @if($stdData->gender==1)
                                                Male
                                                @elseif($stdData->gender==2)
                                                Female
                                                @else
                                                Others
                                                @endif
                                            </option>
                                            <option value="">Select *</option>
                                            <option value="1">Male</option>
                                            <option value="2">Female</option>
                                            <option value="3">Others</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Date Of Birth *</label>
                                        <input type="date" name="dob" placeholder="dd/mm/yyyy" class="form-control "value="{{ $stdData->dob}}" >
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Blood Group *</label>
                                        <select class="select2" name="blGroup">
                                            <option value="{{ $stdData->blGroup}}">
                                                @if($stdData->blGroup==1)
                                                A+
                                                @elseif($stdData->blGroup==2)
                                                A-
                                                @elseif($stdData->blGroup==3)
                                                B+
                                                @elseif($stdData->blGroup==4)
                                                B-
                                                @elseif($stdData->blGroup==5)
                                                O+
                                                @elseif($stdData->blGroup==6)
                                                O-
                                                @elseif($stdData->blGroup==7)
                                                AB+
                                                @else
                                                AB-
                                                @endif</option>
                                            <option value="">Select *</option>
                                            <option value="1">A+</option>
                                            <option value="2">A-</option>
                                            <option value="3">B+</option>
                                            <option value="4">B-</option>
                                            <option value="5">O+</option>
                                            <option value="6">O-</option>
                                            <option value="7">AB+</option>
                                            <option value="8">AB-</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Religion *</label>
                                        <select class="select2" name="religion" >
                                            <option value="{{ $stdData->religion}}">
                                                @if($stdData->religion==1)
                                                Islam
                                                @elseif($stdData->religion==2)
                                                Hindu
                                                @elseif($stdData->religion==3)
                                                Christian
                                                @elseif($stdData->religion==4)
                                                Buddish
                                                @else
                                                Others
                                                @endif</option>
                                            <option value="">Select *</option>
                                            <option value="1">Islam</option>
                                            <option value="2">Hindu</option>
                                            <option value="3">Christian</option>
                                            <option value="4">Buddish</option>
                                            <option value="5">Others</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>E-Mail</label>
                                        <input type="email" name="mail" placeholder="Enter student email" class="form-control" value="{{ $stdData->mail}}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Phone</label>
                                        <input type="text" name="phone" placeholder="Enter gurdian mobile number" class="form-control" value="{{ $stdData->phone}}" >
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group  ">
                                        <label>Address</label>
                                        <input type="text" class="form-control" placeholder="Student full address" name="address" value="{{ $stdData->address}}">
                                    </div>
                                </div>
                                <div class="row mt-5 mb-2">
                                    <h5 class="fw-semibold">Academic Information</h5>
                                </div>
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        @php 
                                            $sessionDetails = \App\Models\sessionManage::all();
                                            $sessionData  = \App\Models\sessionManage::find($stdData->sessName);
                                            $classData  = \App\Models\classManage::find($stdData->className);
                                            $sectionData  = \App\Models\sectionManage::find($stdData->sectionName);
                                            $departmentData  = \App\Models\Department::find($stdData->departmentName);
                                        @endphp
                                        <label>Session *</label>
                                        <select class="select2" name="sessName" >
                                        @if(!empty($sessionData))
                                        <option value="{{$sessionData->id}}">{{$sessionData->session}}</option>
                                        @endif
                                            @if(!empty($sessionDetails) && count($sessionDetails)>0)
                                            @foreach($sessionDetails as $sd)
                                                <option value="{{ $sd->id}}">{{ $sd->session}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Class *</label>
                                        <select class="select2" name="className" >
                                        @if(!empty($classData))
                                        <option value="{{$classData->id}}">{{$classData->className}} </option>
                                        @endif
                                        @if(!empty($classDetails) && count($classDetails)>0)
                                            @foreach($classDetails as $cd)
                                            <option value="{{ $cd->id}}">{{ $cd->className}}</option>
                                            @endforeach
                                        @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Department *</label>
                                        <select class="select2" name="departmentName" >
                                        @if(!empty($departmentData))
                                        <option value="{{$departmentData->id}}">{{$departmentData->departmentName}} </option>
                                        @endif
                                        @if(!empty($departmentDetails) && count($departmentDetails)>0)
                                            @foreach($departmentDetails as $dd)
                                            <option value="{{ $dd->id}}">{{ $dd->departmentName}}</option>
                                            @endforeach
                                        @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Section/Group *</label>
                                        <select class="select2" name="sectionName" >
                                        @if(!empty($sectionData))
                                        <option value="{{$sectionData->id}}">{{$sectionData->section}} </option>
                                        @endif
                                        @if(!empty($sectionDatails) && count($sectionDatails)>0)
                                        @foreach($sectionDatails as $sec)
                                        <option value="{{$sec->id}}">{{$sec->section}}</option>
                                        @endforeach
                                        @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Roll</label>
                                        <input type="text" name="rollNumber" placeholder="Enter student class roll" class="form-control" value="{{ $stdData->rollNumber}}" >
                                    </div>
                                </div>
                                <div class="row mb-2 mt-5">
                                    <h5 class="fw-semibold">Guardian Information</h5>
                                </div>
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label for="gurdian">Guardian Name</label>
                                        <input type="text" class="form-control" placeholder="Enter guardian name" name="gurdian" id="gurdian" value="{{ $stdData->gurdianName}}"  >
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label for="gurdianPhone">Mobile Number</label>
                                        <input type="number" class="form-control" placeholder="Enter phone number" name="gurdianPhone" id="gurdianPhone" value="{{ $stdData->gurdianMobile}}" >
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label for="relationWithStd" >Relation *</label>
                                        <select class="select2" id="relationWithStd"  name="relationWithStd">
                                            <option value="{{ $stdData->relationGurdian}}"> @if($stdData->relationGurdian==1)
                                                Father
                                                @elseif($stdData->relationGurdian==2)
                                                Mother
                                                @elseif($stdData->relationGurdian==3)
                                                Brother
                                                @elseif($stdData->relationGurdian==4)
                                                Sister
                                                @elseif($stdData->relationGurdian==5)
                                                Uncle
                                             
                                                @elseif($stdData->relationGurdian==6)
                                                Aunty
                                               
                                                Others
                                                @endif</option>
                                            <option value="1">Father</option>
                                            <option value="2">Mother</option>
                                            <option value="3">Brother</option>
                                            <option value="4">Sister</option>
                                            <option value="5">Uncle</option>
                                            <option value="6">Aunty</option>
                                            <option value="7">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12 form-group mg-t-8">
                                        <button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Save</button>
                                        <button type="reset" class="btn-fill-lg bg-blue-dark btn-hover-bluedark">Reset</button>
                                    </div>
                                </div>
                            </form>
                            @else
                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        Opps! Sorry, No profile found for update
                                    </div>
                                </div>
                            </div>    
                            @endif
                        </div>
                    </div>
                </div>
@endsection
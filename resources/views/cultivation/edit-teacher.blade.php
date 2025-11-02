@extends('cultivation.include')
@section('backTitle')
Edit Profile
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto">
                            <div class="card-header bg-light">
                                <a href="{{route('teacherList')}}" class="btn btn-success">Teacher List</a>
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
                                    <h3>Edit Teacher Profile</h3>
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
                            @if(!empty($profileData))
                            <form action="{{ route('updateTeacherPhoto') }}" class="form" enctype="multipart/form-data" method="POST">
                    @csrf
                    <input type="hidden" value="{{ $profileData->id }}" name="profileId" />
                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-12 form-group mg-t-30">
                            @if(!empty($profileData->avatar))
                            <div><img class="w-75" src="{{ asset('/public/upload/image/teacher/') }}/{{$profileData->avatar}}" alt="$profileData->insName" /><br /></div>
                            <a href="{{route('delTeacherPhoto',['profileId'=>$profileData->id])}}" class="mt-3 w-75 btn btn-danger btn-lg">Remove</a>
                            @else
                            <label class="text-dark-medium">Avatar (150px X 150px)</label>
                            <input type="file" name="avatar" class="form-control-file" />
                            <div class="mt-4"><input type="submit" value="Update" class="btn btn-primary" /></div>
                            @endif
                        </div>
                    </div>
                </form>
                            <form class="new-added-form" action="{{ route('updateTeacher') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" value="{{ $profileData->id }}" name="teacherId">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Teacher ID</label>
                                        <input type="text" value="{{ $profileData->teacherId }}" class="form-control">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Full Name *</label>
                                        <input type="text" name="firstName" placeholder="Enter first name" class="form-control" value="{{ $profileData->firstName }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Sure Name *</label>
                                        <input type="text" name="lastName" placeholder="Enter last name" class="form-control" value="{{ $profileData->lastName }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Father's Name *</label>
                                        <input type="text" name="fathersName" placeholder="Enter fathers name" class="form-control" value="{{ $profileData->fathersName }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Mother's Name *</label>
                                        <input type="text" name="mothersName" placeholder="Enter mothers name" class="form-control" value="{{ $profileData->mothersName }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Gender *</label>
                                        <select class="select2" name="gender">
                                            <option value="{{ $profileData->gender }}">
                                                @if($profileData->gender==1)
                                                Male
                                                @elseif($profileData->gender==2)
                                                Female
                                                @else
                                                Others
                                                @endif
                                            </option>
                                            <option value="1">Male</option>
                                            <option value="2">Female</option>
                                            <option value="3">Others</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Date Of Birth *</label>
                                        <input type="date" name="dob" placeholder="dd/mm/yyyy" class="form-control " value="{{ $profileData->dob }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Designation *</label>
                                        <select class="select2" name="designation">
                                            <option value="">Select Designation</option>
                                            <option value="1" {{ $profileData->designation == 1 ? 'selected' : '' }}>Principal</option>
                                            <option value="2" {{ $profileData->designation == 2 ? 'selected' : '' }}>Principal(Incharge)</option>
                                            <option value="3" {{ $profileData->designation == 3 ? 'selected' : '' }}>Vice Principal</option>
                                            <option value="4" {{ $profileData->designation == 4 ? 'selected' : '' }}>Head Master</option>
                                            <option value="5" {{ $profileData->designation == 5 ? 'selected' : '' }}>Head Master(Incharge)</option>
                                            <option value="6" {{ $profileData->designation == 6 ? 'selected' : '' }}>Assistant Head Master</option>
                                            <option value="7" {{ $profileData->designation == 7 ? 'selected' : '' }}>Senior Teacher</option>
                                            <option value="8" {{ $profileData->designation == 8 ? 'selected' : '' }}>Assistant Teacher</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Join Date</label>
                                        <input type="date" name="joinDate" placeholder="mm/dd/yyyy" class="form-control" value="{{ $profileData->joinDate }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Blood Group *</label>
                                        <select class="select2" name="blGroup">
                                            <option value="{{ $profileData->blGroup }}">
                                                @if($profileData->blGroup==1)
                                                A+
                                                @elseif($profileData->blGroup==2)
                                                A-
                                                @elseif($profileData->blGroup==3)
                                                B+
                                                @elseif($profileData->blGroup==4)
                                                B-
                                                @elseif($profileData->blGroup==5)
                                                O+
                                                @elseif($profileData->blGroup==6)
                                                O-
                                                @elseif($profileData->blGroup==7)
                                                AB+
                                                @else
                                                AB-
                                                @endif
                                            </option>
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
                                        <select class="select2" name="religion">
                                            <option value="{{ $profileData->religion }}">
                                                @if($profileData->religion==1)
                                                Islam
                                                @elseif($profileData->religion==2)
                                                Hindu
                                                @elseif($profileData->religion==3)
                                                Christian
                                                @elseif($profileData->religion==4)
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
                                        <input type="email" name="email" placeholder="Enter email" class="form-control" value="{{ $profileData->email }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Phone</label>
                                        <input type="text" name="mobile" placeholder="Enter mobile number" class="form-control" value="{{ $profileData->mobile }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group  ">
                                        <label>Address</label>
                                        <input type="text" class="form-control" placeholder="Teacher full address" name="address" value="{{ $profileData->address }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Address</label>
                                        <input type="text" class="form-control" placeholder="Enter full address" value="{{ $profileData->address }}" name="address">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>MPO Index</label>
                                        <input type="text" name="mpoIndex" placeholder="Enter MPO Index" class="form-control" value="{{ $profileData->mpoIndex }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>PDO ID</label>
                                        <input type="text" name="pdoId" placeholder="Enter PDO ID" class="form-control" value="{{ $profileData->pdoId }}">
                                    </div>
                                    
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Rnaking *</label>
                                        <select class="select2" name="rank">
                                            <option value="{{ $profileData->rank }}">{{ $profileData->rank }}</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                            <option value="9">9</option>
                                            <option value="10">10</option>
                                            <option value="11">11</option>
                                            <option value="12">12</option>
                                            <option value="13">13</option>
                                            <option value="14">14</option>
                                            <option value="15">15</option>
                                            <option value="16">16</option>
                                            <option value="17">17</option>
                                            <option value="18">18</option>
                                            <option value="19">19</option>
                                            <option value="20">20</option>
                                            <option value="21">21</option>
                                            <option value="23">23</option>
                                            <option value="24">24</option>
                                            <option value="25">25</option>
                                            <option value="26">26</option>
                                            <option value="27">27</option>
                                            <option value="28">28</option>
                                            <option value="29">29</option>
                                            <option value="30">30</option>
                                            <option value="31">31</option>
                                            <option value="32">32</option>
                                            <option value="34">34</option>
                                            <option value="35">35</option>
                                            <option value="36">36</option>
                                            <option value="37">37</option>
                                            <option value="38">38</option>
                                            <option value="39">39</option>
                                            <option value="40">40</option>
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
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
                                <a href="{{route('staffList')}}" class="btn btn-success">Staff List</a>
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
                                    <h3>Edit Profile</h3>
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
                            <form action="{{ route('updateStaffPhoto') }}" class="form" enctype="multipart/form-data" method="POST">
                    @csrf
                    <input type="hidden" value="{{ $profileData->id }}" name="staffId" />
                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-12 form-group mg-t-30">
                            @if(!empty($profileData->avatar))
                            <div><img class="w-75" src="{{ asset('/public/upload/image/staff/') }}/{{$profileData->avatar}}" alt="$profileData->firstName" /><br /></div>
                            <a href="{{route('delStaffPhoto',['profileId'=>$profileData->id])}}" class="mt-3 w-75 btn btn-danger btn-lg">Remove</a>
                            @else
                            <label class="text-dark-medium">Avatar (150px X 150px)</label>
                            <input type="file" name="avatar" class="form-control-file" />
                            <div class="mt-4"><input type="submit" value="Update" class="btn btn-primary" /></div>
                            @endif
                        </div>
                    </div>
                </form>
                            <form class="new-added-form" action="{{ route('updateStaff') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" value="{{ $profileData->id }}" name="staffId">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Staff ID</label>
                                        <input type="text" value="{{ $profileData->staffId }}" class="form-control" required readonly>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Full Name *</label>
                                        <input type="text" name="firstName" placeholder="Enter first name" class="form-control" value="{{ $profileData->firstName }}" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Sure Name *</label>
                                        <input type="text" name="lastName" placeholder="Enter last name" class="form-control" value="{{ $profileData->lastName }}" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Father's Name *</label>
                                        <input type="text" name="fathersName" placeholder="Enter fathers name" class="form-control" value="{{ $profileData->fathersName }}" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Mother's Name *</label>
                                        <input type="text" name="mothersName" placeholder="Enter mothers name" class="form-control" value="{{ $profileData->mothersName }}" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Gender *</label>
                                        <select class="select2" name="gender" required>
                                            <option value="1" {{ $profileData->gender == 1 ? 'selected' : '' }}>Male</option>
                                            <option value="2" {{ $profileData->gender == 2 ? 'selected' : '' }}>Female</option>
                                            <option value="3" {{ $profileData->gender == 3 ? 'selected' : '' }}>Others</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Date Of Birth *</label>
                                        <input type="date" name="dob" placeholder="dd/mm/yyyy" class="form-control" value="{{ $profileData->dob }}" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Designation *</label>
                                        <select class="select2" name="designation" required>
                                            <option value="1" {{ $profileData->designation == 1 ? 'selected' : '' }}>Administrative Officer</option>
                                            <option value="2" {{ $profileData->designation == 2 ? 'selected' : '' }}>Office Assistant-cum-Computer Operator</option>
                                            <option value="3" {{ $profileData->designation == 3 ? 'selected' : '' }}>Accounts Assistant</option>
                                            <option value="4" {{ $profileData->designation == 4 ? 'selected' : '' }}>Office Assistant</option>
                                            <option value="5" {{ $profileData->designation == 5 ? 'selected' : '' }}>Registrar</option>
                                            <option value="6" {{ $profileData->designation == 6 ? 'selected' : '' }}>Librarian</option>
                                            <option value="7" {{ $profileData->designation == 7 ? 'selected' : '' }}>Assistant Librarian</option>
                                            <option value="7" {{ $profileData->designation == 7 ? 'selected' : '' }}>IT Officer / System Admin / ICT Technician</option>
                                            <option value="8" {{ $profileData->designation == 8 ? 'selected' : '' }}>Data Entry Operator</option>
                                            <option value="9" {{ $profileData->designation == 9 ? 'selected' : '' }}>Lab Assistant / Lab Attendant</option>
                                            <option value="10" {{ $profileData->designation == 10 ? 'selected' : '' }}>Sports Instructor / Coach</option>
                                            <option value="11" {{ $profileData->designation == 11 ? 'selected' : '' }}>Music Teacher / Art Teacher</option>
                                            <option value="12" {{ $profileData->designation == 12 ? 'selected' : '' }}>Hostel Superintendent / Hostel Warden</option>
                                            <option value="13" {{ $profileData->designation == 13 ? 'selected' : '' }}>Office Peon / Office Assistant</option>
                                            <option value="14" {{ $profileData->designation == 14 ? 'selected' : '' }}>MLSS</option>
                                            <option value="15" {{ $profileData->designation == 15 ? 'selected' : '' }}>Security Guard</option>
                                            <option value="16" {{ $profileData->designation == 16 ? 'selected' : '' }}>Gatekeeper</option>
                                            <option value="17" {{ $profileData->designation == 17 ? 'selected' : '' }}>Gardener</option>
                                            <option value="18" {{ $profileData->designation == 18 ? 'selected' : '' }}>Cleaner / Ayah</option>
                                            <option value="19" {{ $profileData->designation == 19 ? 'selected' : '' }}>Driver</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Join Date</label>
                                        <input type="date" name="joinDate" placeholder="mm/dd/yyyy" class="form-control" value="{{ $profileData->joinDate }}" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Blood Group *</label>
                                        <select class="select2" name="blGroup">
                                            <option value="1" {{ $profileData->blGroup == 1 ? 'selected' : '' }}>A+</option>
                                            <option value="2" {{ $profileData->blGroup == 2 ? 'selected' : '' }}>A-</option>
                                            <option value="3" {{ $profileData->blGroup == 3 ? 'selected' : '' }}>B+</option>
                                            <option value="4" {{ $profileData->blGroup == 4 ? 'selected' : '' }}>B-</option>
                                            <option value="5" {{ $profileData->blGroup == 5 ? 'selected' : '' }}>O+</option>
                                            <option value="6" {{ $profileData->blGroup == 6 ? 'selected' : '' }}>O-</option>
                                            <option value="7" {{ $profileData->blGroup == 7 ? 'selected' : '' }}>AB+</option>
                                            <option value="8" {{ $profileData->blGroup == 8 ? 'selected' : '' }}>AB-</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Religion *</label>
                                        <select class="select2" name="religion" required>
                                            <option value="1" {{ $profileData->religion == 1 ? 'selected' : '' }}>Islam</option>
                                            <option value="2" {{ $profileData->religion == 2 ? 'selected' : '' }}>Hindu</option>
                                            <option value="3" {{ $profileData->religion == 3 ? 'selected' : '' }}>Christian</option>
                                            <option value="4" {{ $profileData->religion == 4 ? 'selected' : '' }}>Buddish</option>
                                            <option value="5" {{ $profileData->religion == 5 ? 'selected' : '' }}>Others</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>E-Mail</label>
                                        <input type="email" name="staffMail" placeholder="Enter email" class="form-control" value="{{ $profileData->email }}">
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
                                        <label>Ranking *</label>
                                        <select class="select2" name="rank">
                                            <option value="1" {{ $profileData->rank == 1 ? 'selected' : '' }}>1</option>
                                            <option value="2" {{ $profileData->rank == 2 ? 'selected' : '' }}>2</option>
                                            <option value="3" {{ $profileData->rank == 3 ? 'selected' : '' }}>3</option>
                                            <option value="4" {{ $profileData->rank == 4 ? 'selected' : '' }}>4</option>
                                            <option value="5" {{ $profileData->rank == 5 ? 'selected' : '' }}>5</option>
                                            <option value="6" {{ $profileData->rank == 6 ? 'selected' : '' }}>6</option>
                                            <option value="7" {{ $profileData->rank == 7 ? 'selected' : '' }}>7</option>
                                            <option value="8" {{ $profileData->rank == 8 ? 'selected' : '' }}>8</option>
                                            <option value="9" {{ $profileData->rank == 9 ? 'selected' : '' }}>9</option>
                                            <option value="10" {{ $profileData->rank == 10 ? 'selected' : '' }}>10</option>
                                            <option value="11" {{ $profileData->rank == 11 ? 'selected' : '' }}>11</option>
                                            <option value="12" {{ $profileData->rank == 12 ? 'selected' : '' }}>12</option>
                                            <option value="13" {{ $profileData->rank == 13 ? 'selected' : '' }}>13</option>
                                            <option value="14" {{ $profileData->rank == 14 ? 'selected' : '' }}>14</option>
                                            <option value="15" {{ $profileData->rank == 15 ? 'selected' : '' }}>15</option>
                                            <option value="16" {{ $profileData->rank == 16 ? 'selected' : '' }}>16</option>
                                            <option value="17" {{ $profileData->rank == 17 ? 'selected' : '' }}>17</option>
                                            <option value="18" {{ $profileData->rank == 18 ? 'selected' : '' }}>18</option>
                                            <option value="19" {{ $profileData->rank == 19 ? 'selected' : '' }}>19</option>
                                            <option value="20" {{ $profileData->rank == 20 ? 'selected' : '' }}>20</option>
                                            <option value="21" {{ $profileData->rank == 21 ? 'selected' : '' }}>21</option>
                                            <option value="23" {{ $profileData->rank == 23 ? 'selected' : '' }}>23</option>
                                            <option value="24" {{ $profileData->rank == 24 ? 'selected' : '' }}>24</option>
                                            <option value="25" {{ $profileData->rank == 25 ? 'selected' : '' }}>25</option>
                                            <option value="26" {{ $profileData->rank == 26 ? 'selected' : '' }}>26</option>
                                            <option value="27" {{ $profileData->rank == 27 ? 'selected' : '' }}>27</option>
                                            <option value="28" {{ $profileData->rank == 28 ? 'selected' : '' }}>28</option>
                                            <option value="29" {{ $profileData->rank == 29 ? 'selected' : '' }}>29</option>
                                            <option value="30" {{ $profileData->rank == 30 ? 'selected' : '' }}>30</option>
                                            <option value="31" {{ $profileData->rank == 31 ? 'selected' : '' }}>31</option>
                                            <option value="32" {{ $profileData->rank == 32 ? 'selected' : '' }}>32</option>
                                            <option value="34" {{ $profileData->rank == 34 ? 'selected' : '' }}>34</option>
                                            <option value="35" {{ $profileData->rank == 35 ? 'selected' : '' }}>35</option>
                                            <option value="36" {{ $profileData->rank == 36 ? 'selected' : '' }}>36</option>
                                            <option value="37" {{ $profileData->rank == 37 ? 'selected' : '' }}>37</option>
                                            <option value="38" {{ $profileData->rank == 38 ? 'selected' : '' }}>38</option>
                                            <option value="39" {{ $profileData->rank == 39 ? 'selected' : '' }}>39</option>
                                            <option value="40" {{ $profileData->rank == 40 ? 'selected' : '' }}>40</option>
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
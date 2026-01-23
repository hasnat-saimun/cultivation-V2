@extends('cultivation.include')
@section('backTitle')
Edit Profile
@endsection
@section('backIndex')
@push('styles')
<style>
    .form-card{border:0;border-radius:14px;overflow:hidden;box-shadow:0 14px 34px rgba(23,43,77,.12);background:#fff}
    .form-card .card-header{background:linear-gradient(120deg,#1f3047,#233958);color:#fff;border:0;padding:1.25rem 1.5rem}
    .form-card .card-body{padding:1.5rem}
    .form-card h5{color:#fff;margin:0;font-weight:700}
    .form-card small{color:rgba(255,255,255,.7)}
    .form-divider{font-weight:700;color:#1f3047;margin:14px 0 10px;display:flex;align-items:center;gap:.5rem}
    .form-divider .badge{background:#e6edff;color:#1f3047;font-weight:600;letter-spacing:.2px}
    .form-label-required::after{content:"*";color:#ff6b6b;margin-left:4px}
    .control-help{font-size:.9rem;color:#6c757d}
    .new-added-form .form-group label{font-weight:600;color:#233958}
    .new-added-form .form-control,.new-added-form select,.new-added-form .select2-container--default .select2-selection--single{border-radius:10px}
    .new-added-form .select2-container--default .select2-selection--single{height:42px!important;border-color:#ced4da}
    .new-added-form .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:42px!important}
    .new-added-form .select2-container--default .select2-selection--single .select2-selection__arrow{height:42px!important}
    .avatar-upload{border:1px dashed #cdd5e5;border-radius:12px;padding:18px;text-align:center;background:#f9fbff}
    .avatar-upload input[type=file]{display:inline-block;margin-top:10px}
    .btn-soft-light{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.18)}
    .btn-soft-light:hover{background:rgba(255,255,255,.18);color:#fff}
    .header-actions,.gap-2{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem}
</style>
@endpush
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card form-card height-auto">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1">Edit Teacher Profile</h5>
                                <small>Update personal details, designation, and contact information.</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{route('teacherList')}}" class="btn btn-soft-light btn-sm"><i class="fas fa-list mr-1"></i>Teacher List</a>
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
                            <div class="form-divider"><span class="badge">Identity</span>Basic information</div>
                            @if(!empty($profileData))
                            <form action="{{ route('updateTeacherPhoto') }}" class="form" enctype="multipart/form-data" method="POST">
                    @csrf
                    <input type="hidden" value="{{ $profileData->id }}" name="profileId" />
                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-12 form-group mg-t-30">
                            @if(!empty($profileData->avatar))
                            <div class="avatar-upload">
                                <div><img class="w-75" src="{{ asset('/public/upload/image/teacher/') }}/{{$profileData->avatar}}" alt="$profileData->insName" /><br /></div>
                                <a href="{{route('delTeacherPhoto',['profileId'=>$profileData->id])}}" class="mt-3 w-75 btn btn-danger btn-lg">Remove</a>
                            </div>
                            @else
                            <div class="avatar-upload">
                                <label class="text-dark-medium d-block mb-2">Avatar (150px X 150px)</label>
                                <p class="control-help mb-2">Square image, JPG/PNG up to 1MB.</p>
                                <input type="file" name="avatar" class="form-control-file" />
                                <div class="mt-4"><input type="submit" value="Update" class="btn btn-primary" /></div>
                            </div>
                            @endif
                        </div>
                    </div>
                </form>
                            <form class="new-added-form" action="{{ route('updateTeacher') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" value="{{ $profileData->id }}" name="teacherId">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Teacher ID</label>
                                        <input type="text" value="{{ $profileData->teacherId }}" readonly class="form-control">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Full Name</label>
                                        <input type="text" name="firstName" placeholder="Enter first name" class="form-control" value="{{ $profileData->firstName }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Sure Name</label>
                                        <input type="text" name="lastName" placeholder="Enter last name" class="form-control" value="{{ $profileData->lastName }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Father's Name</label>
                                        <input type="text" name="fathersName" placeholder="Enter fathers name" class="form-control" value="{{ $profileData->fathersName }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Mother's Name</label>
                                        <input type="text" name="mothersName" placeholder="Enter mothers name" class="form-control" value="{{ $profileData->mothersName }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Gender</label>
                                        <select class="select2" name="gender">
                                            <option value="1" {{ $profileData->gender == 1 ? 'selected' : '' }}>Male</option>
                                            <option value="2" {{ $profileData->gender == 2 ? 'selected' : '' }}>Female</option>
                                            <option value="3" {{ $profileData->gender == 3 ? 'selected' : '' }}>Others</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Date Of Birth</label>
                                        <input type="date" name="dob" placeholder="dd/mm/yyyy" class="form-control " value="{{ $profileData->dob }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Designation</label>
                                        <select class="select2" name="designation">
                                            <option value="">Select Designation</option>
                                            @if(isset($designations) && $designations->count() > 0)
                                                @foreach($designations as $desig)
                                                    <option value="{{ $desig->id }}" {{ $profileData->designation_id == $desig->id ? 'selected' : '' }}>{{ $desig->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Join Date</label>
                                        <input type="date" name="joinDate" placeholder="mm/dd/yyyy" class="form-control" value="{{ $profileData->joinDate }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Blood Group</label>
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
                                        <label class="form-label-required">Religion</label>
                                        <select class="select2" name="religion">
                                            <option value="1" {{ $profileData->religion == 1 ? 'selected' : '' }}>Islam</option>
                                            <option value="2" {{ $profileData->religion == 2 ? 'selected' : '' }}>Hindu</option>
                                            <option value="3" {{ $profileData->religion == 3 ? 'selected' : '' }}>Christian</option>
                                            <option value="4" {{ $profileData->religion == 4 ? 'selected' : '' }}>Buddish</option>
                                            <option value="5" {{ $profileData->religion == 5 ? 'selected' : '' }}>Others</option>
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
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Address</label>
                                        <input type="text" class="form-control" placeholder="Enter full address" value="{{ $profileData->address }}" name="address">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>MPO Index</label>
                                        <input type="text" name="mpoIndex" placeholder="Enter MPO Index" class="form-control" value="{{ $profileData->mpoIndex }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>PDS ID</label>
                                        <input type="text" name="pdsId" placeholder="Enter PDS ID" class="form-control" value="{{ $profileData->pdsId }}">
                                    </div>
                                    
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label class="form-label-required">Ranking</label>
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
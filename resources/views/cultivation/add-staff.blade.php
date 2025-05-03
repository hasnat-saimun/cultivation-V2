@extends('cultivation.include')
@section('backTitle')
New Profile
@endsection
@section('backIndex')
@php
    $serverData = \App\Models\ServerConfig::orderBy('id','DESC')->limit(1)->first();
    if(!empty($serverData)):
        $serverId           = $serverData->id;
        $insName            = $serverData->institueName;
        $location           = $serverData->address;
        $stdIdPrefix        = $serverData->studentIdPrefix;
        $teacherIdPrefix    = $serverData->teacherIdPrefix;
        $staffIdPrefix      = $serverData->staffIdPrefix;
    else:
        $serverId           = "";
        $insName            = "Sonar Bangla College";
        $location           = "Poyat, Burichong, Cumilla";
        $stdIdPrefix        = "SBCSTID";
        $teacherIdPrefix    = "SBCTID";
        $staffIdPrefix      = "SBCSTFID";
    endif;
@endphp
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
                                    <h3>New Profile Creation</h3>
                                </div>
                            </div>
                            <form class="new-added-form" action="{{ route('confirmStaff') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Staff ID *</label>
                                        <input type="text" name="staffId" value="{{ $staffIdPrefix }}-@if(empty($chk)) 1 @else {{ $chk->id+1 }} @endif" placeholder="Example: {{ $staffIdPrefix }}-127420" class="form-control" required readonly>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Full Name *</label>
                                        <input type="text" name="firstName" placeholder="Enter first name" class="form-control" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Sure Name *</label>
                                        <input type="text" name="lastName" placeholder="Enter last name" class="form-control" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Father's Name *</label>
                                        <input type="text" name="fathersName" placeholder="Enter fathers name" class="form-control" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Mother's Name *</label>
                                        <input type="text" name="mothersName" placeholder="Enter mothers name" class="form-control" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Gender *</label>
                                        <select class="select2" name="gender" required>
                                            <option value="">Select *</option>
                                            <option value="1">Male</option>
                                            <option value="2">Female</option>
                                            <option value="3">Others</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Date Of Birth *</label>
                                        <input type="date" name="dob" placeholder="dd/mm/yyyy" class="form-control" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Designation *</label>
                                        <select class="select2" name="designation" required>
                                            <option value="">Select *</option>
                                            <option value="1">Principal</option>
                                            <option value="2">Vice Principal</option>
                                            <option value="3">Teacher</option>
                                            <option value="4">Staff</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Blood Group *</label>
                                        <select class="select2" name="blGroup">
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
                                        <select class="select2" name="religion" required>
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
                                        <input type="email" name="staffMail" placeholder="Enter email address" class="form-control">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Join Date</label>
                                        <input type="date" name="joinDate" placeholder="mm/dd/yyy" class="form-control" required>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Phone</label>
                                        <input type="text" name="mobile" placeholder="Enter mobile number" class="form-control">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Address</label>
                                        <input type="text" class="form-control" placeholder="Enter full address" name="address">
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group mg-t-30">
                                        <label class="text-dark-medium">Avatar (150px X 150px)</label>
                                        <input type="file" name="avatar" class="form-control-file">
                                    </div>
                                    <div class="col-12 form-group mg-t-8">
                                        <button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Save</button>
                                        <button type="reset" class="btn-fill-lg bg-blue-dark btn-hover-bluedark">Reset</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
@endsection
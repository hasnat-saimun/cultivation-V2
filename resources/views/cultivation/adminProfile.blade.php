@php
    $cultivationAdmin = \App\Models\CultivationAdmin::orderBy('id','DESC')->limit(1)->first();
    if(!empty($cultivationAdmin)):
        $adminName      =   $cultivationAdmin->adminName;
        $adminId        =   $cultivationAdmin->id;
        $adminEmail     =   $cultivationAdmin->adminMail;
        $userId         =   $cultivationAdmin->adminUser;
        $adminMobile    =   $cultivationAdmin->adminMobile;
        $adminType      =   $cultivationAdmin->adminType;
    else:
        $adminId        =   null;
        $adminName      =   "Abu Yousuf";
        $adminEmail     =   "cultivation@virtualitprofessional.com";
        $userId         =   "Spark Coder";
        $adminMobile    =   "01678909091";
        $adminType      =   "Admin";
    endif;
@endphp
@extends('cultivation.include')
@section('backTitle')
Admin Profile
@endsection
@section('backIndex')
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-md-10 col-12 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="fa-duotone fa-user"></i> Admin Profile
            </div>
            <div class="card-body cultivation">
                @if(session()->has('success'))
                    <div class="alert alert-success w-100">
                        {{ session()->get('success') }}
                    </div>
                @endif
                @if(session()->has('error'))
                    <div class="alert alert-warning w-100">
                        {{ session()->get('error') }}
                    </div>
                @endif
                <section class="row">
                    <div class="col-md-6 col-12">
                        <div class="card-title">
                        <i class="fa-light fa-id-card"></i> Details Update
                        </div>
                        <form action="{{ route('saveAdminProfile') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="adminId" value="{{ $adminId }}">
                            <div class="mb-3">
                                <label for="adminName" class="form-label">Admin Name</label>
                                <input type="text" name="adminName" class="form-control" id="adminName" value="{{ $adminName }}" placeholder="Enter the full name of the admin" required>
                            </div>
                            <div class="mb-3">
                                <label for="adminEmail" class="form-label">Admin Email</label>
                                <input type="text" name="adminEmail" class="form-control" id="adminEmail" value="{{ $adminEmail }}" placeholder="Enter admin email address" required>
                            </div>
                            <div class="mb-3">
                                <label for="adminMobile" class="form-label">Admin Mobile</label>
                                <input type="text" name="adminMobile" class="form-control" id="adminMobile" value="{{ $adminMobile }}" placeholder="Enter admin mobile number" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">Save Profile</button>
                        </form>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="card-title">
                            <i class="fa-duotone fa-key"></i> Password Update
                        </div>
                        <form action="{{ route('changeAdminPassword') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="adminId" value="{{ $adminId }}">
                            <div class="mb-3">
                                <label for="oldPassword" class="form-label">Old Password</label>
                                <input type="password" autocomplete="new-password" name="oldPassword" class="form-control" id="oldPassword" placeholder="Enter old password" required>
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password"  name="newPassword" class="form-control" id="newPassword" placeholder="Enter new password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input type="password"  name="confirmPassword" class="form-control" id="confirmPassword" placeholder="Confirm new password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">Change Password</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection
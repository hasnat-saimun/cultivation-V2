@extends('cultivation.include')
@section('backTitle')
Register Form
@endsection
@section('backIndex')

<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-md-10 col-12 mx-auto">
        <div class="card">
            <div class="card-header bg-light">
                <a href="{{ route('registerList') }}" class="btn btn-success"> Registered List</a>
            </div>
            <div class="card-header">
                <i class="fa-duotone fa-toolbox"></i> User Register Form
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
                    
                    @error('insLogo')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                <form action="{{ route('saveUser') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                            <div class="col-6 mb-3">
                                <label for="adminName" class="form-label">Admin Name</label>
                                <input type="text" name="adminName" class="form-control" id="adminName"  placeholder="Enter the admin name" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="userName" class="form-label">User Name</label>
                                <input type="text" name="userName" class="form-control" id="userName"  placeholder="Enter the user name" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="userMobile" class="form-label">User Mobile</label>
                                <input type="text" name="userMobile" class="form-control" id="userMobile" placeholder="Enter user mobile number" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="userMail" class="form-label">User Email</label>
                                <input type="text" name="userMail" class="form-control" id="userMail" placeholder="Enter user email address" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">User Type</label>
                                <select class="form-select" id="userType" onchange="userSelect()" name="userType" required>
                                    <option value="">Select</option>
                                    <option value="1">Teacher Admin</option>
                                    <option value="2">Cash Admin</option>
                                    <option value="3">General Admin</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="pass" class="form-label">User Password</label>
                                <input type="password" name="pass" class="form-control" id="pass" placeholder="Enter the password" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="confirmPass" class="form-label">Confirm Password</label>
                                <input type="password" name="confirmPass" class="form-control" id="confirmPass" placeholder="Enter the confirm password" required>
                            </div>
                        </div>
                        <div id="accessBox" class="row p-4 d-none">
                            <div class="col-6 mb-3">
                                <label class="form-label">Assign Class</label>
                                @if($classList->count() > 0)
                                    @foreach($classList as $cls)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $cls->id }}" id="cls{{ $cls->id }}" name="className[]">
                                            <label class="form-check-label" for="cls{{ $cls->id }}">
                                            {{ $cls->className }}
                                            </label>
                                        </div>
                                    @endforeach 
                                @endif
                            </div>
                            <div id="subjectBox" class="col-6 mb-3">
                                @if($subjectList)
                                    <label class="form-label">Assign Subject</label>
                                    @foreach($subjectList as $sub)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $sub->id }}" id="sub{{ $sub->id }}" name="subject[]">
                                            <label class="form-check-label" for="sub{{ $sub->id }}">
                                            {{ $sub->subjectName }} ( {{ $sub->subjectType }} )
                                            </label>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    <button type="submit" class="mt-4 btn btn-primary btn-lg">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->

<script>
    function userSelect() {
        console.log('hasnat')
        var str   = document.getElementById('userType').value;
        if(str == "") {
            $("#accessBox").addClass("d-none");
            document.getElementById("accessBox").classList.add = "d-none";
        }
        if(str == 1) {
            $("#accessBox").removeClass("d-none");
        }else{
            $("#accessBox").addClass("d-none");
            document.getElementById("accessBox").classList.add = "d-none";
        }
    }

    
    function classSelect() {
        console.log('hasnat')
        var str   = document.getElementById('classType').value;
        if(str == "") {
            $("#subjectBox").addClass("d-none");
            document.getElementById("subjectBox").classList.add = "d-none";
        }
        if(str == 1) {
            $("#subjectBox").removeClass("d-none");
        }else{
            $("#subjectBox").addClass("d-none");
            document.getElementById("subjectBox").classList.add = "d-none";
        }
    }
</script>
@endsection
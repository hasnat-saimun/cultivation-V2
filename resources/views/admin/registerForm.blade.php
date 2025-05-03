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
                <a href="{{ route('registerList') }}" class="btn btn-success">Registered List</a>
            </div>
            <div class="card-header">
                <i class="fa-duotone fa-toolbox"></i> Register Form
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
                <form action="{{ route('saveRegForm') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="insName" class="form-label">Institute Name</label>
                                <input type="text" name="insName" class="form-control" id="insName"  placeholder="Enter the name of the institute" required>
                            </div>
                            <div class="mb-3">
                                <label for="insAddress" class="form-label">Address</label>
                                <input type="text" name="insAddress" class="form-control" id="insAddress"  placeholder="Enter institute address" required>
                            </div>
                            <div class="mb-3">
                                <label for="officeMobile" class="form-label">Official Mobile</label>
                                <input type="text" name="officeMobile" class="form-control" id="officeMobile" placeholder="Enter office mobile number" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="webName" class="form-label">Web Name</label>
                                <input type="text" name="webName" class="form-control" id="webName"  placeholder="Choose web site name" required>
                            </div>
                            <div class="mb-3">
                                <label for="officeMail" class="form-label">Official Email</label>
                                <input type="text" name="officeMail" class="form-control" id="officeMail" placeholder="Enter office email address" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label class="text-dark-medium">Logo</label>
                                <input type="file" name="insLogo" class="form-control-file" id="insLogo" >
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="mt-4 btn btn-primary btn-lg">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection
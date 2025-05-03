@extends('cultivation.include') @section('backTitle') Registered Edit @endsection @section('backIndex')

<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-md-10 col-12 mx-auto">
        <div class="card">
            <div class="card-header bg-light">
                <a href="{{ route('registerList') }}" class="btn btn-success">Registered List</a>
            </div>
            <div class="card-header"><i class="fa-duotone fa-toolbox"></i> Register Form</div>
            <div class="card-body cultivation">
                @if(session()->has('success'))
                <div class="alert alert-success w-100">
                    {{ session()->get('success') }}
                </div>
                @endif @if(session()->has('error'))
                <div class="alert alert-warning w-100">
                    {{ session()->get('error') }}
                </div>
                @endif
                @if(!empty($regData))
                <form action="{{ route('registerLogoUpdate') }}" class="form" enctype="multipart/form-data" method="POST">
                    @csrf
                    <input type="hidden" value="{{ $regData->id }}" name="regId" />
                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-12 form-group mg-t-30">
                            @if(!empty($regData->insLogo))
                            <div><img class="w-75" src="{{ asset('/public/upload/image/registerLogo/') }}/{{$regData->insLogo}}" alt="$regData->insName" /><br /></div>
                            <a href="{{route('registerLogoDel',['regId'=>$regData->id])}}" class="mt-3 w-75 btn btn-danger btn-lg">Remove</a>
                            @else
                            <label class="text-dark-medium">Avatar (150px X 150px)</label>
                            <input type="file" name="insLogo" class="form-control-file" />
                            <div class="mt-4"><input type="submit" value="Update" class="btn btn-primary" /></div>
                            @endif
                        </div>
                    </div>
                </form>
                <form action="{{ route('registerUpdate') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="regId" value="{{ $regData->id }}" />
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="insName" class="form-label">Institute Name</label>
                                <input type="text" name="insName" class="form-control" id="insName" value="{{ $regData->insName }}" placeholder="Enter the name of the institute" required />
                            </div>
                            <div class="mb-3">
                                <label for="insAddress" class="form-label">Address</label>
                                <input type="text" name="insAddress" class="form-control" id="insAddress" value="{{ $regData->insAddress }}" placeholder="Enter institute address" required />
                            </div>
                            <div class="mb-3">
                                <label for="officeMobile" class="form-label">Official Mobile</label>
                                <input type="text" name="officeMobile" class="form-control" id="officeMobile" value="{{ $regData->officeMobile }}" placeholder="Enter office mobile number" required />
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="webName" class="form-label">Web Name</label>
                                <input type="text" name="webName" class="form-control" id="webName" value="{{ $regData->webName }}" placeholder="Choose web site name" required />
                            </div>
                            <div class="mb-3">
                                <label for="officeMail" class="form-label">Official Email</label>
                                <input type="text" name="officeMail" class="form-control" id="officeMail" value="{{ $regData->officeMail }}" placeholder="Enter office email address" required />
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="mt-4 btn btn-primary btn-lg">Save</button>
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
</div>
<!-- Dashboard summery End Here -->
@endsection

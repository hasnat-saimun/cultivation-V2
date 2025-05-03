@extends('cultivation.include')
@section('backTitle')
Configuration
@endsection
@section('backIndex')
@php
    $serverData = \App\Models\ServerConfig::orderBy('id','DESC')->limit(1)->first();
    if(!empty($serverData)):
        $serverId           = $serverData->id;
        $insName            = $serverData->institueName;
        $location           = $serverData->address;
        $officeMobile       = $serverData->officeMobile;
        $officeMail         = $serverData->officeEmail;
        $principalMail      = $serverData->principalMail;
        $principalMobile    = $serverData->principalMobile;
        $principalName      = $serverData->principalName;
        $principalSign      = $serverData->principalSign;
        $logo               = $serverData->logo;
        $favicon            = $serverData->favicon;
        $avatar             = $serverData->avatar;
        $fbPage             = $serverData->facebookPage;
        $twitterLink        = $serverData->twitterLink;
        $youtubeLink        = $serverData->youtubeChanel;
        $einNumber          = $serverData->einNumber;
        $studentIdPrefix    = $serverData->studentIdPrefix;
        $teacherIdPrefix    = $serverData->teacherIdPrefix;
        $staffIdPrefix      = $serverData->staffIdPrefix;
        $establishDate      = $serverData->establishDate;
    else:
        $serverId           = "";
        $insName            = "";
        $location           = "";
        $officeMobile       = "";
        $officeMail         = "";
        $principalMail      = "";
        $principalMobile    = "";
        $principalName      = "";
        $principalSign      = "";
        $logo               = "";
        $favicon            = "";
        $avatar             = "";
        $fbPage             = "";
        $twitterLink        = "";
        $youtubeLink        = "";
        $einNumber          = "";
        $studentIdPrefix    = "ID";
        $teacherIdPrefix    = "ID";
        $staffIdPrefix      = "ID";
        $establishDate      = "";
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-md-10 col-12 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="fa-duotone fa-toolbox"></i> Configuration
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
                <form action="{{ route('saveConfig') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="serverId" value="{{ $serverId }}">
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="insName" class="form-label">Institute Name</label>
                                <input type="text" name="insName" class="form-control" id="insName" value="{{ $insName }}" placeholder="Enter the name of the institute" required>
                            </div>
                            <div class="mb-3">
                                <label for="insAddress" class="form-label">Address</label>
                                <input type="text" name="insAddress" class="form-control" id="insAddress" value="{{ $location }}" placeholder="Enter institute address" required>
                            </div>
                            <div class="mb-3">
                                <label for="einNumber" class="form-label">EIN Number</label>
                                <input type="text" name="einNumber" class="form-control" id="einNumber" value="{{ $einNumber }}" placeholder="Enter institute EIN Number" required>
                            </div>
                            <div class="mb-3">
                                <label for="officeMobile" class="form-label">Official Mobile</label>
                                <input type="text" name="officeMobile" class="form-control" id="officeMobile" value="{{ $officeMobile }}" placeholder="Enter office mobile number" required>
                            </div>
                            <div class="mb-3">
                                <label for="establishDate" class="form-label">Establish Date</label>
                                <input type="text" name="establishDate" class="form-control" id="establishDate" value="{{ $establishDate }}" placeholder="Enter establish date">
                            </div>
                            <div class="mb-3">
                                <label for="youtubeChanel" class="form-label">Youtube Chanel</label>
                                <input type="text" name="youtubeChanel" class="form-control" id="youtubeChanel" value="{{ $youtubeLink }}" placeholder="Enter youtube chanel link">
                            </div>
                            <!-- <div class="mb-3">
                                <label for="studentIdPrefix" class="form-label">Student ID Prefix</label>
                                <input type="text" name="studentIdPrefix" class="form-control" id="studentIdPrefix" value="{{ $studentIdPrefix }}" placeholder="Example: STDID" required>
                            </div>
                            <div class="mb-3">
                                <label for="teacherIdPrefix" class="form-label">Teacher ID Prefix</label>
                                <input type="text" name="teacherIdPrefix" class="form-control" id="teacherIdPrefix" value="{{ $teacherIdPrefix }}" placeholder="Example: TCRID" required>
                            </div>
                            <div class="mb-3">
                                <label for="staffIdPrefix" class="form-label">Staff ID Prefix</label>
                                <input type="text" name="staffIdPrefix" class="form-control" id="staffIdPrefix" value="{{ $staffIdPrefix }}" placeholder="Example: STFID" required>
                            </div> -->
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="principalName" class="form-label">Principal Name</label>
                                <input type="text" name="principalName" class="form-control" id="principalName" value="{{ $principalName }}" placeholder="Enter the name of the principal" required>
                            </div>
                            <div class="mb-3">
                                <label for="principalMobile" class="form-label">Principal Mobile</label>
                                <input type="text" name="principalMobile" class="form-control" id="principalMobile" value="{{ $principalMobile }}" placeholder="Enter principal mobile number" required>
                            </div>
                            <div class="mb-3">
                                <label for="principalMail" class="form-label">Principal Email</label>
                                <input type="text" name="principalMail" class="form-control" id="principalMail" value="{{ $principalMail }}" placeholder="Enter principal email address" required>
                            </div>
                            <div class="mb-3">
                                <label for="officeMail" class="form-label">Official Email</label>
                                <input type="text" name="officeMail" class="form-control" id="officeMail" value="{{ $officeMail }}" placeholder="Enter office email address" required>
                            </div>
                            <div class="mb-3">
                                <label for="facebookPage" class="form-label">Facebook Page</label>
                                <input type="text" name="facebookPage" class="form-control" id="facebookPage" value="{{ $fbPage }}" placeholder="Enter facebook page link here">
                            </div>
                            <div class="mb-3">
                                <label for="twitterLink" class="form-label">Twitter Profile</label>
                                <input type="text" name="twitterLink" class="form-control" id="twitterLink" value="{{ $twitterLink }}" placeholder="Enter twitter profile Link">
                            </div>
                        </div>
                    </div>
                    @if(empty($serverId))
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label class="text-dark-medium">Logo</label>
                                <input type="file" name="insLogo" class="form-control-file" id="insLogo" required>
                            </div>
                            <div class="mb-3">
                                <label for="favicon" class="form-label">Favicon</label>
                                <input type="file" name="favicon" class="form-control-file" id="favicon" required>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="adminPhoto" class="form-label">Admin Photo</label>
                                <input type="file" name="adminPhoto" class="form-control-file" id="adminPhoto" required>
                            </div>
                            <div class="mb-3">
                                <label for="principalSign" class="form-label">Principal Sign</label>
                                <input type="file" name="principalSign" class="form-control-file" id="principalSign" required>
                            </div>
                        </div>
                    </div>
                    @endif
                    <button type="submit" class="mt-4 btn btn-primary btn-lg">Save</button>
                </form>
                @if(!empty($serverId))
                <div class="row mt-4">
                    <div class="col-md-6 col-12">
                        <div class="mb-3">
                            <label for="insLogo" class="form-label fw-bold">Logo</label>
                            @if(empty($logo))
                            <form class="form" action="{{ route('saveLogo') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="serverId" value="{{ $serverId }}">
                                <input type="file" name="insLogo" class="form-control-file" id="insLogo" required>
                                <button type="submit" class="btn btn-primary btn-lg mt-4">Update</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="w-25 bg-success" src="{{ asset('public') }}\upload\image\cultivation\{{ $logo }}" alt="{{ $insName }}">
                                <div>
                                    <a href="{{ route('delLogo',['id'=>$serverId]) }}">Delete</a>
                                </div>
                            </div>
                            @endif
                        </div>  
                        <div class="mb-3">
                            <label for="favicon" class="form-label fw-bold">Favicon</label>
                            @if(empty($favicon))
                            <form class="form" action="{{ route('saveFavicon') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="serverId" value="{{ $serverId }}">
                                <input type="file" name="favicon" class="form-control-file" id="favicon">
                                <button type="submit" class="btn btn-primary btn-lg mt-4">Update</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="w-25" src="{{ asset('public') }}\upload\image\cultivation\{{ $favicon }}" alt="{{ $insName }}">
                                <div>
                                    <a href="{{ route('delFavicon',['id'=>$serverId]) }}">Delete</a>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="mb-3">
                            <label for="adminPhoto" class="form-label fw-bold">Admin Photo</label>
                            @if(empty($avatar))
                            <form class="form" action="{{ route('saveAvatar') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="serverId" value="{{ $serverId }}"class="form-control-file" >
                                <input type="file" name="adminPhoto" id="adminPhoto" class="form-control-file" required>
                                <button type="submit" class="btn btn-primary btn-lg mt-4">Update</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="w-25" src="{{ asset('public') }}\upload\image\cultivation\{{ $avatar }}" alt="{{ $insName }}">
                                <div>
                                    <a href="{{ route('delAvatar',['id'=>$serverId]) }}">Delete</a>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="principalSign" class="form-label fw-bold">Principal Sign</label>
                            @if(empty($principalSign))
                            <form class="form" action="{{ route('saveSign') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="serverId" value="{{ $serverId }}">
                                <input type="file" name="principalSign" class="form-control" id="principalSign" required>
                                <button type="submit" class="btn btn-primary btn-lg mt-4">Update</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="w-25" src="{{ asset('public') }}\upload\image\cultivation\{{ $principalSign }}" alt="{{ $insName }}">
                                <div>
                                    <a href="{{ route('delSign',['id'=>$serverId]) }}">Delete</a>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <!-- logo section -->
            @endif
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection
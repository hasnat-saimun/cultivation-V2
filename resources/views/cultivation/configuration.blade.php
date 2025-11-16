@extends('cultivation.include')
@section('backTitle')
Configuration
@endsection
@section('backIndex')
@push('styles')
<style>
    .section-title { font-weight:600; }
    .form-hint { font-size:.85rem; color:#6c757d; }
    .btn-soft { background:#f1f3f5; border-color:#f1f3f5; color:#495057; }
    .btn-soft:hover { background:#e9ecef; border-color:#e9ecef; }
    .divider { height:1px; background:#e9ecef; margin: 1rem 0; }
    .config-header { background:#f8f9fa; border-radius:.5rem; padding: .75rem 1rem; }
    .preview-thumb { max-width: 160px; border-radius: .25rem; }
</style>
@endpush
@php
    $serverData = \App\Models\ServerConfig::orderBy('id','DESC')->limit(1)->first();
    if(!empty($serverData)):
        $serverId               = $serverData->id;
        $insName                = $serverData->instituteName;
        $location               = $serverData->address;
        $officeMobile           = $serverData->officeMobile;
        $officeMail             = $serverData->officeEmail;
        $principalMail          = $serverData->principalMail;
        $principalDesignation   = $serverData->principalDesignation;
        $principalMobile        = $serverData->principalMobile;
        $principalName          = $serverData->principalName;
        $principalSign          = $serverData->principalSign;
        $logo                   = $serverData->logo;
        $favicon                = $serverData->favicon;
        $avatar                 = $serverData->avatar;
        $fbPage                 = $serverData->facebookPage;
        $twitterLink            = $serverData->twitterLink;
        $youtubeLink            = $serverData->youtubeChanel;
        $einNumber              = $serverData->einNumber;
        $studentIdPrefix        = $serverData->studentIdPrefix;
        $teacherIdPrefix        = $serverData->teacherIdPrefix;
        $staffIdPrefix          = $serverData->staffIdPrefix;
        $establishDate          = $serverData->establishDate;
        $eduMinName             = $serverData->eduMinName;
        $boardChairmanName      = $serverData->boardChairmanName;
        $eduMinImg              = $serverData->eduMinImg;
        $boardChairmanImg       = $serverData->boardChairmanImg;
        $mapEmbed               = $serverData->mapEmbed;
    else:
        $serverId               = "";
        $insName                = "";
        $location               = "";
        $officeMobile           = "";
        $officeMail             = "";
        $principalMail          = "";
        $principalMobile        = "";
        $principalDesignation   = "";
        $principalName          = "";
        $principalSign          = "";
        $logo                   = "";
        $favicon                = "";
        $avatar                 = "";
        $fbPage                 = "";
        $twitterLink            = "";
        $youtubeLink            = "";
        $einNumber              = "";
        $studentIdPrefix        = "ID";
        $teacherIdPrefix        = "ID";
        $staffIdPrefix          = "ID";
        $establishDate          = "";
        $eduMinName             = "";
        $boardChairmanName      = "";
        $eduMinImg              = "";
        $boardChairmanImg       = "";
        $mapEmbed               = "";
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-md-10 col-12 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-toolbox"></i> Configuration
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
                    @error('principalSign')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    @error('insLogo')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    @error('favicon')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    @error('adminPhoto')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    @error('eduMinImg')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    @error('boardChairmanImg')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                <form action="{{ route('saveConfig') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="serverId" value="{{ $serverId }}">
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="insName" class="form-label">Institute Name</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-school"></i></span></div>
                                    <input type="text" name="insName" class="form-control" id="insName" value="{{ $insName }}" placeholder="Enter the name of the institute" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="insAddress" class="form-label">Address</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span></div>
                                    <input type="text" name="insAddress" class="form-control" id="insAddress" value="{{ $location }}" placeholder="Enter institute address" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="einNumber" class="form-label">EIN Number</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-hashtag"></i></span></div>
                                    <input type="text" name="einNumber" class="form-control" id="einNumber" value="{{ $einNumber }}" placeholder="Enter institute EIN Number" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="officeMobile" class="form-label">Official Mobile</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div>
                                    <input type="text" name="officeMobile" class="form-control" id="officeMobile" value="{{ $officeMobile }}" placeholder="Enter office mobile number" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="establishDate" class="form-label">Establish Date</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar"></i></span></div>
                                    <input type="text" name="establishDate" class="form-control" id="establishDate" value="{{ $establishDate }}" placeholder="Enter establish date">
                                </div>
                                <div class="form-hint">Format: YYYY-MM-DD or a readable date.</div>
                            </div>
                            <div class="mb-3">
                                <label for="youtubeChanel" class="form-label">Youtube Channel</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-youtube"></i></span></div>
                                    <input type="text" name="youtubeChanel" class="form-control" id="youtubeChanel" value="{{ $youtubeLink }}" placeholder="Enter YouTube channel link">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="eduMinName" class="form-label">Education Minister Name</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
                                    <input type="text" name="eduMinName" class="form-control" id="eduMinName" value="{{ $eduMinName }}" placeholder="Enter the education minister name">
                                </div>
                            </div>
                            <!-- <div class="mb-3">
                                <label for="studentIdPrefix" class="form-label">Student ID Prefix</label>
                                <input type="text" name="studentIdPrefix" class="form-control" id="studentIdPrefix" value="{{ $studentIdPrefix }}" placeholder="Example: STDID" >
                            </div>
                            <div class="mb-3">
                                <label for="teacherIdPrefix" class="form-label">Teacher ID Prefix</label>
                                <input type="text" name="teacherIdPrefix" class="form-control" id="teacherIdPrefix" value="{{ $teacherIdPrefix }}" placeholder="Example: TCRID" >
                            </div>
                            <div class="mb-3">
                                <label for="staffIdPrefix" class="form-label">Staff ID Prefix</label>
                                <input type="text" name="staffIdPrefix" class="form-control" id="staffIdPrefix" value="{{ $staffIdPrefix }}" placeholder="Example: STFID" >
                            </div> -->
                            <div class="mb-3">
                                <label for="boardChairmanName" class="form-label">Board Chairman Name</label>
                                <input type="text" name="boardChairmanName" class="form-control" id="boardChairmanName" value="{{ $boardChairmanName }}" placeholder="Enter board chairman name">
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="principalName" class="form-label">Principal Name</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user-tie"></i></span></div>
                                    <input type="text" name="principalName" class="form-control" id="principalName" value="{{ $principalName }}" placeholder="Enter the name of the principal" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="principalDesignation" class="form-label">Principal Designation</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-id-badge"></i></span></div>
                                    <input type="text" name="principalDesignation" class="form-control" id="principalDesignation" value="{{ $principalDesignation }}" placeholder="Enter the current designation of the principal" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="principalMobile" class="form-label">Principal Mobile</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div>
                                    <input type="text" name="principalMobile" class="form-control" id="principalMobile" value="{{ $principalMobile }}" placeholder="Enter principal mobile number" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="principalMail" class="form-label">Principal Email</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-envelope"></i></span></div>
                                    <input type="text" name="principalMail" class="form-control" id="principalMail" value="{{ $principalMail }}" placeholder="Enter principal email address" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="officeMail" class="form-label">Official Email</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-envelope"></i></span></div>
                                    <input type="text" name="officeMail" class="form-control" id="officeMail" value="{{ $officeMail }}" placeholder="Enter office email address" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="facebookPage" class="form-label">Facebook Page</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-facebook"></i></span></div>
                                    <input type="text" name="facebookPage" class="form-control" id="facebookPage" value="{{ $fbPage }}" placeholder="Enter facebook page link here">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="twitterLink" class="form-label">Twitter Profile</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-twitter"></i></span></div>
                                    <input type="text" name="twitterLink" class="form-control" id="twitterLink" value="{{ $twitterLink }}" placeholder="Enter twitter profile Link">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="mapEmbed" class="form-label">Google Map</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-map"></i></span></div>
                                    <input type="text" name="mapEmbed" class="form-control" id="mapEmbed" value="{{ $mapEmbed }}" placeholder="Enter google map embed pb value from iframe">
                                </div>
                                <div class="form-hint">Paste the numeric pb value from the embed iframe URL.</div>
                            </div>
                        </div>
                    </div>
                    @if(empty($serverId))
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label class="text-dark-medium">Logo</label>
                                <input type="file" name="insLogo" class="form-control-file" id="insLogo" >
                            </div>
                            <div class="mb-3">
                                <label for="favicon" class="form-label">Favicon</label>
                                <input type="file" name="favicon" class="form-control-file" id="favicon" >
                            </div>
                            <div class="mb-3">
                                <label for="eduMinImg" class="form-label">Education Ministar Image (150px X 150px)</label>
                                <input type="file" name="eduMinImg" class="form-control-file" id="eduMinImg" >
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label for="adminPhoto" class="form-label">Admin Photo</label>
                                <input type="file" name="adminPhoto" class="form-control-file" id="adminPhoto" >
                            </div>
                            <div class="mb-3">
                                <label for="principalSign" class="form-label">Principal Sign</label>
                                <input type="file" name="principalSign" class="form-control-file" id="principalSign" >
                            </div>
                            <div class="mb-3">
                                <label for="boardChairmanImg" class="form-label">Board Chairman Image  (150px X 150px)</label>
                                <input type="file" name="boardChairmanImg" class="form-control-file" id="boardChairmanImg" >
                            </div>
                        </div>
                    </div>
                    @endif
                    @if(!empty($serverId))
                    <button type="submit" class="mt-4 btn btn-success btn-lg">Update Details</button>
                    @else
                    <button type="submit" class="mt-4 btn btn-success btn-lg">Save Details</button>
                    @endif
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
                                    <input type="file" name="insLogo" class="form-control-file" id="insLogo" >
                                    <button type="submit" class="btn btn-success btn-lg mt-4">Update Logo</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="preview-thumb bg-success" src="{{ asset('public') }}\upload\image\cultivation\{{ $logo }}" alt="{{ $insName }}">
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
                                <button type="submit" class="btn btn-primary btn-lg mt-4">Update Icon</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="preview-thumb" src="{{ asset('public') }}\upload\image\cultivation\{{ $favicon }}" alt="{{ $insName }}">
                                <div>
                                    <a href="{{ route('delFavicon',['id'=>$serverId]) }}">Delete</a>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="boardChairmanImg" class="form-label fw-bold">Board Chairman(300 X 350)</label>
                            @if(empty($boardChairmanImg))
                            <form class="form" action="{{ route('saveBoardChairmanImg') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="serverId" value="{{ $serverId }}">
                                <input type="file" name="boardChairmanImg" class="form-control-file" id="boardChairmanImg">
                                <button type="submit" class="btn btn-danger btn-lg mt-4">Update Chairman</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="preview-thumb" src="{{ asset('public') }}\upload\image\cultivation\{{ $boardChairmanImg }}" alt="{{ $insName }}">
                                <div>
                                    <a href="{{ route('delBoardChairmanImg',['id'=>$serverId]) }}">Delete</a>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="mb-3">
                            <label for="adminPhoto" class="form-label fw-bold">Principal(300 X 350)</label>
                            @if(empty($avatar))
                            <form class="form" action="{{ route('saveAvatar') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="serverId" value="{{ $serverId }}"class="form-control-file" >
                                <input type="file" name="adminPhoto" id="adminPhoto" class="form-control-file" >
                                <button type="submit" class="btn btn-danger btn-lg mt-4">Update Principal Photo</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="preview-thumb" src="{{ asset('public') }}\upload\image\cultivation\{{ $avatar }}" alt="{{ $insName }}">
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
                                <input type="file" name="principalSign" class="form-control-file" id="principalSign" >
                                <button type="submit" class="btn btn-warning btn-lg mt-4">Update Principal Sign</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="preview-thumb" src="{{ asset('public') }}\upload\image\cultivation\{{ $principalSign }}" alt="{{ $insName }}">
                                <div>
                                    <a href="{{ route('delSign',['id'=>$serverId]) }}">Delete</a>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="eduMinImg" class="form-label fw-bold">Education Ministar(300 X 350)</label>
                            @if(empty($eduMinImg))
                            <form class="form" action="{{ route('saveEduMinImg') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="serverId" value="{{ $serverId }}">
                                <input type="file" name="eduMinImg" class="form-control-file" id="eduMinImg">
                                <button type="submit" class="btn btn-danger btn-lg mt-4">Update Minister</button>
                            </form>
                            @else
                            <div class="pt-1">
                                <img class="preview-thumb" src="{{ asset('public') }}\upload\image\cultivation\{{ $eduMinImg }}" alt="{{ $insName }}">
                                <div>
                                    <a href="{{ route('delEduMinImg',['id'=>$serverId]) }}">Delete</a>
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
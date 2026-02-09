@extends('cultivation.include')
@section('backTitle')
Configuration
@endsection
@section('backIndex')
@push('styles')
<style>
    :root {
        --cfg-border: #e7eaee;
        --cfg-soft: #f6f7f9;
        --cfg-ink: #222b38;
        --cfg-muted: #6c757d;
    }
    .section-title { font-weight:600; }
    .form-hint { font-size:.85rem; color:#6c757d; }
    .btn-soft { background:#f1f3f5; border-color:#f1f3f5; color:#495057; }
    .btn-soft:hover { background:#e9ecef; border-color:#e9ecef; }
    .divider { height:1px; background:#e9ecef; margin: 1rem 0; }
    .config-header { background:#f8f9fa; border-radius:.5rem; padding: .75rem 1rem; }
    .preview-thumb { max-width: 160px; border-radius: .25rem; }
    .config-section {
        border: 1px solid var(--cfg-border);
        background: #ffffff;
        border-radius: .75rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 0 rgba(0,0,0,.03);
    }
    .config-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .75rem;
        color: var(--cfg-ink);
    }
    .config-badge {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--cfg-muted);
        background: var(--cfg-soft);
        padding: .2rem .55rem;
        border-radius: 999px;
    }
    .config-section .form-label { font-weight: 600; color: #2b2f36; }
    .config-actions {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        padding-top: .75rem;
        border-top: 1px dashed var(--cfg-border);
        margin-top: .75rem;
    }
    #principalDesignation { color: #111111; background: #ffffff; }
    #principalDesignation option { color: #111111; background: #ffffff; }
    .select2-container--default .select2-selection--single {
        color: #111111;
        background: #ffffff;
        border: 1px solid #ced4da;
        height: calc(1.5em + .75rem + 2px);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #111111;
        line-height: calc(1.5em + .75rem);
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + .75rem);
    }
    .input-group .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
    }
    .input-group .select2-container .select2-selection--single {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    #sms_type { color:#111111; background-color:#ffffff; }
    #sms_type option { color:#111111; background-color:#ffffff; }
</style>
@endpush
@php
    $serverData = \App\Models\ServerConfig::orderBy('id','DESC')->limit(1)->first();
    $teacherDesignations = \App\Models\Designation::teacherDesignations();
    if(!empty($serverData)):
        $validSmsTypes = ['present_only','absent_only','both'];
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
        $smsTypeRaw             = strtolower(trim((string)($serverData->sms_type ?? '')));
        $smsType                = in_array($smsTypeRaw, $validSmsTypes, true) ? $smsTypeRaw : 'both';
        $smsBodyPresent          = $serverData->sms_body_present ?? config('sms.sms_message_present');
        $smsBodyAbsent           = $serverData->sms_body_absent ?? config('sms.sms_message_absent');
        $smsEnabled              = $serverData->sm_on_off !== null && $serverData->sm_on_off !== ''
            ? filter_var($serverData->sm_on_off, FILTER_VALIDATE_BOOLEAN)
            : true;
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
        $smsType                = 'both';
        $smsBodyPresent          = config('sms.sms_message_present');
        $smsBodyAbsent           = config('sms.sms_message_absent');
        $smsEnabled              = true;
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
                    <div class="config-section">
                        <div class="config-section-header">
                            <span class="section-title">Institution Profile</span>
                            <span class="config-badge">Basic</span>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="insName" class="form-label">Institute Name</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-school"></i></span></div>
                                        <input type="text" name="insName" class="form-control" id="insName" value="{{ $insName }}" placeholder="Enter the name of the institute" >
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="einNumber" class="form-label">EIN Number</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-hashtag"></i></span></div>
                                        <input type="text" name="einNumber" class="form-control" id="einNumber" value="{{ $einNumber }}" placeholder="Enter institute EIN Number" >
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="establishDate" class="form-label">Establish Date</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar"></i></span></div>
                                        <input type="text" name="establishDate" class="form-control" id="establishDate" value="{{ $establishDate }}" placeholder="Enter establish date">
                                    </div>
                                    <div class="form-hint">Format: YYYY-MM-DD or a readable date.</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="insAddress" class="form-label">Address</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span></div>
                                        <input type="text" name="insAddress" class="form-control" id="insAddress" value="{{ $location }}" placeholder="Enter institute address" >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="config-section">
                        <div class="config-section-header">
                            <span class="section-title">Contact & Office</span>
                            <span class="config-badge">Operations</span>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="officeMobile" class="form-label">Official Mobile</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div>
                                        <input type="text" name="officeMobile" class="form-control" id="officeMobile" value="{{ $officeMobile }}" placeholder="Enter office mobile number" >
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="officeMail" class="form-label">Official Email</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-envelope"></i></span></div>
                                        <input type="text" name="officeMail" class="form-control" id="officeMail" value="{{ $officeMail }}" placeholder="Enter office email address" >
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
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
                    </div>

                    <div class="config-section">
                        <div class="config-section-header">
                            <span class="section-title">Leadership</span>
                            <span class="config-badge">People</span>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="principalName" class="form-label">Institute Head Name</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user-tie"></i></span></div>
                                        <input type="text" name="principalName" class="form-control" id="principalName" value="{{ $principalName }}" placeholder="Enter the name of the institute head" >
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="principalDesignation" class="form-label">Institute Head Designation</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="far fa-id-badge"></i></span>
                                        <select name="principalDesignation" class="form-select select2" id="principalDesignation">
                                            <option value="">Select Designation</option>
                                            @if(!empty($principalDesignation))
                                                @php
                                                    $hasMatch = $teacherDesignations->first(function($d) use ($principalDesignation){
                                                        return (string)$d->name === (string)$principalDesignation || (string)$d->id === (string)$principalDesignation;
                                                    });
                                                @endphp
                                                @if(!$hasMatch)
                                                    <option value="{{ $principalDesignation }}" selected>{{ $principalDesignation }}</option>
                                                @endif
                                            @endif
                                            @foreach($teacherDesignations as $designation)
                                                <option value="{{ $designation->name }}" {{ (string)$principalDesignation === (string)$designation->name || (string)$principalDesignation === (string)$designation->id ? 'selected' : '' }}>{{ $designation->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="principalMobile" class="form-label">Institute Head Mobile</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div>
                                        <input type="text" name="principalMobile" class="form-control" id="principalMobile" value="{{ $principalMobile }}" placeholder="Enter institute head mobile number" >
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="principalMail" class="form-label">Institute Head Email</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-envelope"></i></span></div>
                                        <input type="text" name="principalMail" class="form-control" id="principalMail" value="{{ $principalMail }}" placeholder="Enter institute head email address" >
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="boardChairmanName" class="form-label">Board Chairman Name</label>
                                    <input type="text" name="boardChairmanName" class="form-control" id="boardChairmanName" value="{{ $boardChairmanName }}" placeholder="Enter board chairman name">
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="eduMinName" class="form-label">Education Minister Name</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
                                        <input type="text" name="eduMinName" class="form-control" id="eduMinName" value="{{ $eduMinName }}" placeholder="Enter the education minister name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="config-section">
                        <div class="config-section-header">
                            <span class="section-title">Social & Media</span>
                            <span class="config-badge">Public</span>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="facebookPage" class="form-label">Facebook Page</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-facebook"></i></span></div>
                                        <input type="text" name="facebookPage" class="form-control" id="facebookPage" value="{{ $fbPage }}" placeholder="Enter facebook page link here">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="twitterLink" class="form-label">Twitter Profile</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-twitter"></i></span></div>
                                        <input type="text" name="twitterLink" class="form-control" id="twitterLink" value="{{ $twitterLink }}" placeholder="Enter twitter profile Link">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="youtubeChanel" class="form-label">Youtube Channel</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-youtube"></i></span></div>
                                        <input type="text" name="youtubeChanel" class="form-control" id="youtubeChanel" value="{{ $youtubeLink }}" placeholder="Enter YouTube channel link">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @php
                        $smsTypeValue = strtolower(trim((string)old('sms_type', $smsType)));
                        if (!in_array($smsTypeValue, ['present_only','absent_only','both'], true)) {
                            $smsTypeValue = 'both';
                        }
                    @endphp
                    <div class="config-section">
                    <div id="sms-config-enabled" style="{{ $smsEnabled ? '' : 'display:none;' }}">
                        <div class="config-section-header">
                            <span class="section-title">SMS Settings</span>
                            <span class="config-badge">Alerts</span>
                        </div>
                        <div class="form-hint mb-3">Applies to attendance notifications and overrides the SMS settings page when set.</div>
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="sms_type" class="form-label">SMS Type</label>
                                    <select name="sms_type" id="sms_type" class="form-select">
                                        <option value="present_only" {{ $smsTypeValue === 'present_only' ? 'selected' : '' }}>Present Only</option>
                                        <option value="absent_only" {{ $smsTypeValue === 'absent_only' ? 'selected' : '' }}>Absent Only</option>
                                        <option value="both" {{ $smsTypeValue === 'both' ? 'selected' : '' }}>Both of Present/Absent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-12"></div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="sms_body_present" class="form-label">Present SMS Body</label>
                                    <textarea name="sms_body_present" id="sms_body_present" class="form-control" rows="3">{{ old('sms_body_present', $smsBodyPresent) }}</textarea>
                                    <div class="form-hint">Placeholders: {student}, {date}, {status}</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="sms_body_absent" class="form-label">Absent SMS Body</label>
                                    <textarea name="sms_body_absent" id="sms_body_absent" class="form-control" rows="3">{{ old('sms_body_absent', $smsBodyAbsent) }}</textarea>
                                    <div class="form-hint">Placeholders: {student}, {date}, {status}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="sms-config-disabled" style="{{ $smsEnabled ? 'display:none;' : '' }}">
                        <div class="config-section-header">
                            <span class="section-title">SMS Settings</span>
                            <span class="config-badge">Alerts</span>
                        </div>
                        <div class="form-hint">Please contact your admin to enable SMS Settings for your institute</div>
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
                                <label for="principalSign" class="form-label">Institute Head Sign</label>
                                <input type="file" name="principalSign" class="form-control-file" id="principalSign" >
                            </div>
                            <div class="mb-3">
                                <label for="boardChairmanImg" class="form-label">Board Chairman Image  (150px X 150px)</label>
                                <input type="file" name="boardChairmanImg" class="form-control-file" id="boardChairmanImg" >
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="config-actions">
                        @if(!empty($serverId))
                        <button type="submit" class="btn btn-success btn-lg">Update Details</button>
                        @else
                        <button type="submit" class="btn btn-success btn-lg">Save Details</button>
                        @endif
                    </div>
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
                            <label for="adminPhoto" class="form-label fw-bold">Institute Head (300 X 350)</label>
                            @if(empty($avatar))
                            <form class="form" action="{{ route('saveAvatar') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="serverId" value="{{ $serverId }}"class="form-control-file" >
                                <input type="file" name="adminPhoto" id="adminPhoto" class="form-control-file" >
                                <button type="submit" class="btn btn-danger btn-lg mt-4">Update Institute Head Photo</button>
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
                            <label for="principalSign" class="form-label fw-bold">Institute Head Sign</label>
                            @if(empty($principalSign))
                            <form class="form" action="{{ route('saveSign') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="serverId" value="{{ $serverId }}">
                                <input type="file" name="principalSign" class="form-control-file" id="principalSign" >
                                <button type="submit" class="btn btn-warning btn-lg mt-4">Update Institute Head Sign</button>
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
@push('scripts')
<script>
    (function(){
        function setSmsSection(enabled){
            var onBox = document.getElementById('sms-config-enabled');
            var offBox = document.getElementById('sms-config-disabled');
            if (!onBox || !offBox) return;
            onBox.style.display = enabled ? '' : 'none';
            offBox.style.display = enabled ? 'none' : '';
        }

        function syncFromServer(){
            var url = @json(route('sms.settings.status'));
            fetch(url, { credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(j){
                    if (!j || !j.ok) return;
                    var enabled = !!j.enabled;
                    setSmsSection(enabled);
                    try { localStorage.setItem('sms_settings_enabled', enabled ? '1' : '0'); } catch (e) {}
                })
                .catch(function(){});
        }

        document.addEventListener('DOMContentLoaded', function(){
            try {
                var stored = localStorage.getItem('sms_settings_enabled');
                if (stored === '1' || stored === '0') {
                    setSmsSection(stored === '1');
                }
            } catch (e) {}

            // Poll for server-side changes (e.g., super admin toggles from another panel)
            syncFromServer();
            setInterval(syncFromServer, 10000);
        });

        window.addEventListener('storage', function(e){
            if (!e || e.key !== 'sms_settings_enabled') return;
            if (e.newValue === '1' || e.newValue === '0') {
                setSmsSection(e.newValue === '1');
            }
        });
    })();
</script>
@endpush
@endsection
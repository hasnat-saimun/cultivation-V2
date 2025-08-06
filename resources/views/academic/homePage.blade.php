@extends('academic.include')
@section('backTitle')
Institute Dashboard
@endsection
@section('backIndex')
@php 
    $homePage = \App\Models\homeInfo::orderBy('id','DESC')->first();
    if(!empty($homePage)):
        $slidImg1               = $homePage->slidImg1;
        $slideHadeMessege1      = $homePage->slideHadeMessege1;
        $slideDescription1      = $homePage->slideDescription1;
        $slidImg2               = $homePage->slidImg2;
        $slideHadeMessege2      = $homePage->slideHadeMessege2;
        $slideDescription2      = $homePage->slideDescription2;
        $slidImg3               = $homePage->slidImg3;
        $slideHadeMessege3      = $homePage->slideHadeMessege3;
        $slideDescription3      = $homePage->slideDescription3;
        $eduMinName             = $homePage->eduMinName;
        $eduMinImg              = $homePage->eduMinImg;
        $eduMinDetail           = $homePage->eduMinDetail;
        $boardChairmanName      = $homePage->boardChairmanName;
        $boardChairmanImg       = $homePage->boardChairmanImg;
        $boardChairmanDetail    = $homePage->boardChairmanDetail;
        $principalName          = $homePage->principalName;
        $principalImg           = $homePage->principalImg;
        $principalDetail        = $homePage->principalDetail;
        $founded                = $homePage->founded;
        $area                   = $homePage->area;
        $teacherTotal           = $homePage->teacherTotal;
        $studentTotal           = $homePage->studentTotal;
        $notice                 = $homePage->notice;
        $wcMsgHadeline          = $homePage->wcMsgHadeline;
        $wclMsgDescription      = $homePage->wclMsgDescription;
        $missionDescription     = $homePage->missionDescription;
        $writerName             = $homePage->writerName;
        $mainGoal               = $homePage->mainGoal;
        $pageId                 = $homePage->id;
    else:
        $pageId                 = null;
        $slidImg1               = "";
        $slideHadeMessege1      = "";
        $slideDescription1      = "";
        $slidImg2               = "";
        $slideHadeMessege2      = "";
        $slideDescription2      = "";
        $slidImg3               = "";
        $slideHadeMessege3      = "";
        $slideDescription3      = "";
        $eduMinName             = "";
        $eduMinImg              = "";
        $eduMinDetail           = "";
        $boardChairmanName      = "";
        $boardChairmanImg       = "";
        $boardChairmanDetail    = "";
        $principalName          = "";
        $principalImg           = "";
        $principalDetail        = "";
        $founded                = "";
        $area                   = "";
        $teacherTotal           = "";
        $studentTotal           = "";
        $notice                 = "";
        $wcMsgHadeline          = "";
        $wclMsgDescription      = "";
        $missionDescription     = "";
        $writerName             = "";
        $mainGoal               = "";
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-12 ">
        <div class="card">
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
            <div class="card-body cultivation">
                <form action="{{ route('insDetails') }}" class="form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="pageId" value="{{ $pageId }}">
                    <div class="row">
                        <div class="col-12 card-header mb-5"> Slider Info</div>
                        <div class="col-4 mb-3">
                            <label for="slidImg1">Slide Image 01 (150px X 150px)</label>
                            @if(empty($slidImg1))
                            <input type="file" name="slidImg1" id="slidImg1"class="form-control-file">
                            @else
                            <div class="my-2">
                                <img class="w-25" src="{{ asset('public/upload/image/cultivation').'/'.$slidImg1 }}" class="form-control">
                                <div><a href="{{ route('delslidImg1',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                            </div>
                            @endif
                        </div>
                        <div class="col-4 mb-3">
                            <label for="slideHadeMessege1">Headline</label>
                            <input type="text" name="slideHadeMessege1" class="form-control" placeholder="Enter the headline" value="{{ $slideHadeMessege1 }}">
                        </div>
                        <div class="col-4 mb-3">
                            <label for="slideDescription1">Description</label>
                             <textarea name="slideDescription1" class="form-control" placeholder="Enter description about institute">{{ $slideDescription1 }}</textarea>
                        </div>
                        <div class="col-4 mb-3">
                            <label for="heroImg">Slide Image 02 (150px X 150px)</label>
                            @if(empty($heroImg))
                            <input type="file" name="heroImg" id="heroImg"class="form-control-file">
                            @else
                            <div class="my-2">
                                <img class="w-25" src="{{ asset('public/upload/image/cultivation').'/'.$heroImg }}" class="form-control">
                                <div><a href="{{ route('delHeroImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                            </div>
                            @endif
                        </div>
                        <div class="col-4 mb-3">
                            <label for="insHeadline">Headline</label>
                            <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                        </div>
                        <div class="col-4 mb-3">
                            <label for="insHeadline">Description</label>
                             <textarea name="insDetails" class="form-control" placeholder="Enter description about institute">{{ $details }}</textarea>
                        </div>
                        <div class="col-4 mb-3">
                            <label for="heroImg">Slide Image 03 (150px X 150px)</label>
                            @if(empty($heroImg))
                            <input type="file" name="heroImg" id="heroImg"class="form-control-file">
                            @else
                            <div class="my-2">
                                <img class="w-25" src="{{ asset('public/upload/image/cultivation').'/'.$heroImg }}" class="form-control">
                                <div><a href="{{ route('delHeroImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                            </div>
                            @endif
                        </div>
                        <div class="col-4 mb-3">
                            <label for="insHeadline">Headline</label>
                            <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                        </div>
                        <div class="col-4 mb-3">
                            <label for="insHeadline">Description</label>
                             <textarea name="insDetails" class="form-control" placeholder="Enter description about institute">{{ $details }}</textarea>
                        </div>
                    </div>
                    <div class="row mt-5">
                        <div class="col-12 mb-5 card-header">Other Info</div>
                        <div class="col-3 mb-3">
                            <label for="insHeadline">Founded Year</label>
                            <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                        </div>
                        <div class="col-3 mb-3">
                            <label for="insHeadline">Campus Area</label>
                            <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                        </div>
                        <div class="col-3 mb-3">
                            <label for="insHeadline">Teacher & Staff</label>
                            <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                        </div>
                        <div class="col-3 mb-3">
                            <label for="insHeadline">Student</label>
                            <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="insHeadline">Important Notice</label>
                            <textarea name="insDetails" class="form-control" placeholder="Enter description about institute">{{ $details }}</textarea>
                        </div>
                    </div>
                    <div class="row mt-5">
                        <div class="col-12 mb-5 card-header">Welcome Info</div>
                         <div class="col-12  mb-3">
                            <label for="insHeadline">Headline</label>
                            <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="insHeadline">Description</label>
                            <textarea name="insDetails" class="form-control" placeholder="Enter description about institute">{{ $details }}</textarea>
                        </div>
                    </div>
                    <div class="row mt-5">
                        <div class="col-12 mb-5 card-header">Mission & Vission</div>
                         <div class="col-12  mb-3">
                            <label for="insHeadline">Writer Name</label>
                            <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="insHeadline">Description</label>
                            <textarea name="insDetails" class="form-control" placeholder="Enter description about institute">{{ $details }}</textarea>
                        </div>
                        <div class="col-12  mb-3">
                            <label for="insHeadline">Mian Goal</label>
                            <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                        </div>
                    </div>
                    <div class="row mt-5 ">
                        <div class="col-6 mb-3">
                            <div class="row ">
                                <div class=" mb-5 card-header "> Education Ministar Info</div>
                                <div class="col-9 mb-3">
                                    <label for="insHeadline">Name</label>
                                    <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="insHeadline">Details</label>
                                    <textarea name="insDetails" class="form-control" placeholder="Enter description about institute">{{ $details }}</textarea>
                                </div> 
                                <div class="col-12  mb-3">
                                    <label for="heroImg">Slide Image 01 (150px X 150px)</label>
                                    @if(empty($heroImg))
                                    <input type="file" name="heroImg" id="heroImg"class="form-control-file">
                                    @else
                                    <div class="my-2">
                                        <img class="w-25" src="{{ asset('public/upload/image/cultivation').'/'.$heroImg }}" class="form-control">
                                        <div><a href="{{ route('delHeroImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="row">
                                <div class="mb-5 card-header">Board Chairman</div>
                                <div class="col-12 mb-3">
                                    <label for="insHeadline">Name</label>
                                    <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="insHeadline">Details</label>
                                    <textarea name="insDetails" class="form-control" placeholder="Enter description about institute">{{ $details }}</textarea>
                                </div> 
                                <div class="col-12  mb-3">
                                    <label for="heroImg">Slide Image 01 (150px X 150px)</label>
                                    @if(empty($heroImg))
                                    <input type="file" name="heroImg" id="heroImg"class="form-control-file">
                                    @else
                                    <div class="my-2">
                                        <img class="w-25" src="{{ asset('public/upload/image/cultivation').'/'.$heroImg }}" class="form-control">
                                        <div><a href="{{ route('delHeroImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                         <div class="col-6 mt-5 mb-3">
                            <div class="row ">
                                <div class=" mb-4 card-header "> Principal</div>
                                <div class="col-12 mb-3">
                                    <label for="insHeadline">Name</label>
                                    <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="insHeadline">Details</label>
                                    <textarea name="insDetails" class="form-control" placeholder="Enter description about institute">{{ $details }}</textarea>
                                </div> 
                                <div class="col-12  mb-3">
                                    <label for="heroImg">Slide Image 01 (150px X 150px)</label>
                                    @if(empty($heroImg))
                                    <input type="file" name="heroImg" id="heroImg"class="form-control-file">
                                    @else
                                    <div class="my-2">
                                        <img class="w-25" src="{{ asset('public/upload/image/cultivation').'/'.$heroImg }}" class="form-control">
                                        <div><a href="{{ route('delHeroImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 ">
                        <button class="btn btn-success btn-lg" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection
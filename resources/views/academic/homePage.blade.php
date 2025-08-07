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
                <form action="{{ route('homeDetails') }}" class="form" method="POST" enctype="multipart/form-data">
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
                                <img class="w-25" src="{{ asset('public/upload/image/cultivation/webHomepage').'/'.$slidImg1 }}" class="form-control">
                                <div><a href="{{ route('delSlidImg1',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
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
                            <label for="slidImg2">Slide Image 02 (150px X 150px)</label>
                            @if(empty($slidImg2))
                            <input type="file" name="slidImg2" id="slidImg2"class="form-control-file">
                            @else
                            <div class="my-2">
                                <img class="w-25" src="{{ asset('public/upload/image/cultivation/webHomepage').'/'.$slidImg2 }}" class="form-control">
                                <div><a href="{{ route('delSlidImg2',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                            </div>
                            @endif
                        </div>
                        <div class="col-4 mb-3">
                            <label for="slideHadeMessege2">Headline</label>
                            <input type="text" name="slideHadeMessege2" class="form-control" placeholder="Enter the headline" value="{{ $slideHadeMessege2 }}">
                        </div>
                        <div class="col-4 mb-3">
                            <label for="slideDescription2">Description</label>
                             <textarea name="slideDescription2" class="form-control" placeholder="Enter description about institute">{{ $slideDescription2 }}</textarea>
                        </div>
                        <div class="col-4 mb-3">
                            <label for="slidImg3">Slide Image 03 (150px X 150px)</label>
                            @if(empty($slidImg3))
                            <input type="file" name="slidImg3" id="slidImg3"class="form-control-file">
                            @else
                            <div class="my-2">
                                <img class="w-25" src="{{ asset('public/upload/image/cultivation/webHomepage').'/'.$slidImg3 }}" class="form-control">
                                <div><a href="{{ route('delSlidImg3',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                            </div>
                            @endif
                        </div>
                        <div class="col-4 mb-3">
                            <label for="slideHadeMessege3">Headline</label>
                            <input type="text" name="slideHadeMessege3" class="form-control" placeholder="Enter the headline" value="{{ $slideHadeMessege3 }}">
                        </div>
                        <div class="col-4 mb-3">
                            <label for="slideDescription3">Description</label>
                             <textarea name="slideDescription3" class="form-control" placeholder="Enter description about institute">{{ $slideDescription3 }}</textarea>
                        </div>
                    </div>
                    <div class="row mt-5">
                        <div class="col-12 mb-5 card-header">Other Info</div>
                        <div class="col-3 mb-3">
                            <label for="founded">Founded Year</label>
                            <input type="text" name="founded" class="form-control" placeholder="Enter the headline" value="{{ $founded }}">
                        </div>
                        <div class="col-3 mb-3">
                            <label for="area">Campus Area</label>
                            <input type="text" name="area" class="form-control" placeholder="Enter the headline" value="{{ $area }}">
                        </div>
                        <div class="col-3 mb-3">
                            <label for="insHeadlteacherTotaline">Teacher & Staff</label>
                            <input type="text" name="teacherTotal" class="form-control" placeholder="Enter the headline" value="{{ $teacherTotal }}">
                        </div>
                        <div class="col-3 mb-3">
                            <label for="insHeadlistudentTotalne">Student</label>
                            <input type="text" name="studentTotal" class="form-control" placeholder="Enter the headline" value="{{ $studentTotal }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="notice">Important Notice</label>
                            <textarea name="notice" class="form-control" placeholder="Enter description about institute">{{ $notice }}</textarea>
                        </div>
                    </div>
                    <div class="row mt-5">
                        <div class="col-12 mb-5 card-header">Welcome Info</div>
                         <div class="col-12  mb-3">
                            <label for="wcMsgHadeline">Headline</label>
                            <input type="text" name="wcMsgHadeline" class="form-control" placeholder="Enter the headline" value="{{ $wcMsgHadeline }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="wclMsgDescription">Description</label>
                            <textarea name="wclMsgDescription" class="form-control" placeholder="Enter description about institute">{{ $wclMsgDescription }}</textarea>
                        </div>
                    </div>
                    <div class="row mt-5">
                        <div class="col-12 mb-5 card-header">Mission & Vission</div>
                         <div class="col-12  mb-3">
                            <label for="writerName">Writer Name</label>
                            <input type="text" name="writerName" class="form-control" placeholder="Enter the headline" value="{{ $writerName }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="missionDescription">Description</label>
                            <textarea name="missionDescription" class="form-control" placeholder="Enter description about institute">{{ $missionDescription }}</textarea>
                        </div>
                        <div class="col-12  mb-3">
                            <label for="mainGoal">Mian Goal</label>
                            <input type="text" name="mainGoal" class="form-control" placeholder="Enter the headline" value="{{ $mainGoal }}">
                        </div>
                    </div>
                    <div class="row mt-5 ">
                        <div class="col-6 mb-3">
                            <div class="row ">
                                <div class=" mb-5 card-header "> Education Ministar Info</div>
                                <div class="col-9 mb-3">
                                    <label for="eduMinName">Name</label>
                                    <input type="text" name="eduMinName" class="form-control" placeholder="Enter the headline" value="{{ $eduMinName }}">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="eduMinDetail">Details</label>
                                    <textarea name="eduMinDetail" class="form-control" placeholder="Enter description about institute">{{ $eduMinDetail }}</textarea>
                                </div> 
                                <div class="col-12  mb-3">
                                    <label for="eduMinImg"> Image (150px X 150px)</label>
                                    @if(empty($eduMinImg))
                                    <input type="file" name="eduMinImg" id="eduMinImg"class="form-control-file">
                                    @else
                                    <div class="my-2">
                                        <img class="w-25" src="{{ asset('public/upload/image/cultivation/webHomepage').'/'.$eduMinImg }}" class="form-control">
                                        <div><a href="{{ route('delEduMinImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="row">
                                <div class="mb-5 card-header">Board Chairman</div>
                                <div class="col-12 mb-3">
                                    <label for="boardChairmanName">Name</label>
                                    <input type="text" name="boardChairmanName" class="form-control" placeholder="Enter the headline" value="{{ $boardChairmanName }}">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="boardChairmanDetail">Details</label>
                                    <textarea name="boardChairmanDetail" class="form-control" placeholder="Enter description about institute">{{ $boardChairmanDetail }}</textarea>
                                </div> 
                                <div class="col-12  mb-3">
                                    <label for="boardChairmanImg"> Image  (150px X 150px)</label>
                                    @if(empty($boardChairmanImg))
                                    <input type="file" name="boardChairmanImg" id="boardChairmanImg"class="form-control-file">
                                    @else
                                    <div class="my-2">
                                        <img class="w-25" src="{{ asset('public/upload/image/cultivation/webHomepage').'/'.$boardChairmanImg }}" class="form-control">
                                        <div><a href="{{ route('delBoardChairmanImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                         <div class="col-6 mt-5 mb-3">
                            <div class="row ">
                                <div class=" mb-4 card-header "> Principal</div>
                                <div class="col-12 mb-3">
                                    <label for="principalName">Name</label>
                                    <input type="text" name="principalName" class="form-control" placeholder="Enter the headline" value="{{ $principalName }}">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="principalDetail">Details</label>
                                    <textarea name="principalDetail" class="form-control" placeholder="Enter description about institute">{{ $principalDetail }}</textarea>
                                </div> 
                                <div class="col-12  mb-3">
                                    <label for="principalImg"> Image  (150px X 150px)</label>
                                    @if(empty($principalImg))
                                    <input type="file" name="principalImg" id="principalImg"class="form-control-file">
                                    @else
                                    <div class="my-2">
                                        <img class="w-25" src="{{ asset('public/upload/image/cultivation/webHomepage').'/'.$principalImg }}" class="form-control">
                                        <div><a href="{{ route('delPrincipalImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 ">
                        <button class="btn btn-success btn-lg w-100" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection
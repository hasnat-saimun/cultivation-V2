@extends('academic.include')
@section('backTitle')
Home Page
@endsection
@section('backIndex')
@php 
    $frontManage = \App\Models\frontManage::orderBy('id','DESC')->first();
    if(!empty($frontManage)):
        $homeHeadline            = $frontManage->homeHeadline;
        $homeDetails             = $frontManage->homeDetails;
        $educationMinistarName   = $frontManage->educationMinistarName;
        $educationMinistarImg    = $frontManage->educationMinistarImg;
        $boardChairmanName       = $frontManage->boardChairmanName;
        $boardChairmanImg        = $frontManage->boardChairmanImg;
        $boardChairmanLocation   = $frontManage->boardChairmanLocation;
        $ourMission              = $frontManage->ourMission;
        $pageId                  = $frontManage->id;
    else:
        $homeHeadline            = "";
        $homeDetails             = "";
        $educationMinistarName   = "";
        $educationMinistarImg    = "";
        $boardChairmanName       = "";
        $boardChairmanImg        = "";
        $boardChairmanLocation   = "";
        $ourMission              = "";
        $pageId                  = null;
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">Home Page Info</div>
            <div class="card-body cultivation">
                <form action="{{ route('frontDetails') }}" class="form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="pageId" value="{{ $pageId }}">
                    <div class="mb-3 form-group">
                        <label for="homeHeadline">Headline</label>
                        <input type="text" name="homeHeadline" class="form-control" placeholder="Enter the headline" value="{{ $homeHeadline }}">
                    </div>
                    <div class="mb-3 form-group">
                        <label for="homeDetails">Details</label>
                        <textarea name="homeDetails" class="form-control" placeholder="Enter description about institute">{{ $homeDetails }}</textarea>
                    </div>
                    <div class="mb-3 form-group">
                        <label for="ourMission">Mission & Vission</label>
                        <input type="text" name="ourMission" class="form-control" placeholder="Enter institute mission" value="{{ $ourMission }}">
                    </div>
                    <div class="row">
                        <div class="col-4 form-group">
                            <div class="mb-3">
                                <label for="educationMinistarImg">Education Ministar</label>
                                <input type="text" name="educationMinistarName" class="form-control mb-3" placeholder="Enter the name" value="{{ $educationMinistarName }}">
                                @if(empty($educationMinistarImg))
                                <input type="file" name="educationMinistarImg" class="">
                                @else
                                <div class="my-2">
                                    <img class="w-75" src="{{ asset('public/upload/image/frontImg').'/'.$educationMinistarImg }}" class="form-control">
                                    <div><a href="{{ route('delEducationMinistarImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-8 form-group">
                            <div class="row">
                                <div class="col-6">
                                    <label for="boardChairmanImg">Board Chairman</label>
                                    <input type="text" name="boardChairmanName" class="form-control mb-3" placeholder="Enter the name" value="{{ $boardChairmanName }}">
                                </div>
                                <div class="col-6">
                                    <label for="boardChairmanLocation">Board Chairman Location</label>
                                    <input type="text" name="boardChairmanLocation" class="form-control mb-3" placeholder="Enter the Chairman Location" value="{{ $boardChairmanLocation }}">
                                </div>
                            <div class="col-7 text-center">
                                @if(empty($boardChairmanImg))
                                    <input type="file" name="boardChairmanImg" class="">
                                    @else
                                    <div class="my-2">
                                        <img class="w-75" src="{{ asset('public/upload/image/frontImg').'/'.$boardChairmanImg }}" class="form-control">
                                        <div><a href="{{ route('delBoardChairmanImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                                    </div>
                                @endif
                            </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-success btn-lg" type="submit">Save</button>
                    </div>
                </form>
                </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection
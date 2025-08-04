@extends('academic.include')
@section('backTitle')
Institute Info
@endsection
@section('backIndex')
@php 
    $institute = \App\Models\InstituteDetails::orderBy('id','DESC')->first();
    if(!empty($institute)):
        $headline       = $institute->insHeadline;
        $details        = $institute->insDetails;
        $foundedDate    = $institute->establishDate;
        $landSize       = $institute->landSize;
        $heroImg        = $institute->heroImg;
        $mission        = $institute->mission;
        $vision         = $institute->vision;
        $pageId         = $institute->id;
    else:
        $pageId         = null;
        $headline       = "";
        $details        = "";
        $foundedDate    = "";
        $landSize       = "";
        $heroImg        = "";
        $mission        = "";
        $vision         = "";
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
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
            <div class="card-header">Institute Info</div>
            <div class="card-body cultivation">
                <form action="{{ route('insDetails') }}" class="form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="pageId" value="{{ $pageId }}">
                    <div class="mb-3">
                        <label for="insHeadline">Headline</label>
                        <input type="text" name="insHeadline" class="form-control" placeholder="Enter the headline" value="{{ $headline }}">
                    </div>
                    <div class="mb-3">
                        <label for="establishDate">Founded</label>
                        <input type="text" name="establishDate" class="form-control" placeholder="Enter establish date" value="{{ $foundedDate }}">
                    </div>
                    <div class="mb-3">
                        <label for="landSize">Land Size</label>
                        <input type="text" name="landSize" class="form-control" placeholder="Enter total land size" value="{{ $landSize }}">
                    </div>
                    <div class="mb-3">
                        <label for="insDetails">Details</label>
                        <textarea name="insDetails" class="form-control" placeholder="Enter description about institute">{{ $details }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="ourMission">Mission</label>
                        <input type="text" name="ourMission" class="form-control" placeholder="Enter institute mission" value="{{ $mission }}">
                    </div>
                    <div class="mb-3">
                        <label for="ourVision">Vission</label>
                        <input type="text" name="ourVision" class="form-control" placeholder="Enter institute vision" value="{{ $vision }}">
                    </div>
                    <div class="mb-3">
                    <label for="heroImg">Avatar (150px X 150px)</label>
                        @if(empty($heroImg))
                        <input type="file" name="heroImg" id="heroImg"class="form-control-file">
                        @else
                        <div class="my-2">
                            <img class="w-25" src="{{ asset('public/upload/image/cultivation').'/'.$heroImg }}" class="form-control">
                            <div><a href="{{ route('delHeroImg',['id'=>$pageId]) }}" class="text-danger fw-bold">Delete</a></div>
                        </div>
                        @endif
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
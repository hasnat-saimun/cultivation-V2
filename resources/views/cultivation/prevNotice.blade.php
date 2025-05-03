@extends('academic.include')
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
            @if(!empty($notice))
            <div class="card-header">
                <i class="fa-duotone fa-toolbox"></i> Notice-> {{ $notice->headline }}
            </div>
            <div class="card-body cultivation mt-4">
                <div class="row" id="noticeBoard">
                    <!-- ID CARD DESIGN ONE -->
                    <div class="col-12 row mb-4 mt-4">
                        <div class="col-md-12">
                            <div class="p-2 pt-1">
                                <div class="row mb-4">
                                    <div class="col-6 mx-auto text-center">
                                        <img class="w-50 bg-success p-1" src="{{ asset('public') }}\upload\image\cultivation\{{ $logo }}" alt="{{ $insName }}">
                                    </div>
                                    <div class="col-7 text-center mx-auto mt-4">
                                        @include('cultivation.noticeHeader')
                                    </div>
                                </div>
                                <div class="row mt-4 mb-4">
                                    <div class="col-6 display-5 fw-bold mb-2">Source:- </div>
                                    <div class="col-6 display-5 fw-bold mb-2 text-right">Date:- {{ $notice->created_at->format('d-m-Y') }}</div>
                                </div>
                                <div class="row mt-1 align-items-center text-left">
                                    <div class="col-12">
                                        <h3 class="display-5">{{ $notice->headline }}</h3>
                                        {!! $notice->body !!}
                                    </div>
                                    <div class="text-right mt-4 col-12 row">
                                        <div class="col-12 textright">
                                            <img style="height:60px;width:170px" src="{{ asset('public') }}\upload\image\cultivation\{{ $principalSign }}" alt="{{ $principalName }}">
                                        </div>
                                        <div class="col-12">
                                            <p class="fw-bold text-dark mb-0 mr-4 pr-4">{{ $principalName }}</p>
                                            <p class="fw-bold text-dark mb-0 mr-4 pr-4">Principal/Head Of Institute</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-success btn-lg my-4 d-print-none" onclick="printDiv('noticeBoard')"><i class="fa-regular fa-print"></i> Print</button>
                        <a class="btn btn-primary btn-lg my-4 d-print-none mx-2" href="{{ route('editNotice',['id'=>$notice->id]) }}"><i class="fa-regular fa-square-pen"></i> Edit Notice</a>
                        <a class="btn btn-success btn-lg my-4 d-print-none mx-2" href="{{ route('noticeList') }}"><i class="fa-light fa-list-check"></i> All Notice</a>
                    </div>
                </div>
            </div>
            @else
            <div class="card-body cultivation">
                <div class="alert alert-info">Sorry! Notice not found with your query</div>
            </div>
            @endif
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
<script type="text/javascript">
    function printDiv(e){
        var printContents = document.getElementById(e).innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
    }
</script>
@endsection
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
                                <div class="row mb-3">
                                    <div class="col-12">
                                        @include('cultivation.noticeHeader')
                                    </div>
                                </div>
                                <hr class="mt-2 mb-3">
                                <div class="row mt-3 mb-4">
                                    <div class="col-12 text-end" style="font-size:14px;color:#555;">Date: {{ $notice->created_at->format('d-m-Y') }}</div>
                                </div>
                                <div class="row mt-1 align-items-center text-left">
                                    <div class="col-12">
                                        <h3 class="display-5">{{ $notice->headline }}</h3>
                                        @if(!empty($notice->attachment))
                                            @php $ext = strtolower(pathinfo($notice->attachment, PATHINFO_EXTENSION)); @endphp
                                            @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                                <img src="{{ asset('public/'.$notice->attachment) }}" alt="Notice attachment" class="img-fluid mt-3">
                                            @elseif($ext === 'pdf')
                                                <iframe src="{{ asset('public/'.$notice->attachment) }}" width="100%" height="700" class="mt-3" style="border:1px solid #e2e8f0;border-radius:6px;"></iframe>
                                                <p class="mt-2"><a href="{{ asset('public/'.$notice->attachment) }}" target="_blank">Open PDF in new tab</a></p>
                                            @else
                                                <p class="mt-3"><a href="{{ asset('public/'.$notice->attachment) }}" target="_blank">Download attachment</a></p>
                                            @endif
                                        @else
                                            {!! $notice->body !!}
                                        @endif
                                    </div>
                                    @if(empty($notice->attachment))
                                    <div class="text-right mt-4 col-12 row">
                                        <div class="col-12 textright">
                                            <img style="height:60px;width:170px" src="{{ asset('public') }}\upload\image\cultivation\{{ $principalSign }}" alt="{{ $principalName }}">
                                        </div>
                                        <div class="col-12">
                                            <p class="fw-bold text-dark mb-0 mr-4 pr-4">{{ $principalName }}</p>
                                            <p class="fw-bold text-dark mb-0 mr-4 pr-4">Principal/Head Of Institute</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if(empty($notice->attachment))
                        <button class="btn btn-success btn-lg my-4 d-print-none" onclick="printDiv('noticeBoard')"><i class="fa-regular fa-print"></i> Print</button>
                        @endif
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
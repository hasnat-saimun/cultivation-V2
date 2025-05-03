@extends('result.include')
@section('backTitle')
Get Admit Card
@endsection
@php
    $classData = \App\Models\classManage::find($classId);
    $sectionData = \App\Models\sectionManage::find($groupId);
    $sessionData = \App\Models\sessionManage::find($sessionId);
    $examData = \App\Models\Exam::find($examId);
    if($classData):
        $className = $classData->className;
    else:
        $className = "-";
    endif;
    if($sectionData):
        $sectionName = $sectionData->section;
    else:
        $sectionName = "-";
    endif;
    if($sessionData):
        $sessionName = $sessionData->session;
    else:
        $sessionName = "-";
    endif;
    if($examData):
        $examName = $examData->examName;
    else:
        $examName = "-";
    endif;
@endphp
@section('backIndex')
    @if($studentList->count()>0)
        <div class="row">
            <div class="col-6 col-md-3 mb-2"><b>Group:</b>  {{ $sectionName }}</div>
            <div class="col-6 col-md-3 mb-2"><b>Class:</b>  {{ $className }}</div>
            <div class="col-6 col-md-3 mb-2"><b>Session:</b> {{ $sessionName }}</div>
            <div class="col-6 col-md-3 mb-2"><b>Exam:</b> {{ $examName }}</div>
        </div>
        <div class="card">
            <div class="card-header">Admit Card</div>
            <div class="card-body" id="idCardFull">
                <div class="row">
                    <div class="col-12">
                        <button class="btn btn-success btn-lg mb-4 d-print-none mt-4" onclick="printDiv('idCardFull')"><i class="fa-light fa-print"></i> All Print</button>
                        <a href="{{ route('admitCard') }}" class="btn btn-lg btn-primary d-print-none"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
                    </div>
                    @foreach($studentList as $std)
                    <div class="col-6">
                        <div class="row" id="idCard-{{ $std->id }}">
                            <!-- ID CARD DESIGN ONE -->
                            <div class="col-12 row mb-4">
                                <div class="col-md-12">
                                    <div class="id-bg p-2 text-center pt-1 row">
                                        <div class="col-12">
                                            @include('cultivation.stdIdHeader')
                                        </div>
                                        <div class="col-4 mx-auto display-5 fw-bold bg-success text-white rounded mb-2">Admit Card</div>
                                        <div class="row mt-1 align-items-center no-gutter">
                                            <div class="col-4 mt-4">
                                                @if(!empty($std->avatar))
                                                <img src="{{ asset('/public/upload/image/student/') }}/{{ $std->avatar }}" alt="{{ $std->stdId }}" class="w-50 img-thumbnail">
                                                @else
                                                <img src="{{ asset('/public/back-office/img/') }}/avatar.jpeg" alt="{{ $std->stdId }}" class="w-50 img-thumbnail">
                                                @endif
                                            </div>
                                            <div class="col-8 text-left text-dark">
                                                <p class="mb-0"><span class="fw-bold"> Student ID:</span> {{ $std->stdId }}</p>
                                                <p class="mb-0"><span class="fw-bold"> Name:</span> {{ $std->fullName }} {{ $std->sureName }}</p>
                                                <p class="mb-0"><span class="fw-bold"> Roll Number:</span> {{ $std->rollNumber}}</p>
                                                <p class="mb-0"><span class="fw-bold"> Class:</span> {{ $className}}</p>
                                                <p class="mb-0"><span class="fw-bold"> Session:</span> {{ $sessionName}}</p>
                                                <p class="mb-0"><span class="fw-bold"> Exam:</span> {{ $examName}}</p>
                                            </div>
                                            <div class="text-center mt-4 col-4">
                                                <p class="fw-bold text-dark mb-0">Student Sign</p>
                                            </div>
                                            <div class="text-right mt-4 col-8">
                                                <p class="fw-bold text-dark mb-0 mr-4 pr-4">Authorize Sign</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- <button class="btn btn-success btn-lg mb-4 d-print-none mt-4" onclick="printDiv('idCard-{{ $std->id }}')">Print</button> -->
                            </div>
                        </div>
                    </div>
                    @endforeach
                    <div class="col-12">
                        <button class="btn btn-success btn-lg mb-4 d-print-none mt-4" onclick="printDiv('idCardFull')"><i class="fa-light fa-print"></i> All Print</button>
                        <a href="{{ route('admitCard') }}" class="btn btn-lg btn-primary d-print-none"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
                    </div>
                </div>
            </div>
        </div>
    @else
    <div class="alert alert-info">
        Sorry! No data found
    </div>
    <div class="mb-4"> <a href="{{ route('admitCard') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
    @endif
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
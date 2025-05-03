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
            <div class="card-header">Generate Attendent Sheet</div>
            <div class="card-body" id="idCardFull">
                <div class="row">
                    <div class="col-12">
                        <button class="btn btn-success btn-lg d-print-none my-2" onclick="printDiv('idCardFull')"><i class="fa-light fa-print"></i> All Print</button>
                        <a href="{{ route('attendSheet') }}" class="btn btn-lg btn-primary d-print-none"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
                    </div>
                    @foreach($studentList as $std)
                    <div class="col-12">
                        <fieldset class="print-border" style="page-break-after:always !important;margin-bottom:1rem" id="idCard-{{ $std->id }}">
                            <div class="row">
                                <!-- ID CARD DESIGN ONE -->
                                <div class="col-12 row mb-2">
                                    <div class="col-12 text-center" style="margin:1rem 0">
                                        @include('cultivation.stdIdHeader')
                                    </div>
                                    <div class="col-12 row align-items-center">
                                        <div class="col-2">
                                            @if(!empty($std->avatar))
                                            <img src="{{ asset('/public/upload/image/student/') }}/{{ $std->avatar }}" alt="{{ $std->stdId }}" class="w-100 img-thumbnail">
                                            @else
                                            <img src="{{ asset('/public/back-office/img/') }}/avatar.jpeg" alt="{{ $std->stdId }}" class="w-100 img-thumbnail">
                                            @endif
                                        </div>
                                        <div class="col-5">
                                            
                                            <p class="mb-0"><span class="fw-bold"> Student ID:</span> {{ $std->stdId }}</p>
                                            <p class="mb-0"><span class="fw-bold"> Name:</span> {{ $std->fullName }} {{ $std->sureName }}</p>
                                            <p class="mb-0"><span class="fw-bold"> Roll Number:</span> {{ $std->rollNumber}}</p>
                                        </div>
                                        <div class="col-5">
                                            <p class="mb-0"><span class="fw-bold"> Class:</span> {{ $className}}</p>
                                            <p class="mb-0"><span class="fw-bold"> Session:</span> {{ $sessionName}}</p>
                                            <p class="mb-0"><span class="fw-bold"> Exam:</span> {{ $examName}}</p>
                                        </div>
                                    </div>
                                    <div class="col-3 text-dark text-center mx-auto fw-bold border border-dark rounded" style="margin:2rem 0">Attendent Sheet</div>
                                    <div class="col-12 table-responsive" style="margin:1rem 0">
                                        <table class="table table-bordered">
                                            <thead>
                                                <th>SL</th>
                                                <th>Date</th>
                                                <th>Subject Name</th>
                                                <th>Student Sign</th>
                                                <th>Examiner Sign</th>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>3</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>4</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>5</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>6</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>7</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>8</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>9</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>10</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>11</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>12</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>13</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>14</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>15</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- <button class="btn btn-success btn-lg mb-4 d-print-none mt-4" onclick="printDiv('idCard-{{ $std->id }}')">Print</button> -->
                                    <div class="col-12 row" style="margin:2rem 0">
                                        <div class="col-6">
                                            <p class="fw-bold">Hall Superindentent</p>
                                            <p>(Signature with seal)</p>
                                        </div>
                                        <div class="col-6 text-right">
                                            <p class="fw-bold">Principal</p>
                                            <p>(Signature with seal)</p>
                                        </div>
                                        <div class="col-12">
                                            <p class="fw-bold">Note:</p>
                                            <ol class="list-group list-group-numbered border-0">
                                                <li class="list-group-item">This format is applicable only for one candidate</li>
                                                <li class="list-group-item">For different course different sheet should be used</li>
                                                <li class="list-group-item">Send this copy to the exam comittee after the exam end</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    @endforeach
                    <div class="col-12">
                        <button class="btn btn-success btn-lg my-2 d-print-none" onclick="printDiv('idCardFull')"><i class="fa-light fa-print"></i> All Print</button>
                        <a href="{{ route('attendSheet') }}" class="btn btn-lg btn-primary d-print-none"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
                    </div>
                </div>
            </div>
        </div>
    @else
    <div class="alert alert-info">
        Sorry! No data found
    </div>
    <div class="mb-4"> <a href="{{ route('attendSheet') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
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
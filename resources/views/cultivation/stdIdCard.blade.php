@extends('cultivation.include')
@section('backTitle')
{{$std->fullName}} ID Card
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto col-10 mx-auto">
                        <div class="card-body">
                            <div class="heading-layout1">
                                <div class="item-title">
                                    <h3>Student ID Card</h3>
                                </div>
                            </div>
                            @if(isset($std))
                            @php 
                            $sessionData  = \App\Models\sessionManage::find($std->sessName);
                            $sectionData  = \App\Models\sectionManage::find($std->sectionName);
                            $classData  = \App\Models\classManage::find($std->className);
                            $classData  = \App\Models\classManage::find($std->className);
                            $departmentData  = \App\Models\Department::find($std->departmentName);
                                if(null !== $sessionData && $sessionData->count()>0){
                                    $sessionName= $sessionData->session;
                                }else{
                                    $sessionName= '-';
                                }

                                if(null !== $classData && $classData->count()>0){
                                    $className = $classData->className;
                                }else{
                                    $className= '-';
                                }

                                if(null !== $departmentData && $departmentData->count()>0){
                                    $departmentName = $departmentData->departmentName;
                                }else{
                                    $departmentName= '-';
                                }




                                if(null !== $sectionData && $sectionData->count()>0){
                                    $sectionName = $sectionData->section;
                                }else{
                                    $sectionName= '-';
                                }
                            @endphp 
                            <div class="row">
                                <div class="col-6 mx-auto">
                                    <div class="row" id="idCardOne">
                                        <!-- ID CARD DESIGN ONE -->
                                        <div class="col-6 col-md-12 row mb-4">
                                            <div class="col-md-12">
                                                <div class="id-bg p-2 text-center pt-1 row">
                                                    <div class="col-12">
                                                        @include('cultivation.stdIdHeader')
                                                    </div>
                                                    <div class="col-6 mx-auto display-5 fw-bold bg-success text-white rounded mb-2">STUDENT ID CARD</div>
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
                                                            <p class="mb-0"><span class="fw-bold"> Name:</span> {{ $std->fullName }} {{ $std->lastName }}</p>
                                                            <p class="mb-0"><span class="fw-bold"> Roll Number:</span> {{ $std->rollNumber}}</p>
                                                            <p class="mb-0"><span class="fw-bold"> Class:</span> {{ $className}}</p>
                                                            <p class="mb-0"><span class="fw-bold"> Department:</span> {{ $departmentName}}</p>
                                                            <p class="mb-0">
                                                                <span class="fw-bold"> Session:</span> {{ $sessionName}}</p>
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
                                            <button class="btn btn-success btn-lg mb-4 d-print-none mt-4" onclick="printDiv('idCardOne')">Print</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mx-auto">
                                    <div class="row" id="idCardTwo">
                                        <!-- ID CARD DESIGN TWO -->
                                        <div class="col-6 col-md-12 row mb-4">
                                            <div class="col-md-12">
                                                <div class="id-bg2 p-2 text-center pt-1 row">
                                                    <div class="col-12">
                                                        @include('cultivation.stdIdHeader')
                                                    </div>
                                                    <div class="col-6 mx-auto display-5 fw-bold bg-success text-white rounded mb-2">STUDENT ID CARD</div>
                                                    <div class="row mt-1 align-items-center no-gutter">
                                                        <div class="col-4">
                                                            @if(!empty($std->avatar))
                                                            <img src="{{ asset('/public/upload/image/student/') }}/{{ $std->avatar }}" alt="{{ $std->stdId }}" class="w-50 img-thumbnail">
                                                            @else
                                                            <img src="{{ asset('/public/back-office/img/') }}/avatar.jpeg" alt="{{ $std->stdId }}" class="w-50 img-thumbnail">
                                                            @endif
                                                        </div>
                                                        <div class="col-8 text-left text-dark">
                                                            <p class="mb-0"><span class="fw-bold"> Student ID:</span> {{ $std->stdId }}</p>
                                                            <p class="mb-0"><span class="fw-bold"> Name:</span> {{ $std->fullName }} {{ $std->lastName }}</p>
                                                            <p class="mb-0"><span class="fw-bold"> Roll Number:</span> {{ $std->rollNumber}}</p>
                                                            <p class="mb-0"><span class="fw-bold"> Class:</span> {{ $className }}</p>
                                                            <p class="mb-0">
                                                            <span class="fw-bold"> Department:</span> {{ $departmentName}}</p>
                                                            <p class="mb-0">
                                                                <span class="fw-bold"> Session:</span> {{ $sessionName }}</p>
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
                                            <button class="btn btn-success btn-lg mb-4 d-print-none mt-4" onclick="printDiv('idCardTwo')">Print</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <!-- ID CARD DESIGN THREE -->
                                    <div class="row" id="idCardThree">
                                        <div class="col-6 col-md-12 row mb-4">
                                            <div class="col-md-12">
                                                <div class="id-bg1 p-2 text-center pt-1 row">
                                                    <div class="col-12">
                                                        @include('cultivation.stdIdHeader')
                                                    </div>
                                                    <div class="col-6 mx-auto display-5 fw-bold bg-success text-white rounded mb-2">STUDENT ID CARD</div>
                                                    <div class="row mt-1 align-items-center no-gutter">
                                                        <div class="col-4">
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
                                                            <p class="mb-0"><span class="fw-bold"> Class:</span> {{ $className }}</p>
                                                            <p class="mb-0"><span class="fw-bold"> Department:</span> {{ $departmentName}}</p>
                                                            <p class="mb-0">
                                                                <span class="fw-bold"> Session:</span> {{ $sessionName }}</p>
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
                                            <button class="btn btn-success btn-lg mb-4 d-print-none mt-4" onclick="printDiv('idCardThree')">Print</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                @else
                                <div class="col-md-12">
                                    <div class="alert alert-info">Sorry! No data found</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
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
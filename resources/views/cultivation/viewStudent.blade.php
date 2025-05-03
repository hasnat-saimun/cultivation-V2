@extends('cultivation.include')
@section('backTitle')
{{$singleData->fullName}} Profile
@endsection
@section('backIndex')
<style>
    body {
  padding: 0;
  margin: 0;
  color: #000;
}
.card{
    background-color:#b3c4c5 !important;
    padding-bottom: 20px !important;

}
.student-profile .card {
  border-radius: 20px;
}

.student-profile .card .card-header .profile_img {
  width: 150px;
  height: 150px;
  object-fit: cover;
  margin: 10px auto;
  border: 10px solid #ccc;
  border-radius: 50%;
}

.student-profile .card h3 {
  font-size: 20px;
  font-weight: 700;
}

.student-profile .card p {
  font-size: 16px;
  color: #000;
}

.student-profile .table th,
.student-profile .table td {
  font-size: 14px;
  padding: 5px 10px;
  color: #000;
}
</style>
<div class="single-info-details">
    <div class="item-content">
        <div class="header-inline item-header">
            <div class="header-elements d-print-none">
                <ul>
                    <li><a href="{{route('studentList')}}"><i class="fa-solid fa-arrow-left"></i></a></li>
                    <li><a href="#" onclick="printDiv('userView')"><i class="fas fa-print"></i></a></li>
                    <li><a href="#"><i class="fas fa-download"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>


<!-- Student Profile -->
    <div class="student-profile py-4" id="userView">
        @php
            $sessionData =\App\Models\sessionManage::find($singleData->sessName);
            $classData   =\App\Models\classManage::find($singleData->className);
            $sectionData =\App\Models\sectionManage::find($singleData->sectionName);
            $departmentData =\App\Models\Department::find($singleData->departmentName);
        @endphp
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                    <div class="card-header bg-transparent text-center">
                        <img class="rounded:100" style="width:150px;height:150px; border-radius: 80px ;" src="{{ asset('/public/upload/image/student/') }}/{{$singleData->avatar}}" alt="student">
                        <h3 class="mt-2">{{$singleData->fullName}}</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><strong class="pr-1">Student ID:</strong>{{ $singleData->stdId }}</p>
                        <p class="mb-0"><strong class="pr-1">Class:</strong> @if(!empty($classData))
                        <td class="font-medium text-dark-medium">{{$classData->className}}</td>
                        @else
                        <td>-</td>
                        @endif</p>
                        <p class="mb-0"><strong class="pr-1">Department:</strong>
                        @if(!empty($departmentData))
                            <td class="font-medium text-dark-medium">{{$departmentData->departmentName}}</td>
                        @else
                            <td>-</td>
                        @endif</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h3 class="mb-0"><i class="far fa-clone pr-1"></i>Personal Information</h3>
                    </div>
                    <div class="card-body pt-0">
                        <table class="table table-bordered mt-2">
                        <tr>
                            <th width="30%">Name</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->fullName}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Sure Name</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->sureName}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Father Name</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->father}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Mother Name</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->mother}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Gender</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->gender}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Birth Date</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->dob}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Blood Group</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->blGroup}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Religion</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->religion}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Location</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->address}}</td>
                        </tr>
                        <tr>
                            <th width="30%">E-mail</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->mail}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Mobile Nmuber</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->phone}}</td>
                        </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-5">
                <div class="col-12">
                <div class="card shadow-sm mt-5">
                    <div class="card-header bg-transparent border-0">
                        <h3 class="mb-0"><i class="far fa-clone pr-1"></i>General Information</h3>
                    </div>
                    <div class="card-body pt-0">
                        <table class="table table-bordered mt-2">
                        <tr>
                            <th width="30%">Student ID</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->stdId}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Class</th>
                            <td width="2%">:</td>
                            @if(!empty($classData))
                            <td class="font-medium text-dark-medium">
                                {{$classData->className}}
                            </td>
                            @else
                            <td>-</td>
                        @endif
                        </tr>
                        <tr>
                            <th width="30%">Department</th>
                            <td width="2%">:</td>
                            @if(!empty($departmentData))
                            <td class="font-medium text-dark-medium">
                                {{$departmentData->departmentName}}
                            </td>
                            @else
                            <td>-</td>
                            @endif
                        </tr>
                        <tr>
                            <th width="30%">Scition</th>
                            <td width="2%">:</td>
                            @if(!empty($sectionData))
                            <td class="font-medium text-dark-medium">
                                {{$sectionData->section}}
                            </td>
                            @else
                            <td>-</td>
                            @endif
                        </tr>
                        <tr>
                            <th width="30%">Roll Number</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->rollNumber}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Academic Year</th>
                            <td width="2%">:</td>
                            @if(!empty($sessionData))
                            <td class="font-medium text-dark-medium">
                                {{$sessionData->session}}
                            </td>
                            @else
                            <td>-</td>
                            @endif
                        </tr>
                        </table>
                    </div>
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

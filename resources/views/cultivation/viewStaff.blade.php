@extends('cultivation.include')
@section('backTitle')
{{$singleData->firstName}} Profile
@endsection
@section('backIndex')
    <style>
    body {
  padding: 0;
  margin: 0;
  color: #000;
}
.card{
    background-color:#b4c5b4 !important;
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
            <div class="header-elements">
                <ul>
                    <li><a href="{{route('staffList')}}"><i class="fa-solid fa-arrow-left"></i></a></li>
                    <li><a href="#"><i class="fas fa-print"></i></a></li>
                    <li><a href="#"><i class="fas fa-download"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>


<!-- Student Profile -->
    <div class="student-profile py-4">
        
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                    <div class="card-header bg-transparent text-center">
                        <img class="rounded:100" style="width:150px;height:150px; border-radius: 80px ;" src="{{ asset('/public/upload/image/staff/') }}/{{$singleData->avatar}}" alt="student">
                        <h3 class="mt-2">{{$singleData->firstName}}</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><strong class="pr-1">Staff ID:</strong>{{$singleData->staffId}}</p>
                        <p class="mb-0"><strong class="pr-1">Designation:</strong>{{$singleData->designation}}</p>
                        <p class="mb-0"><strong class="pr-1">Mobile:</strong> {{$singleData->mobile}}</p>
                        <p class="mb-0"><strong class="pr-1">E-mail:</strong>{{$singleData->email}}
                       </p>
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
                            <td>{{$singleData->firstName}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Sure Name</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->lastName}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Father Name</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->fathersName}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Mother Name</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->mothersName}}</td>
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
                            <th width="30%">Join Date</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->joinDate}}</td>
                        </tr>
                        <tr>
                            <th width="30%">E-mail</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->email}}</td>
                        </tr>
                        <tr>
                            <th width="30%">Mobile Nmuber</th>
                            <td width="2%">:</td>
                            <td>{{$singleData->mobile}}</td>
                        </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

@endsection
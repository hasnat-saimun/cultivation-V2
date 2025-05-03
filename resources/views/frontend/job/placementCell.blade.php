@extends('frontend.include') @section('fronttitle') Syllabus @endsection @section('frontcontent')
<section>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12 text-center con-title">
                <h2 class="wow fadeInLeft animated" data-wow-delay=".60s">Placement<span> Cell</span></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @if(Session::get('success'))
                <div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <i class="material-icons">close</i>
                    </button>
                    <span>{!! Session::get('success') !!}</span>
                </div>
                @endif @if(Session::get('error'))
                <div class="alert alert-warning">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <i class="material-icons">close</i>
                    </button>
                    <span>{!! Session::get('error') !!}</span>
                </div>
                @endif
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Placement Cell</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Create Profile</button>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                        <div class="card rounded-0 border-top-0">
                            <div class="card-body table-responsive">
                                <!-- On tables -->
                                <table id="myTable" data-order='[[ 0, "desc" ]]' class="table table-border">
                                    <thead>
                                        <tr>
                                            <th class="d-none">Id</th>
                                            <th>Name</th>
                                            <th>Session</th>
                                            <th>Roll Number</th>
                                            <th>Company</th>
                                            <th>Photo</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($Datakey)) @foreach($Datakey as $data)
                                        <tr>
                                            <td class="d-none">{!!$data->id!!}</td>
                                            <td>{!!$data->fullName!!}</td>
                                            <td>{!!$data->sessionYear!!}</td>
                                            <td>{!!$data->rollNumber!!}</td>
                                            <td>{!!$data->companyName!!}</td>
                                            <td style="width:10%"><img class="w-100" src="{{ asset('/public/upload/image/placementCell/').'/'.$data->avatar}}" alt="{!! $data->fullName !!}" style="max-height: 120px !important;" /></td>
                                            <td>
                                                <a href="#" class="text-success my-2" data-bs-toggle="modal" data-bs-target="#getData{{ $data->id }}">
                                                    <i class="fa fa-eye" style="color: green;"></i>
                                                </a>
                                                <!-- Modal -->
                                                <div class="modal fade" id="getData{{ $data->id }}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <p class="modal-title fs-5" id="staticBackdropLabel">Details About {{ $data->fullName }}</p>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="text-center mb-3">
                                                                    <img class="w-50 border" src="{{ asset('/public/upload/image/placementCell/').'/'.$data->avatar}}" alt="{!! $data->fullName !!}" />
                                                                </div>
                                                                <table class="table table-bordered">
                                                                    <tr>
                                                                        <th class="fw-bold">Session</th>
                                                                        <td class="text-start">: {!! $data->sessionYear !!}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th class="fw-bold">Roll Number</th>
                                                                        <td class="text-start">: {!! $data->rollNumber !!}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th class="fw-bold">Company</th>
                                                                        <td class="text-start">: {!! $data->companyName !!}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th class="fw-bold">Position</th>
                                                                        <td class="text-start">: {!! $data->designation !!}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th class="fw-bold">Mobile</th>
                                                                        <td class="text-start">: {!! $data->mobile!!}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th class="fw-bold">Email</th>
                                                                        <td class="text-start">: {!! $data->email !!}</td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade card rounded-0" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                        <div class="card-body">
                            <form class="form row" method="POST" action="{{ route('savePlacementCell')}}" enctype="multipart/form-data">
                                @csrf
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <input type="text" name="fullName" class="form-control" placeholder="Enter your full name(*)" required />
                                    </div>
                                    <div class="mb-3">
                                        <input type="number" name="rollNumber" class="form-control" placeholder="Enter roll number(*)" minlength="6" maxlength="6" required />
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" name="sessionYear" class="form-control" placeholder="Enter session year(*)" required />
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <input type="email" name="email" class="form-control" placeholder="Enter a valid email(*)" required />
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" name="mobile" class="form-control" placeholder="Enter mobile number(*)" required />
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" name="designation" class="form-control" placeholder="Enter current designation/title(*)" required />
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <input type="text" name="companyName" class="form-control" placeholder="Enter current company(*)" required />
                                    </div>
                                    <div class="mb-3">
                                        <textarea class="form-control" name="jobDetails" placeholder="Details about your profession" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <input type="file" class="form-control" name="avatar" required />
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <input type="submit" class="btn btn-primary" value="Save Profile" />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

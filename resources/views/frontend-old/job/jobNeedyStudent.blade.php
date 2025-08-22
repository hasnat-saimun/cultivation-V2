@extends('frontend.include')
@section('fronttitle')
Syllabus
@endsection
@section('frontcontent')
<section>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12 text-center con-title">
                <h2 class="wow fadeInLeft animated" data-wow-delay=".60s">Job Needy <span> Student</span></h2>
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
                @endif
                
                @if(Session::get('error'))
                  <div class="alert alert-warning">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <i class="material-icons">close</i>
                    </button>
                    <span>{!! Session::get('error') !!}</span>
                  </div>
                @endif 
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Job Seekers List</button>
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
                                <table id="myTable" data-order='[[ 0, "desc" ]]' class="table table-border" >
                                    <thead>
                                        <tr>
                                            <th class="d-none">Id</th>
                                            <th>Name</th>
                                            <th>Session</th>
                                            <th>Roll Number</th>
                                            <th>Photo</th>
                                            <th>View CV</th>
                                            
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($Datakey))
                                        @foreach($Datakey as $data)
                                        <tr>
                                            <td class="d-none">{!!$data->id!!}</td>
                                            <td>{!!$data->fullName!!}</td>
                                            <td>{!!$data->sessionYear!!}</td>
                                            <td>{!!$data->rollNumber!!}</td>
                                            <td><img class="w-50" src="{{ asset('public/upload/image/neddyStudent/').'/'.$data->avatar}}" alt="{!! $data->fullName !!}" style="max-height:120px !important"></td>
                                            <td>
                                                <a href="#" class="text-success my-2" data-bs-toggle="modal" data-bs-target="#getData{{ $data->id }}" >
                                                    <i class="fa fa-eye" style="color: green;"></i>
                                                </a>
<!-- Modal -->
<div class="modal fade" id="getData{{ $data->id }}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <p class="modal-title fs-5" id="staticBackdropLabel">CV of {{ $data->fullName }}</p>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
            <div class="text-center">
                <embed class="w-100" height="550px" src="{{ asset('public/upload/image/neddyStudent/').'/'.$data->attachment}}" alt="{!! $data->fullName !!}"></embed>
            </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
                                            </td>
                                        </tr>
                                        @endforeach         
                                       @endif
                                    </tbody>
                                 </table>                         
                            </div>
                        </div>    
                    </div>
                    <div class="tab-pane fade card rounded-0" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                        <div class="card-body">
                            <form class="form row" method="POST" action="{{ route('saveNeedyStdPanel') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="fullName">Full Name</label>
                                        <input type="text" name="fullName" class="form-control" placeholder="Enter your full name(*)" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="rollNumber">Roll Number</label>
                                        <input type="number" name="rollNumber" class="form-control" placeholder="Enter roll number(*)" minlength="6" maxlength="6" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="sessionYear">Session</label>
                                        <input type="text" name="sessionYear" class="form-control" placeholder="Enter session year(*)" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="Enter a valid email(*)" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="mobile">Mobile Number</label>
                                        <input type="text" name="mobile" class="form-control" placeholder="Enter mobile number(*)" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="attachment">CV</label>
                                        <input type="file" class="form-control" name="attachment" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="avatar">Photo</label>
                                        <input type="file" class="form-control" name="avatar" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <input type="submit" class="btn btn-primary" value="Save Profile">
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
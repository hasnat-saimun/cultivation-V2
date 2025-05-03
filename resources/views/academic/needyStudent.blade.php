@extends('academic.include')
@section('backTitle')
Needy Student Panel
@endsection
@section('backIndex')
@php 

    if(!empty($itemId)):
        $plcList       = \App\Models\needyStudentPanel::find($itemId);
        if(!empty($plcList)):
            $fullName       = $plcList->fullName;
            $mobileNumber   = $plcList->mobile;
            $emailAddress   = $plcList->email;
            $sessionYear    = $plcList->sessionYear;
            $rollNumber     = $plcList->rollNumber;
            $attachment     = $plcList->attachment;
            $avatar         = $plcList->avatar;
        endif;
    else:
        $itemId          = null;   
        $fullName       = "";
        $mobileNumber   = "";
        $emailAddress   = "";
        $joinDate       = "";
        $sessionYear    = "";
        $rollNumber     = "";
        $designation    = "";
        $companyName    = "";
        $jobDetails     = "";
        $avatar         = "";
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">@yield('backTitle')</div>
            <div class="card-body cultivation">
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
                <form action="{{ route('saveNeedyStdPanel') }}" class="form row" method="POST" enctype="multipart/form-data">
                    <div class="col-12 col-md-6">
                        <input type="hidden" name="itemId" value="{{$itemId}}">
                        @csrf
                        <div class="mb-3">
                            <label for="fullName">Full Name</label>
                            <input type="text" name="fullName" class="form-control" placeholder="Enter the full name of the student" value="{{ $fullName }}">
                        </div>
                        <div class="mb-3">
                            <label for="mobile">Mobile</label>
                            <input type="text" name="mobile" class="form-control" placeholder="Enter mobile number" value="{{ $mobileNumber }}">
                        </div>
                        <div class="mb-3">
                            <label for="attachment">Cv(PDF)</label>
                            @if(!empty($attachment))
                            <div>
                                <iframe src="{{ asset('public/upload/image/neddyStudent/').'/'.$attachment }}" class="w-50" height="300px"></iframe>
                                <a href="{{ route('delNeedyStdPaneldoc',['id'=>$itemId]) }}" class="fw-bold text-danger">Delete</a>
                            </div>
                            @else
                            <input type="file" name="attachment" class="form-control-file">
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="email">Email</label>
                            <input type="text" name="email" class="form-control" placeholder="Enter email address" value="{{ $emailAddress }}">
                        </div>
                        <div class="mb-3">
                            <label for="sessionYear">Session</label>
                            <input type="text" name="sessionYear" class="form-control" placeholder="Enter session year" value="{{ $sessionYear }}">
                        </div>
                        <div class="mb-3">
                            <label for="rollNumber">Roll Number</label>
                            <input type="text" name="rollNumber" class="form-control" placeholder="Enter roll number" value="{{ $rollNumber }}">
                        </div>
                        <div class="mb-3">
                            <label for="avatar">Avatar(PDF/Photo)</label>
                            @if(!empty($avatar))
                            <div>
                                <iframe src="{{ asset('public/upload/image/neddyStudent/').'/'.$avatar }}" class="w-50" height="300px"></iframe>
                                <a href="{{ route('delNeedyStdPanelCon',['id'=>$itemId]) }}" class="fw-bold text-danger">Delete</a>
                            </div>
                            @else
                            <input type="file" name="avatar" class="form-control-file">
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-success btn-lg mx-2" type="submit">Save</button>
                        <a class="btn btn-primary btn-lg mx-2" href="{{ route('needyStudentPanel') }}">New Profile</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-12">
            <div class="card-body cultivation">
                <div class="card-header mb-3">Jod Needy Student List</div>
                <table id="myTable" class="table table-striped table-responsive">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Session Year</th>
                            <th>Attachment</th>
                            <th>Photo</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($needy))
                        @foreach($needy as $item)
                            <tr>
                                <td>{{ $item->fullName }}</td>
                                <td>{{ $item->email }}</td>
                                <td>{{ $item->mobile }}</td>
                                <td>{{ $item->sessionYear }}</td>
                                <td>{{ $item->attachment }}</td>
                                <td>{{ $item->avatar }}</td>
                                <td>
                                    <a href="{{ route('editNeedyStdPanel',['id'=>$item->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                    <a href="{{ route('delNeedyStdPanel',['id'=>$item->id]) }}" onclick="return confirm('Are you sure you want to delete this item?');" title="Get Id Card"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        @else
                            <tr>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection
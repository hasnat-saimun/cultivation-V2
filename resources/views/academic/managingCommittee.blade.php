@extends('academic.include')
@section('backTitle')
Governing Body
@endsection
@section('backIndex')
@php 
    $managingCommittee    = \App\Models\ManagingComittee::orderBy('id','DESC')->get();
     
    $proId          = null;   
    $fullName       = "";
    $mobileNumber   = "";
    $emailAddress   = "";
    $qualification  = "";
    $jobDetails     = "";
    $designation    = "";
    $address        = "";
    $validYear      = "";
    $status         = "";
    $avatar         = "";

    if(!empty($commId)):
        $committee       = \App\Models\ManagingComittee::find($commId);
        if(!empty($committee)):
            $proId          = $committee->id;   
            $fullName       = $committee->fullName;
            $mobileNumber   = $committee->mobile;
            $emailAddress   = $committee->email;
            $qualification  = $committee->qualification;
            $jobDetails     = $committee->jobDetails;
            $designation    = $committee->designation;
            $address        = $committee->address;
            $validYear      = $committee->validYear;
            $status         = $committee->status;
            $avatar         = $committee->avatar;
        endif;
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">Governing Body</div>
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
                        
                    @error('avatar')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    </div>
                </div>
                <form action="{{ route('saveManagingCommittee') }}" class="form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="proId" value="{{ $proId }}">
                    @csrf
                    <div class="mb-3">
                        <label for="fullName">Full Name</label>
                        <input type="text" name="fullName" class="form-control" placeholder="Enter the fullname of the committee member" value="{{ $fullName }}">
                    </div>
                    <div class="mb-3">
                        <label for="mobileNumber">Mobile</label>
                        <input type="text" name="mobileNumber" class="form-control" placeholder="Enter the mobile number of the member" value="{{ $mobileNumber }}">
                    </div>
                    <div class="mb-3">
                        <label for="emailAddress">Email</label>
                        <input type="text" name="emailAddress" class="form-control" placeholder="Enter email address" value="{{ $emailAddress }}">
                    </div>
                    <div class="mb-3">
                        <label for="address">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Enter member full address" value="{{ $address }}">
                    </div>
                    <div class="mb-3">
                        <label for="jobDetails">Job Details</label>
                        <input type="text" name="jobDetails" class="form-control" placeholder="Enter profession or job details" value="{{ $jobDetails }}">
                    </div>
                    <div class="mb-3">
                        <label for="qualification">Qualification</label>
                        <input type="text" name="qualification" class="form-control" placeholder="Enter educational qualification" value="{{ $qualification }}">
                    </div>
                    <div class="mb-3">
                        <label for="designation">Designation</label>
                        <select name="designation" class="form-select">
                            @if(!empty($explId))
                            <option value="{{ $committee->designation_id ?? '' }}">{{ $committee->designation ?? $exDesig ?? '' }}</option>
                            @endif
                            @if(isset($designations) && $designations->count() > 0)
                                @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}">{{ $desig->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="validYear">Valid Year</label>
                        <input type="text" name="validYear" class="form-control" placeholder="Enter committee valid date" value="{{ $validYear }}">
                    </div>
                    <div class="mb-3">
                    <label for="avatar">Avatar (150px X 150px)</label>
                        @if(empty($avatar))
                        <input type="file" name="avatar" id="avatar"class="form-control-file">
                        @else
                        <div class="my-2">
                            <img class="w-25" src="{{ asset('public/upload/image/cultivation').'/'.$avatar }}" class="form-control">
                            <div><x-delete-action :action="route('delImgContent',['id'=>$proId])" class="text-danger fw-bold">Delete</x-delete-action></div>
                        </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-success btn-lg mx-2" type="submit">Save</button>
                        <a class="btn btn-primary btn-lg mx-2" href="{{ route('managingCommittee') }}">New Profile</a>
                    </div>
                </form>
            </div>
            <div class="card-header mt-5">Governing Body Member List</div>
            <div class="card card-body cultivation">
                <table id="myTable" class="table table-striped table-responsive">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Designation</th>
                            <th>Job Details</th>
                            <th>Address</th>
                            <th>Valid Year</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($managingCommittee))
                        @php
                            $x = 1;
                        @endphp
                        @foreach($managingCommittee as $profile)
                            <tr>
                                <td>{{ $x }}</td>
                                <td>{{ $profile->fullName }}</td>
                                <td>{{ $profile->email }}</td>
                                <td>{{ $profile->mobile }}</td>
                                <td>{{ $profile->designation }}</td>
                                <td>{{ $profile->jobDetails }}</td>
                                <td>{{ $profile->address }}</td>
                                <td>{{ $profile->validYear }}</td>
                                <td>{{ $profile->status }}</td>
                                <td>
                                        <a href="{{ route('viewManagingCommittee',  ['id'=>$profile->id]) }}"><i class="fa-solid fa-eye mx-2" style="color:rgb(35 170 211);"></i></a>
                                        <a href="{{ route('editManagingCommittee',['id'=>$profile->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                        <x-delete-action :action="route('delManagingCommittee',['id'=>$profile->id])"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></x-delete-action>
                                </td>
                            </tr>
                        @endforeach
                        @else
                            <tr>
                                <td>1</td>
                                <td>Rasel Khondokar</td>
                                <td>-</td>
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
</div>
<!-- Dashboard summery End Here -->
@endsection

@extends('academic.include')
@section('backTitle')
Semister Plan Management
@endsection
@section('backIndex')
@php 
    if(!empty($itemId)):
        $items       = \App\Models\SemisterPlan::find($itemId);
        if(!empty($items)): 
            $title              = $items->title;
            $assignClass        = $items->assignClass;
            $assignDepartment   = $items->assignDepartment;
            $assignSession      = $items->assignSession;
            $attachment         = $items->attachment;
        endif;
    else:
        $itemId             = null;
        $title              = "";
        $assignClass        = "";
        $assignDepartment   = "";
        $assignSession      = "";
        $attachment         = "";
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">Semister Plan Management</div>
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
                <form action="{{ route('saveSemisterPlan') }}" class="form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="itemId" value="{{ $itemId }}">
                    @csrf
                    <div class="mb-3">
                        <label for="title">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Enter the title of the plan" value="{{ $title }}">
                    </div>
                    <div class="mb-3">
                        <label for="assignClass">Class</label>
                        <select name="assignClass" class="form-select">
                        @if(!empty($itemId))
                            @php
                                $existClass = \App\Models\classManage::find($assignClass);
                            @endphp
                            <option value="{{ $assignClass }}">{{ $existClass->className }}</option>
                            @endif
                            @php
                                $classes = \App\Models\classManage::orderBy('id','DESC')->get();
                            @endphp
                            @if(!empty($classes))
                                @foreach($classes as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                                @endforeach
                            @else
                                <option value="">-</option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assignDepartment">Department</label>
                        <select name="assignDepartment" class="form-select">
                        @if(!empty($itemId))
                            @php
                                $existDept = \App\Models\Department::find($assignDepartment);
                            @endphp
                            <option value="{{ $assignDepartment }}">{{ $existDept->departmentName }}</option>
                            @endif
                            @php
                                $department = \App\Models\Department::orderBy('id','DESC')->get();
                            @endphp
                            @if(!empty($department))
                                @foreach($department as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->departmentName }}</option>
                                @endforeach
                            @else
                                <option value="">-</option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assignSession">Session</label>
                        <select name="assignSession" class="form-select">
                        @if(!empty($itemId))
                            @php
                                $existSession = \App\Models\sessionManage::find($assignSession);
                            @endphp
                            <option value="{{ $assignSession }}">{{ $existSession->session }}</option>
                            @endif
                            @php
                                $session = \App\Models\sessionManage::orderBy('id','DESC')->get();
                            @endphp
                            @if(!empty($session))
                                @foreach($session as $sess)
                                <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                                @endforeach
                            @else
                                <option value="">-</option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="attachment">Attachment(PDF/Photo)</label>
                        @if(!empty($attachment))
                        <div>
                            <iframe src="{{ asset('public/upload/image/cultivation/semisterPlan/').'/'.$attachment }}" class="w-25" height="300px"></iframe>
                            <a href="{{ route('delSemisterPlanContent',['id'=>$itemId]) }}" class="fw-bold text-danger">Delete</a>
                        </div>
                        @else
                        <input type="file" name="attachment" class="form-control-file">
                        @endif
                    </div>
                    <div class="mt-4">
                        <button class="btn btn-success btn-lg mx-2" type="submit">Save</button>
                        <a class="btn btn-primary btn-lg mx-2" href="{{ route('semisterPlanManage') }}">Create New</a>
                    </div>
                </form>
            </div>
            <div class="card-header">Existing Semister Plan</div>
            <div class="card-body cultivation">
                <table id="myTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Title</th>
                            <th>Class</th>
                            <th>Department</th>
                            <th>Session</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($semisterPlanList))
                        @php
                            $x = 1;
                        @endphp
                        @foreach($semisterPlanList as $item)
                        @php 
                        $itemClass      = \App\Models\classManage::find($item->assignClass);
                            $itemDepartment = \App\Models\Department::find($item->assignDepartment);
                            $itemSession    = \App\Models\sessionManage::find($item->assignSession);
                        @endphp
                            <tr>
                                <td>{{ $x }}</td>
                                <td>{{ $item->title }}</td>
                                <td>{{ $itemClass->className }}</td>
                                <td>{{ $itemDepartment->departmentName }}</td>
                                <td>{{ $itemSession->session }}</td>
                                <td>
                                    <a href="{{ route('editSemisterPlan',['id'=>$item->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                        <a href="{{ route('delSemisterPlan',['id'=>$item->id]) }}"onclick="return confirm('Are you sure you want to delete this item?');" title="Get Id Card" ><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        @else
                            <tr>
                                <td colspan="6">Sorry! No data found</td>
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
@extends('result.include')
@section('backTitle')
Add New Marks
@endsection
@section('backIndex')
@php
    use App\Models\CultivationAdmin;
    use App\Models\classManage;
    use App\Models\Subject;

    $adminId = session('cultivationAdmin'); // or your custom session key
    $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
    $isTeacherAdmin = $user && $user->userType == 1;
@endphp
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto">
                        <div class="card-body">
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
                            <div class="heading-layout1">
                                <div class="item-title">
                                    <h3>Add New Marks</h3>
                                </div>
                            </div>
                            <form class="new-added-form" action="{{ route('getMarks') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Exam *</label>
                                        <select class="select2" name="examId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $examList = \App\Models\Exam::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($examList))
                                                @foreach($examList as $exm)
                                                <option value="{{ $exm->id }}">{{ $exm->examName }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div><!-- Class Dropdown -->
<div class="col-12 form-group">
    <label>Class *</label>
    <!-- Class Dropdown -->
    <select class="select2" name="classId" required>
        <option value="">Select *</option>
        @php
            if($isTeacherAdmin) {
                $classIds = $user->access_class_array ?? [];
                $classes = \App\Models\classManage::whereIn('id', $classIds)->get();
            } else {
                $classes = \App\Models\classManage::orderBy('id','DESC')->get();
            }
        @endphp
        @foreach($classes as $cls)
            <option value="{{ $cls->id }}">{{ $cls->className }}</option>
        @endforeach
    </select>
</div>

                                    <div class="col-12 form-group">
                                        <label>Session *</label>
                                        <select class="select2" name="sessionId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $sessions = \App\Models\sessionManage::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($sessions))
                                                @foreach($sessions as $sess)
                                                <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Section/Group *</label>
                                        <select class="select2" name="groupId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $department = \App\Models\sectionManage::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($department))
                                                @foreach($department as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->section }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    
<!-- Subject Dropdown -->
<div class="col-12 form-group">
    <label>Subject *</label>
   <select class="select2" name="subjectId" required>
    <option value="">Select *</option>
    @php
        if($isTeacherAdmin) {
            $subjectIds = $user->access_subject_array ?? [];
            $subjectId = \App\Models\Subject::whereIn('id', $subjectIds)->get();
        } else {
            $subjectId = \App\Models\Subject::orderBy('id','DESC')->get();
        }
    @endphp
    @foreach($subjectId as $sub)
        <option value="{{ $sub->id }}">{{ $sub->subjectName }}</option>
    @endforeach
</select>
</div>
                                    <div class="col-12 form-group mg-t-8">
                                        <button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Get Data</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
@endsection
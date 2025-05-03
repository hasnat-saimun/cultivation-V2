@extends('academic.include')
@section('backTitle')
Academic
@endsection
@section('backIndex')
                <!-- Dashboard Content Start Here -->
                <h3>Institute Info</h3>
                <div class="row mb-5 intro-box text-center">
                    <div class="col-2">
                        <a href="{{ route('insInfo') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-solid fa-building-columns"></i> About Institue</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('principalSpeech') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-solid fa-comment"></i> Principal Speech</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('exPrincipal') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-solid fa-user"></i> Ex Principal</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('managingCommittee') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-solid fa-users"></i> Managing Committee</a>
                    </div>
                </div>
                <!-- Dashboard Content Start Here -->
                <h3>Academic Info</h3>
                <div class="row mb-5 intro-box text-center">
                    <div class="col-2">
                        <a href="{{ route('classRoutineManage') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-book-open-reader"></i> Class Routine</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('semisterPlanManage') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-book-open-cover"></i> Semister Plan</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('examRoutineManage') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-book-open-reader"></i> Exam Routine</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('syllabusManage') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-book-open-cover"></i> Syllabus</a>
                    </div>
                    <div class="col-1">
                        <a href="{{ route('newNotice') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-book-open-reader"></i> Notice</a>
                    </div>
                </div>
                <!-- Dashboard Content Start Here -->
                <h3>Placement Cell</h3>
                <div class="row mb-5 intro-box text-center">
                    <div class="col-2">
                        <a href="{{ route('placementCell') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-solid fa-user-doctor"></i> Job Placement</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('needyStudentPanel') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-graduation-cap"></i> Job Needy Student</a>
                    </div>
                </div>
                <!-- Dashboard Content Start Here -->
                <h3>Other</h3>
                <div class="row mb-5 intro-box text-center">
                    <div class="col-2">
                        <a href="{{ route('newPhoto') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-image"></i> Add Photo</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('newVideo') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-solid fa-video"></i> Add Video</a>
                    </div>
                </div>
@endsection
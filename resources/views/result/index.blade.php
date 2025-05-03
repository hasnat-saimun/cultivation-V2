@extends('result.include')
@section('backTitle')
Dashboard
@endsection
@section('backIndex')
                <!-- Dashboard Content Start Here -->
                <div class="row my-4 intro-box text-center">
                    <div class="col-2">
                        <a href="{{ route('createClass') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-rhombus"></i> New Class</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('createDepartment') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-rhombus"></i> New Deperment</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('createSection') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-rhombus"></i> New Section</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('createSession') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-rhombus"></i> New Session</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('createSubject') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-solid fa-book"></i> New Subject</a>
                    </div>
                    <div class="col-2 ">
                        <a href="{{ route('createGrade') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-display-chart-up-circle-dollar"></i> Add G.P</a>
                    </div>
                    <div class="col-2 mt-4">
                        <a href="{{ route('createExam') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-diploma"></i> New Examination</a>
                    </div>
                    <div class="col-2 mt-4">
                        <a href="{{ route('addMarks') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-display-chart-up-circle-dollar"></i> Add Mark</a>
                    </div>
                </div>
@endsection
@extends('result.singleinclude')
@section('backTitle')
All Marksheet
@endsection
@section('backIndex')
<div class="main-website">
    <div class="main-content">
        @include('components.institute-header')
        <div class="container-fluid mb-4">
            <form method="GET" action="{{ route('allMarksheet') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Exam *</label>
                    <select name="examId" class="form-control" required>
                        <option value="">Select</option>
                        @php $examList = \App\Models\Exam::orderBy('id','DESC')->get(); @endphp
                        @foreach($examList as $ex)
                            <option value="{{ $ex->id }}" {{ $ex->id == request('examId') ? 'selected' : '' }}>{{ $ex->examName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Class *</label>
                    <select name="classId" class="form-control" required>
                        <option value="">Select</option>
                        @php $classList = \App\Models\classManage::orderBy('id','ASC')->get(); @endphp
                        @foreach($classList as $cl)
                            <option value="{{ $cl->id }}" {{ $cl->id == request('classId') ? 'selected' : '' }}>{{ $cl->className ?? ('Class-'.$cl->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Session</label>
                    <select name="sessionId" class="form-control">
                        <option value="">All</option>
                        @php $sessionList = \App\Models\sessionManage::orderBy('id','DESC')->get(); @endphp
                        @foreach($sessionList as $s)
                            <option value="{{ $s->id }}" {{ $s->id == request('sessionId') ? 'selected' : '' }}>{{ $s->session ?? ('Session-'.$s->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section/Group</label>
                    <select name="sectionId" class="form-control">
                        <option value="">All</option>
                        @php $sectionList = \App\Models\sectionManage::orderBy('id','ASC')->get(); @endphp
                        @foreach($sectionList as $sec)
                            <option value="{{ $sec->id }}" {{ $sec->id == request('sectionId') ? 'selected' : '' }}>{{ $sec->section ?? ('Section-'.$sec->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100">Load Results</button>
                </div>
                <div class="col-md-2">
                    @if($studentsLoaded)
                        <a href="{{ route('allMarksheet') }}" class="btn btn-warning w-100">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        @if(!$examId || !$classId)
            <div class="alert alert-info container">Please select required filters (Exam & Class) to view results.</div>
        @endif

        @if($examId && $classId)
            <div class="result-section">
                <div class="row">
                    <div class="col-12">
                        <div class="result-text text-center mt-2">
                            <h4 class="fw-bold">Class Result Sheet @if($exam) - {{ $exam->examName }} @endif</h4>
                            @if($exam && $exam->passingSystem == 1)
                                <p class="text-muted mb-0"><small>Feature-wise Passing System Applied</small></p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @php $subjectCount = count($subjects); @endphp
            @if($studentsLoaded && (count($passResults) + count($failResults)) === 0)
                <div class="alert alert-warning container">No marks found for the selected filters.</div>
            @endif

            @if(count($passResults) > 0)
                <h5 class="mt-4 fw-bold text-success">Passed Students ({{ count($passResults) }})</h5>
                <div class="table-responsive dark-border mb-5">
                    <table class="w-100 table-striped table-bordered text-center table">
                        <tr class="table-dark text-dark">
                            <th rowspan="3"><b>SL</b></th>
                            <th rowspan="3"><b>Student Id</b></th>
                            <th rowspan="3"><b>Name</b></th>
                            <th colspan="{{ max($subjectCount*6, 1) }}"><b>Subject</b></th>
                            <th rowspan="3"><b>Total</b></th>
                            <th rowspan="3"><b>Grade</b></th>
                            <th rowspan="3"><b>GPA</b></th>
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th colspan="6"><b>{{ $sub->subjectName }}</b></th>
                                @endforeach
                            @else
                                <th><b>No subjects</b></th>
                            @endif
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th><b>CQ</b></th>
                                    <th><b>MCQ</b></th>
                                    <th><b>P</b></th>
                                    <th><b>TOTAL</b></th>
                                    <th><b>GRADE</b></th>
                                    <th><b>POINT</b></th>
                                @endforeach
                            @else
                                <th><b>-</b></th>
                            @endif
                        </tr>
                        @foreach($passResults as $i=>$res)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $res['student']->stdId }}</td>
                                <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                @foreach($res['subjects'] as $sres)
                                    <td>{{ $sres['cq'] }}</td>
                                    <td>{{ $sres['mcq'] }}</td>
                                    <td>{{ $sres['practical'] }}</td>
                                    <td>{{ $sres['total'] }}</td>
                                    <td>{{ $sres['grade'] }}</td>
                                    <td>{{ $sres['gradePoint'] }}</td>
                                @endforeach
                                <td>{{ $res['totalMarks'] }}</td>
                                <td>{{ $res['finalLetter'] }}</td>
                                <td>{{ $res['finalGpa'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            @if(count($failResults) > 0)
                <h5 class="mt-4 fw-bold text-danger">Failed Students ({{ count($failResults) }})</h5>
                <div class="table-responsive dark-border mb-5">
                    <table class="w-100 table-striped table-bordered text-center table">
                        <tr class="table-dark text-dark">
                            <th rowspan="3"><b>SL</b></th>
                            <th rowspan="3"><b>Student Id</b></th>
                            <th rowspan="3"><b>Name</b></th>
                            <th colspan="{{ max($subjectCount*6, 1) }}"><b>Subject</b></th>
                            <th rowspan="3"><b>Total</b></th>
                            <th rowspan="3"><b>Grade</b></th>
                            <th rowspan="3"><b>GPA</b></th>
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th colspan="6"><b>{{ $sub->subjectName }}</b></th>
                                @endforeach
                            @else
                                <th><b>No subjects</b></th>
                            @endif
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th><b>CQ</b></th>
                                    <th><b>MCQ</b></th>
                                    <th><b>P</b></th>
                                    <th><b>TOTAL</b></th>
                                    <th><b>GRADE</b></th>
                                    <th><b>POINT</b></th>
                                @endforeach
                            @else
                                <th><b>-</b></th>
                            @endif
                        </tr>
                        @foreach($failResults as $i=>$res)
                            <tr class="table-danger">
                                <td>{{ $i+1 }}</td>
                                <td>{{ $res['student']->stdId }}</td>
                                <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                @foreach($res['subjects'] as $sres)
                                    <td>{{ $sres['cq'] }}</td>
                                    <td>{{ $sres['mcq'] }}</td>
                                    <td>{{ $sres['practical'] }}</td>
                                    <td>{{ $sres['total'] }}</td>
                                    <td>{{ $sres['grade'] }}</td>
                                    <td>{{ $sres['gradePoint'] }}</td>
                                @endforeach
                                <td>{{ $res['totalMarks'] }}</td>
                                <td>{{ $res['finalLetter'] }}</td>
                                <td>{{ $res['finalGpa'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        @endif

        
    </div>
</div>
@endsection
@extends('result.include')
@section('backTitle')
Get Mark
@endsection
@php
    $classData = \App\Models\classManage::find($classId);
    $sectionData = \App\Models\sectionManage::find($groupId);
    $sessionData = \App\Models\sessionManage::find($sessionId);
    $examData = \App\Models\Exam::find($examId);
    $subjectData = \App\Models\Subject::find($subjectId);
    if($classData):
        $className = $classData->className;
    else:
        $className = "-";
    endif;
    if($sectionData):
        $sectionName = $sectionData->section;
    else:
        $sectionName = "-";
    endif;
    if($sessionData):
        $session_name = $sessionData->session;
    else:
        $session_name = "-";
    endif;
    if($examData):
        $examName = $examData->examName;
    else:
        $examName = "-";
    endif;
    if($subjectData):
        $subjectName = $subjectData->subjectName;
    else:
        $subjectName = "-";
    endif;
@endphp
@section('backIndex')
    @if($studentList->count()>0)
        <form method="POST" class="card-body form form-group" action="{{ route('confirmMarks') }}">
                <div class="row">
                    <div class="col-6 col-md-4 mb-2"><b>Group/Section:</b>  {{ $sectionName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Class:</b>  {{ $className }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Session:</b> {{ $session_name }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Exam:</b> {{ $examName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Subject:</b> {{ $subjectName }}</div>
                </div>
                @csrf
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Roll</th>
                            <th>Student Name</th>
                            <th>CQ</th>
                            <th>MCQ</th>
                            <th>Practical</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($studentList->count()>0)
                        <input type="hidden" name="sessionId" value="{{ $sessionId }}">
                        <input type="hidden" name="classId" value="{{ $classId }}">
                        <input type="hidden" name="examId" value="{{ $examId }}">
                        <input type="hidden" name="groupId" value="{{ $groupId }}">
                        <input type="hidden" name="subjectId" value="{{ $subjectId }}">
                        @foreach($studentList as $std)
                        @php
                            $marksData = \App\Models\Marksheet::where(['sessionId'=>$sessionId,'classId'=>$classId,'groupId'=>$groupId,'studentId'=>$std->id,'examId'=>$examId,'subjectId'=>$subjectId])->first();
                            if($marksData):
                                $subjectMarks = $marksData->subjectMarks;
                                $objectMarks = $marksData->objectMarks;
                                $practicalMarks = $marksData->practicalMarks;
                            else:
                                $subjectMarks = "";
                                $objectMarks = "";
                                $practicalMarks = "";
                            endif;
                        @endphp
                        <input type="hidden" name="studentId[]" value="{{ $std->id }}">
                        <tr>
                            <td>{{ $std->stdId }}</td>
                            <td>{{ $std->rollNumber }}</td>
                            <td>{{ $std->fullName.' '.$std->sureName }}</td>
                            <td><input type="text" class="form-control" name="cqMarks[]" value="{{ $subjectMarks }}" id="" placeholder="Enter CQ Marks"></td>
                            <td><input type="text" class="form-control" name="mcqMarks[]" value="{{ $objectMarks }}" id="" placeholder="Enter MCQ Marks"></td>
                            <td><input type="text" class="form-control" name="practical[]" value="{{ $practicalMarks }}" id="" placeholder="Enter Practical Marks"></td>
                        </tr>
                        @endforeach
                        <div class="mb-4"><input type="submit" value="Save" class="btn btn-success"> <a href="{{ route('addMarks') }}" class="btn btn-primary">Back</a></div>
                        @else
                        <tr>
                            <td colspan="5">Sorry! No data found</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </form>
    @else
    <div class="alert alert-info">
        Sorry! No data found
    </div>
    <div class="mb-4"> <a href="{{ route('addMarks') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
    @endif
@endsection
@extends('result.include')
@section('backTitle')
Marksheet Generate
@endsection
@section('backIndex')
    @php
        if(isset($studentDetails)):
            $adminId        = $studentDetails->admitId;
            $stdName        = $studentDetails->firstName." ".$studentDetails->lastName;
            $rollNumber     = $studentDetails->roll;
            $fName          = $studentDetails->fathersName;
            $mName          = $studentDetails->mothersName;
            $sessionDetails = $studentDetails->session;
            $classDetails   = $studentDetails->class;

            $sessionName    = \App\Models\Session::find($sessionDetails)->sessionName;
            $className      = \App\Models\Classes::find($classDetails)->className;
        else:;
            $adminId        = "";
            $stdName        = "";
            $rollNumber     = "";
            $fName          = "";
            $mName          = "";
            $sessionDetails = "";
            $classDetails   = "";
            $sessionName    = "";
            $className      = "";
        endif;
        $examDetails    = \App\Models\Exam::find($examId);
        if(isset($examDetails)):
            $examName   = $examDetails->examName;
        else:
            $examName   = "";
        endif;

    @endphp
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4 marksheet">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto col-12 mx-auto">
                        <div class="card-body row">
                            @if(!empty($studentDetails))
                            <div class="card-header bg-light border-bottom-0 col-12">
                                <div class="item-title text-center">
                                    <h1 class="mb-2 fw-bold">Sonar Bangla University College</h1>
                                    <h3 class="mb-0 text-uppercase fw-bold">Academic Transcript</h3>
                                    <p class="text-left fw-bold">SL No- </p>
                                    <button class="btn btn-warning btn-sm d-print-none" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                                    <p class="fw-bold">{{ $examName }} </p>
                                </div>
                            </div>
                            <table class="col-8 col-md-8 mb-4  ">
                                <tbody>
                                    <tr>
                                        <th>Name</th>
                                        <td>:</td>
                                        <td>{{ $stdName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Father Name</th>
                                        <td>:</td>
                                        <td>{{ $fName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Mother Name</th>
                                        <td>:</td>
                                        <td>{{ $mName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Roll Number</th>
                                        <td>:</td>
                                        <td>{{ $rollNumber }}</td>
                                    </tr>
                                    <tr>
                                        <th>Session</th>
                                        <td>:</td>
                                        <td>{{ $sessionName }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <table class="col-4 col-md-4 mb-4 table-bordered text-center">
                                <thead>
                                    <th>
                                        Range of Marks<br>
                                        (Percentage)
                                    </th>
                                    <th>Grade</th>
                                    <th>Point</th>
                                </thead>
                                <tbody>
                                    @php 
                                        $gradeList = \App\Models\GradeList::orderBy('id','ASC')->get();
                                    @endphp
                                    @if(isset($gradeList))
                                        @foreach($gradeList as $gl)
                                            <tr>
                                                <td>{{ $gl->minMark }} - {{ $gl->maxMark }}</td>
                                                <td>{{ $gl->gradeName }}</td>
                                                <td>{{ $gl->gradePoint }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                            <h3 class="mt-4 mb-2 fw-bold">Main Subject</h3>
                            <table class="table table-bordered col-12 text-center">
                                <thead>
                                    <th>Subject Name</th>
                                    <th>Theory</th>
                                    <th>M.C.Q</th>
                                    <th>Practical</th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                    <th>Point</th>
                                </thead>
                                <tbody>
                                    @php
                                        $chkMarks = \App\Models\Marksheet::where(['studentId'=>$adminId,'classId'=>$classId,'examId'=>$examId,'sessionId'=>$sessionId])->get();
                                        $subtotalMarks = $chkMarks->sum('totalMarks');
                                    @endphp
                                    @if(isset($chkMarks))
                                        @foreach($chkMarks as $ckMark)
                                            @php 
                                                $subjectMarks   = $ckMark->subjectMarks;
                                                $objectMarks    = $ckMark->objectMarks;
                                                $parcticalMarks = $ckMark->practicalMarks;
                                                $totalMarks     = $subjectMarks+$objectMarks+$parcticalMarks;
                                                $grade          = $ckMark->laterGrade;
                                                $gradePoint     = $ckMark->gradePoint;

                                                if(empty($subjectMarks)):
                                                    $subjectMarks = "-";
                                                endif;

                                                if(empty($objectMarks)):
                                                    $objectMarks = "-";
                                                endif;

                                                if(empty($parcticalMarks)):
                                                    $parcticalMarks = "-";
                                                endif;

                                                $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
                                            @endphp
                                            @if($subjectDetails->subjectType=="Main")
                                            <tr>
                                                <td>{{ $subjectDetails->subjectName }}</td>
                                                <td>{{ $subjectMarks }}</td>
                                                <td>{{ $objectMarks }}</td>
                                                <td>{{ $parcticalMarks }}</td>
                                                <td>{{ $totalMarks }}</td>
                                                <td>{{ $grade }}</td>
                                                <td>{{ $gradePoint }}</td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                            <h3 class="mt-4 mb-2 fw-bold">Optional Subject</h3>
                            <table class="table table-bordered col-12 text-center">
                                <thead>
                                    <th>Subject Name</th>
                                    <th>Theory</th>
                                    <th>M.C.Q</th>
                                    <th>Practical</th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                    <th>Point</th>
                                </thead>
                                <tbody>
                                    @if(isset($chkMarks))
                                        @foreach($chkMarks as $ckMark)
                                            @php 
                                                $subjectMarks   = $ckMark->subjectMarks;
                                                $objectMarks    = $ckMark->objectMarks;
                                                $parcticalMarks = $ckMark->practicalMarks;
                                                $totalMarks     = $subjectMarks+$objectMarks+$parcticalMarks;
                                                $grade          = $ckMark->laterGrade;
                                                $gradePoint     = $ckMark->gradePoint;

                                                if(empty($subjectMarks)):
                                                    $subjectMarks = "-";
                                                endif;

                                                if(empty($objectMarks)):
                                                    $objectMarks = "-";
                                                endif;

                                                if(empty($parcticalMarks)):
                                                    $parcticalMarks = "-";
                                                endif;

                                                $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
                                            @endphp
                                            @if($subjectDetails->subjectType=="Optional")
                                            <tr>
                                                <td>{{ $subjectDetails->subjectName }}</td>
                                                <td>{{ $subjectMarks }}</td>
                                                <td>{{ $objectMarks }}</td>
                                                <td>{{ $parcticalMarks }}</td>
                                                <td>{{ $totalMarks }}</td>
                                                <td>{{ $grade }}</td>
                                                <td>{{ $gradePoint }}</td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                            <table class="col-12 mb-4  table table-bordered">
                                <thead>
                                        <th width="20%">Total Marks: {{ $subtotalMarks }}</th>
                                        <th width="20%">Letter Grade: </th>
                                        <th width="20%">Grade Point: </th>
                                        <th>Remark- </th>
                                </thead>
                            </table>

                            
                            <table class="col-3 my-4 mx-auto  table table-bordered text-center">
                                <tbody>
                                        <th style="padding-top:6rem">Guardian Signature</th>
                                </tbody>
                            </table>

                            <table class="col-3 mx-auto my-4 table table-bordered text-center">
                                <tbody>
                                        <th style="padding-top:6rem">Principal Signature</th>
                                </tbody>
                            </table>
                            @else
                            <div class="alert alert-info col-12">
                                Sorry! No data found
                                <div class="">
                                    <a href="{{ url()->previous() }}" class="btn btn-success">Go Back</a>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
@endsection
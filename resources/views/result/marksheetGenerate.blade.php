@extends('result.include')
@section('backTitle')
Marksheet Generate
@endsection
@section('backIndex')
    @php
        if($studentDetails):
            $adminId        = $studentDetails->admitId;
            echo $stdName        = $studentDetails->fullName." ".$studentDetails->sureName;
            $rollNumber     = $studentDetails->rollNumber;
            $fName          = $studentDetails->father;
            $mName          = $studentDetails->mother;
            $sessionDetails = $studentDetails->sessName;
            $classDetails   = $studentDetails->class;
            if($sessionDetails):
                $sessionName    = \App\Models\sessionManage::find($sessionDetails)->session;
            else:
                $sessionName    = "-";
            endif;
            if($classDetails):
                $className      = \App\Models\Classes::find($classDetails->className);
            else:
                $className    = "-";
            endif;
        else:
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
        
        $subtotalMarks = 0;
        if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count()) {
            foreach($studentDetails->marksheet as $ckMark) {
                $subjectMarks   = $ckMark->subjectMarks ?? 0;
                $objectMarks    = $ckMark->objectMarks ?? 0;
                $parcticalMarks = $ckMark->practicalMarks ?? 0;
                $subtotalMarks += ($subjectMarks + $objectMarks + $parcticalMarks);
            }
        }
    @endphp
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4 marksheet">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto col-12 mx-auto">
                        <div class="card-body row">
                            @if($studentDetails)
                            <div class="card-header bg-light border-bottom-0 col-12">
                                <div class="item-title text-center">
                                    <h1 class="mb-2 fw-bold">@if($config)   {{ $config->instituteName }} @else Jahanara Ayub Academy @endif</h1>
                                    <h3 class="mb-0 text-uppercase fw-bold">{{ $config->transcript_title ?? 'Academic Transcript' }}</h3>
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
                                    <th>Range of Marks</th>
                                    <th>Grade</th>
                                    <th>Point</th>
                                </thead>
                                <tbody>
                                    @php 
                                        $gradeList = \App\Models\GradeList::orderBy('gradePoint','DESC')->get();
                                    @endphp
                                    @if($gradeList)
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
@php
    $isFeatureWise = isset($examDetails) && $examDetails->passingSystem == 1;
    $finalLetterGrade = '-';
    $hasFail = false;
@endphp

<!-- Main Subject Table -->
<h3 class="mt-4 mb-2 fw-bold">Main Subject</h3>
<table class="table table-bordered col-12 text-center">
    <thead>
        <th>Subject Name</th>
        <th>Theory</th>
        <th>Grade</th>
        <th>M.C.Q</th>
        <th>Grade</th>
        <th>Practical</th>
        <th>Grade</th>
        <th>Total</th>
        <th>Grade</th>
        <th>Point</th>
    </thead>
    <tbody>
        @if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count()>0)
            @foreach($studentDetails->marksheet as $ckMark)
                @php
                    $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);

                    $fullCQ        = $subjectDetails->CQ ?? 0;
                    $fullMCQ       = $subjectDetails->MCQ ?? 0;
                    $fullPractical = $subjectDetails->Practical ?? 0;

                    $subjectMarks   = is_numeric($ckMark->subjectMarks) ? $ckMark->subjectMarks : 0;
                    $objectMarks    = is_numeric($ckMark->objectMarks) ? $ckMark->objectMarks : 0;
                    $parcticalMarks = is_numeric($ckMark->practicalMarks) ? $ckMark->practicalMarks : 0;

                    $cqPercent        = ($fullCQ > 0 && $subjectMarks > 0)   ? ($subjectMarks / $fullCQ) * 100 : null;
                    $mcqPercent       = ($fullMCQ > 0 && $objectMarks > 0)   ? ($objectMarks / $fullMCQ) * 100 : null;
                    $practicalPercent = ($fullPractical > 0 && $parcticalMarks > 0) ? ($parcticalMarks / $fullPractical) * 100 : null;

                    $cqGradeRow = $cqPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $cqPercent)->where('maxMark', '>=', $cqPercent)->first() : null;
                    $mcqGradeRow = $mcqPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $mcqPercent)->where('maxMark', '>=', $mcqPercent)->first() : null;
                    $practicalGradeRow = $practicalPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $practicalPercent)->where('maxMark', '>=', $practicalPercent)->first() : null;
                    

                    if($fullCQ == '-'):
                        $subjectMarks = '-';
                        $cqPercent = '-';
                        $cqGrade = '-';
                    endif;
                    if($fullMCQ == '-'):
                        $objectMarks = '-';
                        $mcqPercent = 'h';
                        $mcqGrade = '-';
                    endif;
                    if($fullPractical == '-'):
                        $parcticalMarks = '-';
                        $practicalPercent = '-';
                        $practicalGrade = '-';
                    endif;  

                    $cqGrade = $cqGradeRow ? $cqGradeRow->gradeName : '-';
                    $mcqGrade = $mcqGradeRow ? $mcqGradeRow->gradeName : '-';
                    $practicalGrade = $practicalGradeRow ? $practicalGradeRow->gradeName : '-';

                    $totalMarks     = $subjectMarks + $objectMarks + $parcticalMarks;
                    $gradeRow = \App\Models\GradeList::where('minMark', '<=', $totalMarks)
                        ->where('maxMark', '>=', $totalMarks)
                        ->first();
                    $grade      = $gradeRow ? $gradeRow->gradeName : '-';
                    $gradePoint = $gradeRow ? $gradeRow->gradePoint : '-';

                    // Feature Wise F logic
                    if($isFeatureWise && ($cqGrade == 'F' || $mcqGrade == 'F' || $practicalGrade == 'F')) {
                        $grade = 'F';
                        $hasFail = true;
                    }
                @endphp
                @if($subjectDetails->subjectType=="Main")
                <tr>
                    <td>{{ $subjectDetails->subjectName }}</td>
                    <td>{{ $subjectMarks }}</td>
                    <td>{{ $cqGrade }}</td>
                    <td>{{ $objectMarks }}</td>
                    <td>{{ $mcqGrade }}</td>
                    <td>{{ $parcticalMarks }}</td>
                    <td>{{ $practicalGrade }}</td>
                    <td>{{ $totalMarks }}</td>
                    <td>{{ $grade }}</td>
                    <td>{{ $gradePoint }}</td>
                </tr>
                @endif
            @endforeach
        @else
        <tr>
            <td colspan="7">No data found</td>
        </tr>
        @endif
    </tbody>
</table>

<!-- Optional Subject Table -->
<h3 class="mt-4 mb-2 fw-bold">Optional Subject</h3>
<table class="table table-bordered col-12 text-center">
    <thead>
        <th>Subject Name</th>
        <th>Theory</th>
        <th>Grade</th>
        <th>M.C.Q</th>
        <th>Grade</th>
        <th>Practical</th>
        <th>Grade</th>
        <th>Total</th>
        <th>Grade</th>
        <th>Point</th>
    </thead>
    <tbody>
        @php
            $hasOptional = false;
            if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count()>0) {
                foreach($studentDetails->marksheet as $ckMark) {
                    $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
                    if($subjectDetails && $subjectDetails->subjectType=="Optional") {
                        $hasOptional = true;
                        break;
                    }
                }
            }
        @endphp
        @if($hasOptional)
            @foreach($studentDetails->marksheet as $ckMark)
                @php
                    $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);

                    if($subjectDetails && $subjectDetails->subjectType=="Optional") {
                        $fullCQ        = $subjectDetails->CQ ?? '-';
                        $fullMCQ       = $subjectDetails->MCQ ?? '-';
                        $fullPractical = $subjectDetails->Practical ?? '-';

                        $subjectMarks   = is_numeric($ckMark->subjectMarks) ? $ckMark->subjectMarks : '-';
                        $objectMarks    = is_numeric($ckMark->objectMarks) ? $ckMark->objectMarks : '-';
                        $parcticalMarks = is_numeric($ckMark->practicalMarks) ? $ckMark->practicalMarks : '-';

                        $cqPercent        = ($fullCQ > 0 && $subjectMarks > 0)   ? ($subjectMarks / $fullCQ) * 100 : "-";
                        $mcqPercent       = ($fullMCQ > 0 && $objectMarks > 0)   ? ($objectMarks / $fullMCQ) * 100 : "-";
                        $practicalPercent = ($fullPractical > 0 && $parcticalMarks > 0) ? ($parcticalMarks / $fullPractical) * 100 : "-";

                        if($fullCQ == '-'):
                            $subjectMarks = '-';
                            $cqPercent = '-';
                            $cqGrade = '-';
                        endif;
                        if($fullMCQ == '-'):
                            $objectMarks = '-';
                            $mcqPercent = 'h';
                            $mcqGrade = '-';
                        endif;
                        if($fullPractical == '-'):
                            $parcticalMarks = '-';
                            $practicalPercent = '-';
                            $practicalGrade = '-';
                        endif;  

                        $cqGradeRow = $cqPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $cqPercent)->where('maxMark', '>=', $cqPercent)->first() : null;
                        $mcqGradeRow = $mcqPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $mcqPercent)->where('maxMark', '>=', $mcqPercent)->first() : null;
                        $practicalGradeRow = $practicalPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $practicalPercent)->where('maxMark', '>=', $practicalPercent)->first() : null;

                        $cqGrade = $cqGradeRow ? $cqGradeRow->gradeName : '-';
                        $mcqGrade = $mcqGradeRow ? $mcqGradeRow->gradeName : '-';
                        $practicalGrade = $practicalGradeRow ? $practicalGradeRow->gradeName : '-';

                        $totalMarks     = $subjectMarks + $objectMarks + $parcticalMarks;
                        $gradeRow = \App\Models\GradeList::where('minMark', '<=', $totalMarks)
                            ->where('maxMark', '>=', $totalMarks)
                            ->first();
                        $grade      = $gradeRow ? $gradeRow->gradeName : '-';
                        $gradePoint = $gradeRow ? $gradeRow->gradePoint : '-';

                        // Feature Wise F logic
                        if($isFeatureWise && ($cqGrade == 'F' || $mcqGrade == 'F' || $practicalGrade == 'F')) {
                            $grade = 'F';
                            $hasFail = true;
                        }
                    }
                @endphp
                @if($subjectDetails && $subjectDetails->subjectType=="Optional")
                <tr>
                    <td>{{ $subjectDetails->subjectName }}</td>
                    <td>{{ $subjectMarks }}</td>
                    <td>{{ $cqGrade }}</td>
                    <td>{{ $objectMarks }}</td>
                    <td>{{ $mcqGrade }}</td>
                    <td>{{ $parcticalMarks }}</td>
                    <td>{{ $practicalGrade }}</td>
                    <td>{{ $totalMarks }}</td>
                    <td>{{ $grade }}</td>
                    <td>{{ $gradePoint }}</td>
                </tr>
                @endif
            @endforeach
        @else
        <tr>
            <td colspan="10">No data found</td>
        </tr>
        @endif
    </tbody>
</table>
@php
    // If feature wise and any subject failed, set final grade and point to F
    $mainSubjects = [];
    $mainGradePoints = [];
    $hasFail = false;

    if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count() > 0) {
        foreach($studentDetails->marksheet as $ckMark) {
            $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
            if($subjectDetails && $subjectDetails->subjectType == "Main") {
                $subjectMarks   = is_numeric($ckMark->subjectMarks) ? $ckMark->subjectMarks : 0;
                $objectMarks    = is_numeric($ckMark->objectMarks) ? $ckMark->objectMarks : 0;
                $parcticalMarks = is_numeric($ckMark->practicalMarks) ? $ckMark->practicalMarks : 0;
                $totalMarks     = $subjectMarks + $objectMarks + $parcticalMarks;

                $gradeRow = \App\Models\GradeList::where('minMark', '<=', $totalMarks)
                    ->where('maxMark', '>=', $totalMarks)
                    ->first();

                $grade      = $gradeRow ? $gradeRow->gradeName : '-';
                $gradePoint = $gradeRow ? $gradeRow->gradePoint : 0;
                
                // Feature Wise F logic
                if($isFeatureWise && ($cqGrade == 'F' || $mcqGrade == 'F' || $practicalGrade == 'F')) {
                    $grade = 'F';
                    $hasFail = true;
                }
                
                if($grade == 'F') {
                    $hasFail = true;
                }
                $mainGradePoints[] = $gradePoint;
            }
            
            // Optional subject logic
            if($subjectDetails && $subjectDetails->subjectType == "Optional") {
                $optionalSubjectFound = true;
                $subjectMarks   = is_numeric($ckMark->subjectMarks) ? $ckMark->subjectMarks : 0;
                $objectMarks    = is_numeric($ckMark->objectMarks) ? $ckMark->objectMarks : 0;
                $parcticalMarks = is_numeric($ckMark->practicalMarks) ? $ckMark->practicalMarks : 0;
                $totalMarks     = $subjectMarks + $objectMarks + $parcticalMarks;

                $gradeRow = \App\Models\GradeList::where('minMark', '<=', $totalMarks)
                    ->where('maxMark', '>=', $totalMarks)
                    ->first();

                $optionalPoint = $gradeRow ? $gradeRow->gradePoint : 0;
            }else {
                $optionalSubjectFound = false;
                $optionalPoint = 0; 
            }
        }
    }
    // NCTB: If optional subject grade point > 2, only the excess over 2 is added to GPA
    $optionalBonus = 0;
    if($optionalSubjectFound && $optionalPoint > 2) {
        $optionalBonus = $optionalPoint - 2;
    }

    // Calculate GPA
    $mainSubjectCount = count($mainGradePoints);
    $finalGradePoint = $mainSubjectCount > 0 ? round((array_sum($mainGradePoints) + $optionalBonus) / $mainSubjectCount, 2) : '-';
    
    if($hasFail) {
        $finalLetterGrade = 'F';
    } elseif(count($mainGradePoints) > 0) {
        // Find letter grade by average point
        $gradeListRow = \App\Models\GradeList::where('gradePoint', '<=', $finalGradePoint)
            ->orderBy('gradePoint', 'desc')
            ->first();
        $finalLetterGrade = $gradeListRow ? $gradeListRow->gradeName : '-';
    } else {
        $finalLetterGrade = '-';
        $finalGradePoint = '-';
    }
@endphp

<table class="col-12 mb-4  table table-bordered">
    <thead>
        <th width="20%">Total Marks: {{ $subtotalMarks }}</th>
        <th width="20%">Letter Grade: {{ $finalLetterGrade }}</th>
        <th width="20%">Grade Point: {{ $finalGradePoint }}</th>
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
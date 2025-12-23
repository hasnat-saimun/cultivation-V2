@extends('result.include')
@section('backTitle')
Marksheet Generate
@endsection
@section('backIndex')
    <style>
        @page { size: A4; margin: 12mm; }
        html, body { background: #fff; }
        @media print {
            html, body { background: #fff !important; }
            #wrapper, .wrapper, .dashboard-page-one, .dashboard-content-one { background: #fff !important; }
            .d-print-none { display: none !important; }
            .marksheet .card { box-shadow: none !important; border: none !important; }
            .marksheet .transcript { border: none !important; }
            .signature-row { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 16px !important; width: 100% !important; }
            .marksheet table.table, .marksheet table.table-bordered { border-collapse: collapse !important; }
            .marksheet table.table thead th, .marksheet table.table-bordered thead th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .marksheet table.table th, .marksheet table.table td, .marksheet table.table-bordered th, .marksheet table.table-bordered td { border: 1px solid #000 !important; }
        }
        .marksheet .transcript {
            background: #fff;
            padding: 16px;
            border: 1px solid #e5e7eb;
        }
        .marksheet table.table, .marksheet table.table-bordered { font-size: 12px; border-collapse: collapse; }
        .marksheet table.table thead th, .marksheet table.table-bordered thead th { background: #f3f4f6; font-weight: 700; }
        .marksheet table.table th, .marksheet table.table td, .marksheet table.table-bordered th, .marksheet table.table-bordered td { padding: 6px; border: 1px solid #2d3748; }
        .marksheet h3 { margin-top: 8px; margin-bottom: 8px; }
        .signature-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px; width: 100%; }
        .signature-box { display: flex; flex-direction: column; justify-content: flex-end; align-items: center; min-height: 90px; page-break-inside: avoid; }
        .signature-space { height: 60px; }
        .signature-line { width: 80%; border-bottom: 1px solid #2d3748; }
        .signature-role { font-weight: 600; margin-bottom: 6px; }
        .signature-label { margin-top: 6px; font-size: 11px; color: #4a5568; }
        
    </style>
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
        $selectedReligiousId = (int) ($studentDetails->religiousSubjectId ?? 0);
        $classIdForResolve = (int) ($studentDetails->className ?? 0);
        $map = \App\Models\ReligiousSubjectDefault::where('classId', $classIdForResolve)->first();
        $effectiveReligiousId = $selectedReligiousId > 0 ? $selectedReligiousId : ($map ? (int)$map->subjectId : 0);
        if($effectiveReligiousId === 0){
            $fallback = \App\Models\Subject::where('isReligious', true)
                ->where(function($q) use ($classIdForResolve){ $q->where('assign_class', (string)$classIdForResolve)->orWhere('assign_class','0'); })
                ->orderBy('id')->first();
            if($fallback){ $effectiveReligiousId = (int)$fallback->id; }
        }
        if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count()) {
            foreach($studentDetails->marksheet as $ckMark) {
                $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
                // Skip other religious subjects; include only effective one (student-selected or class default)
                if($subjectDetails && ($subjectDetails->isReligious ?? false)){
                    if($effectiveReligiousId === 0 || (int)$subjectDetails->id !== $effectiveReligiousId){
                        continue;
                    }
                }
                $hasAny = is_numeric($ckMark->subjectMarks) || is_numeric($ckMark->objectMarks) || is_numeric($ckMark->practicalMarks);
                if($hasAny){
                    $subjectMarks   = is_numeric($ckMark->subjectMarks) ? $ckMark->subjectMarks : 0;
                    $objectMarks    = is_numeric($ckMark->objectMarks) ? $ckMark->objectMarks : 0;
                    $parcticalMarks = is_numeric($ckMark->practicalMarks) ? $ckMark->practicalMarks : 0;
                    $subtotalMarks += ($subjectMarks + $objectMarks + $parcticalMarks);
                }
            }
        }
    @endphp
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4 marksheet">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto col-12 mx-auto">
                        <div class="card-body row transcript">
                            @include('components.institute-header')
                            @if($studentDetails)
                            <div class="col-12 mb-3">
                                <div class="text-center">
                                    <h3 class="mb-0 text-uppercase fw-bold">{{ $config->transcript_title ?? 'Academic Transcript' }}</h3>
                                    <p class="fw-bold mb-1">{{ $examName }}</p>
                                    <button class="btn btn-warning btn-sm d-print-none" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
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
                    // Skip other religious subjects; include only effective one
                    if($subjectDetails && ($subjectDetails->isReligious ?? false)){
                        if($effectiveReligiousId === 0 || (int)$subjectDetails->id !== $effectiveReligiousId){
                            continue;
                        }
                    }

                    // Safely read full marks only if subject exists
                    $fullCQ = 0; $fullMCQ = 0; $fullPractical = 0;
                    if($subjectDetails){
                        $fullCQ        = $subjectDetails->CQ ?? 0;
                        $fullMCQ       = $subjectDetails->MCQ ?? 0;
                        $fullPractical = $subjectDetails->Practical ?? 0;
                    }

                    $hasAnyRow = is_numeric($ckMark->subjectMarks) || is_numeric($ckMark->objectMarks) || is_numeric($ckMark->practicalMarks);
                    $subjectMarks   = $hasAnyRow && is_numeric($ckMark->subjectMarks) ? (float)$ckMark->subjectMarks : null;
                    $objectMarks    = $hasAnyRow && is_numeric($ckMark->objectMarks) ? (float)$ckMark->objectMarks : null;
                    $parcticalMarks = $hasAnyRow && is_numeric($ckMark->practicalMarks) ? (float)$ckMark->practicalMarks : null;

                    $cqPercent        = ($fullCQ > 0 && $subjectMarks !== null)   ? ($subjectMarks / $fullCQ) * 100 : null;
                    $mcqPercent       = ($fullMCQ > 0 && $objectMarks !== null)   ? ($objectMarks / $fullMCQ) * 100 : null;
                    $practicalPercent = ($fullPractical > 0 && $parcticalMarks !== null) ? ($parcticalMarks / $fullPractical) * 100 : null;

                    $cqGradeRow = $cqPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $cqPercent)->where('maxMark', '>=', $cqPercent)->first() : null;
                    $mcqGradeRow = $mcqPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $mcqPercent)->where('maxMark', '>=', $mcqPercent)->first() : null;
                    $practicalGradeRow = $practicalPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $practicalPercent)->where('maxMark', '>=', $practicalPercent)->first() : null;

                    $cqGrade = $cqGradeRow ? $cqGradeRow->gradeName : '-';
                    $mcqGrade = $mcqGradeRow ? $mcqGradeRow->gradeName : '-';
                    $practicalGrade = $practicalGradeRow ? $practicalGradeRow->gradeName : '-';

                    $totalMarks = null; $grade = '-'; $gradePoint = null;
                    if($hasAnyRow){
                        $totalMarks     = ($subjectMarks ?: 0) + ($objectMarks ?: 0) + ($parcticalMarks ?: 0);
                        $gradeRow = \App\Models\GradeList::where('minMark', '<=', $totalMarks)
                            ->where('maxMark', '>=', $totalMarks)
                            ->first();
                        $grade      = $gradeRow ? $gradeRow->gradeName : '-';
                        $gradePoint = $gradeRow ? (float)$gradeRow->gradePoint : null;
                    }

                    // Feature Wise F logic and fail propagation
                    if($isFeatureWise && ($cqGrade === 'F' || $mcqGrade === 'F' || $practicalGrade === 'F')) {
                        $grade = 'F';
                        $gradePoint = 0.00;
                        $hasFail = true;
                    }
                    if($grade === 'F' || (is_numeric($gradePoint) && $gradePoint <= 0)) {
                        $hasFail = true;
                    }
                    $gradePointDisplay = ($grade === 'F') ? '0.00' : (is_numeric($gradePoint) ? number_format($gradePoint,2) : '-');
                @endphp
                @if($subjectDetails && $subjectDetails->subjectType=="Main")
                <tr>
                    <td>{{ $subjectDetails->subjectName }}</td>
                    <td>{{ $subjectMarks !== null ? $subjectMarks : '-' }}</td>
                    <td>{{ $cqGrade }}</td>
                    <td>{{ $objectMarks !== null ? $objectMarks : '-' }}</td>
                    <td>{{ $mcqGrade }}</td>
                    <td>{{ $parcticalMarks !== null ? $parcticalMarks : '-' }}</td>
                    <td>{{ $practicalGrade }}</td>
                    <td>{{ $totalMarks !== null ? $totalMarks : '-' }}</td>
                    <td>{{ $grade }}</td>
                    <td>{{ $gradePointDisplay }}</td>
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
                        $hasAnyOpt = is_numeric($ckMark->subjectMarks) || is_numeric($ckMark->objectMarks) || is_numeric($ckMark->practicalMarks);
                        if($hasAnyOpt){ $hasOptional = true; break; }
                    }
                }
            }
        @endphp
        @if($hasOptional)
            @foreach($studentDetails->marksheet as $ckMark)
                @php
                    $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
                    // Optional table unaffected by religious selection

                    if($subjectDetails && $subjectDetails->subjectType=="Optional") {
                        $fullCQ        = $subjectDetails->CQ ?? 0;
                        $fullMCQ       = $subjectDetails->MCQ ?? 0;
                        $fullPractical = $subjectDetails->Practical ?? 0;

                        $hasAnyRow = is_numeric($ckMark->subjectMarks) || is_numeric($ckMark->objectMarks) || is_numeric($ckMark->practicalMarks);
                        $subjectMarks   = $hasAnyRow && is_numeric($ckMark->subjectMarks) ? (float)$ckMark->subjectMarks : null;
                        $objectMarks    = $hasAnyRow && is_numeric($ckMark->objectMarks) ? (float)$ckMark->objectMarks : null;
                        $parcticalMarks = $hasAnyRow && is_numeric($ckMark->practicalMarks) ? (float)$ckMark->practicalMarks : null;

                        $cqPercent        = ($fullCQ > 0 && $subjectMarks !== null)   ? ($subjectMarks / $fullCQ) * 100 : null;
                        $mcqPercent       = ($fullMCQ > 0 && $objectMarks !== null)   ? ($objectMarks / $fullMCQ) * 100 : null;
                        $practicalPercent = ($fullPractical > 0 && $parcticalMarks !== null) ? ($parcticalMarks / $fullPractical) * 100 : null;

                        $cqGradeRow = $cqPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $cqPercent)->where('maxMark', '>=', $cqPercent)->first() : null;
                        $mcqGradeRow = $mcqPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $mcqPercent)->where('maxMark', '>=', $mcqPercent)->first() : null;
                        $practicalGradeRow = $practicalPercent !== null ? \App\Models\GradeList::where('minMark', '<=', $practicalPercent)->where('maxMark', '>=', $practicalPercent)->first() : null;

                        $cqGrade = $cqGradeRow ? $cqGradeRow->gradeName : '-';
                        $mcqGrade = $mcqGradeRow ? $mcqGradeRow->gradeName : '-';
                        $practicalGrade = $practicalGradeRow ? $practicalGradeRow->gradeName : '-';

                        $totalMarks = null; $grade = '-'; $gradePoint = null;
                        if($hasAnyRow){
                            $totalMarks     = ($subjectMarks ?: 0) + ($objectMarks ?: 0) + ($parcticalMarks ?: 0);
                            $gradeRow = \App\Models\GradeList::where('minMark', '<=', $totalMarks)
                                ->where('maxMark', '>=', $totalMarks)
                                ->first();
                            $grade      = $gradeRow ? $gradeRow->gradeName : '-';
                            $gradePoint = $gradeRow ? (float)$gradeRow->gradePoint : null;
                        }

                        // Feature Wise F logic and fail propagation
                        if($isFeatureWise && ($cqGrade === 'F' || $mcqGrade === 'F' || $practicalGrade === 'F')) {
                            $grade = 'F';
                            $gradePoint = 0.00;
                            $hasFail = true;
                        }
                        if($grade === 'F' || (is_numeric($gradePoint) && $gradePoint <= 0)) {
                            $hasFail = true;
                        }
                        $gradePointDisplay = ($grade === 'F') ? '0.00' : (is_numeric($gradePoint) ? number_format($gradePoint,2) : '-');
                    }
                @endphp
                @if($subjectDetails && $subjectDetails->subjectType=="Optional")
                <tr>
                    <td>{{ $subjectDetails->subjectName }}</td>
                    <td>{{ $subjectMarks !== null ? $subjectMarks : '-' }}</td>
                    <td>{{ $cqGrade }}</td>
                    <td>{{ $objectMarks !== null ? $objectMarks : '-' }}</td>
                    <td>{{ $mcqGrade }}</td>
                    <td>{{ $parcticalMarks !== null ? $parcticalMarks : '-' }}</td>
                    <td>{{ $practicalGrade }}</td>
                    <td>{{ $totalMarks !== null ? $totalMarks : '-' }}</td>
                    <td>{{ $grade }}</td>
                    <td>{{ $gradePointDisplay }}</td>
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
            // Include only the effective religious subject in final GPA tally
            if($subjectDetails && ($subjectDetails->isReligious ?? false)){
                if($effectiveReligiousId === 0 || (int)$subjectDetails->id !== $effectiveReligiousId){
                    continue;
                }
            }
            // Skip subjects with no marks from GPA and totals
            $hasAny = is_numeric($ckMark->subjectMarks) || is_numeric($ckMark->objectMarks) || is_numeric($ckMark->practicalMarks);
            if(!$hasAny){
                continue;
            }

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
            
            // Optional subject logic (only if marks exist)
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

                            
                            <div class="signature-row">
                                <div class="signature-box">
                                    <div class="signature-role">Guardian</div>
                                    <div class="signature-space"></div>
                                    <div class="signature-line"></div>
                                    <div class="signature-label">Signature</div>
                                </div>
                                <div class="signature-box">
                                    <div class="signature-role">Class Teacher</div>
                                    <div class="signature-space"></div>
                                    <div class="signature-line"></div>
                                    <div class="signature-label">Signature</div>
                                </div>
                                <div class="signature-box">
                                    <div class="signature-role">Principal</div>
                                    <div class="signature-space"></div>
                                    <div class="signature-line"></div>
                                    <div class="signature-label">Signature</div>
                                </div>
                            </div>
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
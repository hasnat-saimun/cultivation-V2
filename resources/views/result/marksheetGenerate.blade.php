@extends('result.include')
@section('backTitle')
Marksheet Generate
@endsection
@section('backIndex')
    <style>
        @page { size: A4 landscape; margin: 12mm; }
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
            .result-header-band { background: #f3f4f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #000 !important; }
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
                            @include('components.result-header')
                            @if($studentDetails)
                            <div class="col-12 mb-3">
                                <div class="text-center">
                                    <h3 class="mb-0 text-uppercase fw-bold">{{ $config->transcript_title ?? 'Academic Transcript' }}</h3>
                                    <p class="fw-bold mb-1">{{ $examName }}</p>
                                    
                                    @if(isset($maxMarkedSubjects, $studentMarkedSubjects) && empty($hideForMaxRule) && (int)$maxMarkedSubjects > 0)
                                        <div class="mt-2 d-print-none">
                                            <span class="badge bg-info text-dark">Counted subjects: {{ $studentMarkedSubjects }} / {{ $maxMarkedSubjects }}</span>
                                        </div>
                                    @endif
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
@if(!empty($hideForMaxRule))
    <div class="alert alert-warning col-12">
        Result not available: this student has marks in {{ $studentMarkedSubjects }} subject(s), which is less than the class maximum of {{ $maxMarkedSubjects }} subject(s) for this exam.
        <div class="mt-2">
            <a href="{{ url()->previous() }}" class="btn btn-success btn-sm">Go Back</a>
        </div>
    </div>
@else
@php
    $isFeatureWise = isset($examDetails) && $examDetails->passingSystem == 1;
    $finalLetterGrade = '-';
    $hasFail = false;
@endphp

<!-- Main Subject Tables (paired vs single) -->
<h3 class="mt-4 mb-2 fw-bold">Main Subject</h3>
@php
    // Build paired main subject rows
    $mainRowsRaw = [];
    $num = function($v){ return is_numeric($v) ? (float)$v : 0.0; };
    $baseAlias = function($alias){
        if(!$alias) return null; 
        $a = strtolower(trim($alias));
        $mapA = config('subject_pairs.aliases', []);
        $mapN = config('subject_pairs.names', []);
        if(isset($mapA[$a])){
            $mapped = strtolower(trim((string)$mapA[$a]));
            $mapped = str_replace(['-','  '],'_', $mapped);
            $mapped = preg_replace('/__+/', '_', $mapped);
            return trim($mapped, '_');
        }
        $orig = trim($alias);
        if(isset($mapN[$orig])){
            $mapped = strtolower(trim((string)$mapN[$orig]));
            $mapped = str_replace(['-','  '],'_', $mapped);
            $mapped = preg_replace('/__+/', '_', $mapped);
            return trim($mapped, '_');
        }
        $a = str_replace(['-','  '],'_', $a);
        $a = preg_replace('/(_1st|_first)/','', $a);
        $a = preg_replace('/(_2nd|_second)/','', $a);
        $a = preg_replace('/(_paper|_part|_p)$/','', $a);
        $a = preg_replace('/__+/', '_', $a);
        return trim($a, '_');
    };
    if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count()>0){
        foreach($studentDetails->marksheet as $ckMark){
            $sub = \App\Models\Subject::find($ckMark->subjectId);
            if(!$sub) continue;
            // Skip other religious subjects; include only effective one
            if(($sub->isReligious ?? false)){
                if($effectiveReligiousId === 0 || (int)$sub->id !== $effectiveReligiousId){ continue; }
            }
            if(($sub->subjectType ?? 'Main') !== 'Main') continue;
            $base = $baseAlias($sub->alias ?? $sub->subjectName);
            $mainRowsRaw[] = [
                'id' => (int)$sub->id,
                'name' => $sub->subjectName,
                'alias' => $sub->alias,
                'base' => $base,
                'fullCQ' => (float)($sub->CQ ?? 0),
                'fullMCQ' => (float)($sub->MCQ ?? 0),
                'fullPr' => (float)($sub->Practical ?? 0),
                'cq' => is_numeric($ckMark->subjectMarks) ? (float)$ckMark->subjectMarks : 0.0,
                'mcq' => is_numeric($ckMark->objectMarks) ? (float)$ckMark->objectMarks : 0.0,
                'pr' => is_numeric($ckMark->practicalMarks) ? (float)$ckMark->practicalMarks : 0.0,
            ];
        }
    }
    // Group by base alias and merge, preserving per-paper components
    $groups = [];
    foreach($mainRowsRaw as $r){ $b = $r['base'] ?? null; $key = $b ?: ('single_'.$r['id']); $groups[$key] = $groups[$key] ?? []; $groups[$key][] = $r; }
    $pairedMain = [];
    $subtotalMarksPaired = 0;
    foreach($groups as $key=>$items){
        $displayName = $items[0]['name'];
        $displayName = preg_replace('/\s*(1st|2nd)\s*Paper$/i','', $displayName);
        $fullCQ = 0; $fullMCQ = 0; $fullPr = 0; $cq = 0; $mcq = 0; $pr = 0;
        foreach($items as $it){ $fullCQ += $it['fullCQ']; $fullMCQ += $it['fullMCQ']; $fullPr += $it['fullPr']; $cq += $it['cq']; $mcq += $it['mcq']; $pr += $it['pr']; }
        $any = ($cq>0) || ($mcq>0) || ($pr>0);
        $total = $cq + $mcq + $pr;
        $grade = '-'; $gpDisp = '-';
        $cqGrade = '-'; $mcqGrade = '-'; $prGrade = '-';
        $failFw = false;
        if($any){
            // Compute component grades always for display
            $cqPct = $fullCQ>0 ? ($cq/$fullCQ)*100 : null;
            $mcqPct = $fullMCQ>0 ? ($mcq/$fullMCQ)*100 : null;
            $prPct = $fullPr>0 ? ($pr/$fullPr)*100 : null;
            $cGrades = [];
            foreach(['cqPct'=>$cqPct,'mcqPct'=>$mcqPct,'prPct'=>$prPct] as $k=>$v){
                if($v===null){ $cGrades[$k] = '-'; } else { $row = \App\Models\GradeList::forScore($v); $cGrades[$k] = $row ? $row->gradeName : '-'; }
            }
            // Paired subjects use total marks passing; ignore feature-wise fail
            $cqGrade = $cGrades['cqPct'] ?? '-';
            $mcqGrade = $cGrades['mcqPct'] ?? '-';
            $prGrade = $cGrades['prPct'] ?? '-';
            if($failFw){ $grade='F'; $gpDisp='0.00'; }
            else{ 
                $fullSum = $fullCQ + $fullMCQ + $fullPr;
                $percent = $fullSum>0 ? ($total/$fullSum)*100 : null;
                if($percent!==null){
                    $gRow = \App\Models\GradeList::forScore($percent);
                    $grade = $gRow ? $gRow->gradeName : '-';
                    $gpDisp = $gRow ? number_format($gRow->gradePoint,2) : '-';
                }
            }
            if($total>0){ $subtotalMarksPaired += $total; }
        }
        // Per-paper components
        $paper1 = isset($items[0]) ? [
            'cq' => ($items[0]['cq']>0 ? $items[0]['cq'] : '-'),
            'mcq' => ($items[0]['mcq']>0 ? $items[0]['mcq'] : '-'),
            'pr' => ($items[0]['pr']>0 ? $items[0]['pr'] : '-'),
            // compute component letters per paper
            'cqGrade' => ($items[0]['fullCQ']>0 && $items[0]['cq']>0) ? ((\App\Models\GradeList::forScore(($items[0]['cq']/$items[0]['fullCQ'])*100))->gradeName ?? '-') : '-',
            'mcqGrade' => ($items[0]['fullMCQ']>0 && $items[0]['mcq']>0) ? ((\App\Models\GradeList::forScore(($items[0]['mcq']/$items[0]['fullMCQ'])*100))->gradeName ?? '-') : '-',
            'prGrade' => ($items[0]['fullPr']>0 && $items[0]['pr']>0) ? ((\App\Models\GradeList::forScore(($items[0]['pr']/$items[0]['fullPr'])*100))->gradeName ?? '-') : '-',
        ] : null;
        $paper2 = isset($items[1]) ? [
            'cq' => ($items[1]['cq']>0 ? $items[1]['cq'] : '-'),
            'mcq' => ($items[1]['mcq']>0 ? $items[1]['mcq'] : '-'),
            'pr' => ($items[1]['pr']>0 ? $items[1]['pr'] : '-'),
            'cqGrade' => ($items[1]['fullCQ']>0 && $items[1]['cq']>0) ? ((\App\Models\GradeList::forScore(($items[1]['cq']/$items[1]['fullCQ'])*100))->gradeName ?? '-') : '-',
            'mcqGrade' => ($items[1]['fullMCQ']>0 && $items[1]['mcq']>0) ? ((\App\Models\GradeList::forScore(($items[1]['mcq']/$items[1]['fullMCQ'])*100))->gradeName ?? '-') : '-',
            'prGrade' => ($items[1]['fullPr']>0 && $items[1]['pr']>0) ? ((\App\Models\GradeList::forScore(($items[1]['pr']/$items[1]['fullPr'])*100))->gradeName ?? '-') : '-',
        ] : null;
        $pairedMain[] = [
            'name' => $displayName,
            'paired' => (count($items) >= 2),
            'paper1' => $paper1,
            'paper2' => $paper2,
            'cq' => $any ? ($cq>0 ? $cq : '-') : '-',
            'mcq' => $any ? ($mcq>0 ? $mcq : '-') : '-',
            'pr' => $any ? ($pr>0 ? $pr : '-') : '-',
            'total' => $any ? ($total>0 ? $total : '-') : '-',
            'grade' => $grade,
            'gradePoint' => $gpDisp,
            'fail' => $failFw || ($grade==='F'),
            'cqGrade' => $cqGrade,
            'mcqGrade' => $mcqGrade,
            'prGrade' => $prGrade,
            'fullCQ' => $fullCQ,
            'fullMCQ' => $fullMCQ,
            'fullPr' => $fullPr,
        ];
    }
    // Override subtotal to use paired sums for consistency
    $subtotalMarks = $subtotalMarksPaired;
@endphp
@php
    $pairedRows = array_values(array_filter($pairedMain, function($r){ return !empty($r['paired']); }));
    $singleRows = array_values(array_filter($pairedMain, function($r){ return empty($r['paired']); }));
@endphp

@if(count($pairedRows) > 0)
<h5 class="mt-3 fw-bold">Paired Subjects</h5>
<table class="table table-bordered col-12 text-center">
    <thead>
        <th>Subject Name</th>
        <th>CQ-1</th>
        <th>MCQ-1</th>
        <th>P-1</th>
        <th>CQ-2</th>
        <th>MCQ-2</th>
        <th>P-2</th>
        <th>Total</th>
        <th>Grade</th>
        <th>Point</th>
    </thead>
    <tbody>
        @if(count($pairedRows) > 0)
            @foreach($pairedRows as $row)
                @php
                    // We already computed component grades in paired array
                @endphp
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>
                        {{ $row['paper1']['cq'] ?? '-' }}
                        @if(isset($row['paper1']['cqGrade']))<div><small class="text-muted">{{ $row['paper1']['cqGrade'] }}</small></div>@endif
                    </td>
                    <td>
                        {{ $row['paper1']['mcq'] ?? '-' }}
                        @if(isset($row['paper1']['mcqGrade']))<div><small class="text-muted">{{ $row['paper1']['mcqGrade'] }}</small></div>@endif
                    </td>
                    <td>
                        {{ $row['paper1']['pr'] ?? '-' }}
                        @if(isset($row['paper1']['prGrade']))<div><small class="text-muted">{{ $row['paper1']['prGrade'] }}</small></div>@endif
                    </td>
                    <td>
                        {{ $row['paper2']['cq'] ?? '-' }}
                        @if(isset($row['paper2']['cqGrade']))<div><small class="text-muted">{{ $row['paper2']['cqGrade'] }}</small></div>@endif
                    </td>
                    <td>
                        {{ $row['paper2']['mcq'] ?? '-' }}
                        @if(isset($row['paper2']['mcqGrade']))<div><small class="text-muted">{{ $row['paper2']['mcqGrade'] }}</small></div>@endif
                    </td>
                    <td>
                        {{ $row['paper2']['pr'] ?? '-' }}
                        @if(isset($row['paper2']['prGrade']))<div><small class="text-muted">{{ $row['paper2']['prGrade'] }}</small></div>@endif
                    </td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ $row['grade'] }}</td>
                    <td>{{ $row['gradePoint'] }}</td>
                </tr>
            @endforeach
        @else
            <tr><td colspan="10">No paired subjects</td></tr>
        @endif
    </tbody>
</table>
@endif

@if(count($singleRows) > 0)
<h5 class="mt-3 fw-bold">Single Subjects</h5>
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
        @foreach($singleRows as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['cq'] }}</td>
                <td>{{ $row['cqGrade'] }}</td>
                <td>{{ $row['mcq'] }}</td>
                <td>{{ $row['mcqGrade'] }}</td>
                <td>{{ $row['pr'] }}</td>
                <td>{{ $row['prGrade'] }}</td>
                <td>{{ $row['total'] }}</td>
                <td>{{ $row['grade'] }}</td>
                <td>{{ $row['gradePoint'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

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

    if(isset($pairedMain) && count($pairedMain) > 0) {
        foreach($pairedMain as $row) {
            $hasAny = ($row['cq'] !== '-') || ($row['mcq'] !== '-') || ($row['pr'] !== '-') || ($row['total'] !== '-');
            if(!$hasAny) { continue; }
            $gradePoint = ($row['grade'] === 'F') ? 0 : (is_numeric($row['gradePoint']) ? (float)$row['gradePoint'] : 0);
            if($row['grade'] === 'F'){ $hasFail = true; }
            $mainGradePoints[] = $gradePoint;
        }
    }
    
    // Optional subject logic (unchanged)
    if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count() > 0) {
        foreach($studentDetails->marksheet as $ckMark) {
            $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
            $hasAny = is_numeric($ckMark->subjectMarks) || is_numeric($ckMark->objectMarks) || is_numeric($ckMark->practicalMarks);
            if(!$hasAny){ continue; }
            if($subjectDetails && $subjectDetails->subjectType == "Optional") {
                $optionalSubjectFound = true;
                $subjectMarks   = is_numeric($ckMark->subjectMarks) ? $ckMark->subjectMarks : 0;
                $objectMarks    = is_numeric($ckMark->objectMarks) ? $ckMark->objectMarks : 0;
                $parcticalMarks = is_numeric($ckMark->practicalMarks) ? $ckMark->practicalMarks : 0;
                $totalMarks     = $subjectMarks + $objectMarks + $parcticalMarks;
                $gradeRow = \App\Models\GradeList::where('minMark', '<=', $totalMarks)->where('maxMark', '>=', $totalMarks)->first();
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

@endif

                            
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
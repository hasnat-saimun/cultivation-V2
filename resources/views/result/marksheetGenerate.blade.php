@extends('result.include')
@section('backTitle')
Marksheet Generate
@endsection
@section('backIndex')
    <style>
        /* Print: A4 portrait for single result */
        @page { size: A4 portrait; margin: 12mm; }
        html, body { background: #fff; }
        @media print {
            html, body { background: #fff !important; }
            #wrapper, .wrapper, .dashboard-page-one, .dashboard-content-one { background: #fff !important; }
            .d-print-none { display: none !important; }
            /* Hide admin page title/nav on print */
            .breadcrumbs-area, .header-menu-one, .navbar { display: none !important; }
            .marksheet .card { box-shadow: none !important; border: none !important; }
            .marksheet .transcript { border: none !important; }
            .signature-row { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 16px !important; width: 100% !important; }
            .marksheet table.table, .marksheet table.table-bordered { border-collapse: collapse !important; }
            .marksheet table.table thead th, .marksheet table.table-bordered thead th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .marksheet table.table th, .marksheet table.table td, .marksheet table.table-bordered th, .marksheet table.table-bordered td { border: 1px solid #000 !important; }
            .result-header-band { background: #f3f4f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #000 !important; }
            /* Compact institute header for single result print */
            .report-header { gap: 6px !important; margin-bottom: 6px !important; padding-bottom: 4px !important; border-bottom: 1px solid #cbd5e1 !important; }
            .report-header .hdr-logo { height: 48px !important; }
            .report-header .name { font-size: 18px !important; }
            .report-header .subline, .report-header .contacts { font-size: 11px !important; }
            /* Failed subjects: compact two-column print list */
            .failed-subjects { font-size: 11px !important; }
            .failed-subjects h4 { margin-bottom: 4px !important; }
            .failed-subjects ul { -webkit-columns: 2 !important; columns: 2 !important; column-gap: 12px !important; list-style-position: inside !important; margin: 4px 0 !important; padding-left: 0 !important; }
            .failed-subjects li { break-inside: avoid !important; margin: 0 0 3px !important; padding: 0 !important; }
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
        .sign-image { height: 44px; width: auto; max-width: 140px; object-fit: contain; margin-bottom: 8px; }
        /* Student info table: tidy labels and values */
        .student-info th { width: 140px; white-space: nowrap; }
        .student-info td { word-break: break-word; overflow-wrap: anywhere; }
        /* Screen: failed subjects spacing */
        .failed-subjects h4 { margin-bottom: 6px; }
        .failed-subjects ul { margin: 6px 0; }
        
    </style>
    @php
        if($studentDetails):
            // Map to newAdmission fields
            $adminId        = $studentDetails->stdId ?? ($studentDetails->id ?? null);
            $stdName        = ($studentDetails->fullName ?? '')." ".($studentDetails->sureName ?? '');
            $rollNumber     = $studentDetails->rollNumber ?? '';
            $fName          = $studentDetails->fatherName ?? ($studentDetails->father ?? '');
            $mName          = $studentDetails->motherName ?? ($studentDetails->mother ?? '');
            $sessionDetails = $studentDetails->sessName ?? null;
            $classDetails   = $studentDetails->className ?? null;
            // Resolve session name robustly (id, object, or string)
            if(!empty($sessionDetails)){
                if(is_numeric($sessionDetails)){
                    $sessModel = \App\Models\sessionManage::find($sessionDetails);
                    $sessionName = $sessModel ? ($sessModel->session ?? ('Session-'.$sessModel->id)) : '-';
                } elseif(is_object($sessionDetails)) {
                    $sessionName = $sessionDetails->session ?? '-';
                } else {
                    $sessionName = (string)$sessionDetails;
                }
            } else { $sessionName = "-"; }
            // Resolve class name robustly (support classManage or Classes model)
            try{
                $className = '-';
                // Candidates: explicit className id, class object/id, or string name
                $classIdCandidates = [];
                if(isset($studentDetails->className)){
                    if(is_numeric($studentDetails->className)) $classIdCandidates[] = (int)$studentDetails->className;
                }
                if(!empty($classDetails)){
                    if(is_numeric($classDetails)) $classIdCandidates[] = (int)$classDetails;
                    elseif(is_object($classDetails) && isset($classDetails->className) && is_numeric($classDetails->className)) $classIdCandidates[] = (int)$classDetails->className;
                }
                $clModel = null;
                foreach($classIdCandidates as $cid){
                    $clModel = \App\Models\classManage::find($cid);
                    if(!$clModel) $clModel = \App\Models\Classes::find($cid);
                    if($clModel) break;
                }
                if($clModel){
                    $className = $clModel->className ?? ('Class-'.$clModel->id);
                } else {
                    // Fallback to string if provided
                    if(is_string($classDetails) && trim($classDetails) !== '') $className = (string)$classDetails;
                }
            }catch(\Throwable $e){ $className = '-'; }
            // Resolve section/group name robustly
            $sectionName = "-";
            try{
                $secCandidates = [];
                if(isset($studentDetails->sectionId) && is_numeric($studentDetails->sectionId)) $secCandidates[] = (int)$studentDetails->sectionId;
                if(isset($studentDetails->sectionName) && is_numeric($studentDetails->sectionName)) $secCandidates[] = (int)$studentDetails->sectionName;
                if(isset($studentDetails->section) && is_numeric($studentDetails->section)) $secCandidates[] = (int)$studentDetails->section;
                if(empty($secCandidates) && isset($studentDetails->section) && is_object($studentDetails->section)){
                    $maybeId = $studentDetails->section->id ?? null;
                    if(is_numeric($maybeId)) $secCandidates[] = (int)$maybeId;
                }
                $secModel = null;
                foreach($secCandidates as $sid){
                    $secModel = \App\Models\sectionManage::find($sid);
                    if($secModel) break;
                }
                if($secModel){
                    $sectionName = $secModel->section ?? ('Section-'.$secModel->id);
                } else {
                    // Fallback to string if present
                    if(isset($studentDetails->sectionName) && is_string($studentDetails->sectionName) && trim($studentDetails->sectionName) !== ''){
                        $sectionName = (string)$studentDetails->sectionName;
                    } elseif(isset($studentDetails->section) && is_string($studentDetails->section) && trim($studentDetails->section) !== ''){
                        $sectionName = (string)$studentDetails->section;
                    }
                }
            }catch(\Throwable $e){ $sectionName = '-'; }
            // Resolve department name from student table field
            $departmentName = '-';
            try{
                $deptCandidates = [];
                if(isset($studentDetails->departmentName) && is_numeric($studentDetails->departmentName)) $deptCandidates[] = (int)$studentDetails->departmentName;
                if(isset($studentDetails->departmentId) && is_numeric($studentDetails->departmentId)) $deptCandidates[] = (int)$studentDetails->departmentId;
                $deptModel = null;
                foreach($deptCandidates as $did){
                    $deptModel = \App\Models\Department::find($did);
                    if($deptModel) break;
                }
                if($deptModel){
                    $departmentName = $deptModel->departmentName ?? ('Department-'.$deptModel->id);
                } elseif(isset($studentDetails->departmentName) && is_string($studentDetails->departmentName) && trim($studentDetails->departmentName) !== '') {
                    $departmentName = (string)$studentDetails->departmentName;
                }
            }catch(\Throwable $e){ $departmentName = '-'; }
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
            $sectionName    = "";
            $departmentName = "";
        endif;
        $examDetails    = \App\Models\Exam::find($examId);
        if(isset($examDetails)):
            $examName   = $examDetails->examName;
        else:
            $examName   = "";
        endif;
        
        $subtotalMarks = 0;
        $selectedReligiousId = (int) ($studentDetails->religiousSubjectId ?? 0);
        $selectedFourthSubjectId = (int) ($studentDetails->fourthSubjectId ?? 0);
        $classIdForResolve = (int) ($studentDetails->className ?? 0);
        $map = \App\Models\ReligiousSubjectDefault::where('classId', $classIdForResolve)->first();
        $effectiveReligiousId = $selectedReligiousId > 0 ? $selectedReligiousId : ($map ? (int)$map->subjectId : 0);
        if($effectiveReligiousId === 0){
            $islamPreferred = \App\Models\Subject::where('isReligious', true)
                ->where(function($q){
                    $q->whereRaw('LOWER(subjectName) LIKE ?', ['%islam%'])
                      ->orWhereRaw('LOWER(alias) LIKE ?', ['%islam%']);
                })
                ->where(function($q) use ($classIdForResolve){ $q->where('assign_class', (string)$classIdForResolve)->orWhere('assign_class','0'); })
                ->orderByRaw("CASE WHEN LOWER(subjectName) LIKE '%111%' OR LOWER(alias) LIKE '%111%' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->first();
            $fallback = $islamPreferred ?: \App\Models\Subject::where('isReligious', true)
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
                @include('components.institute-header')
                <div class="container-fluid d-print-none mb-2 text-end">
                    <button type="button" class="btn btn-warning btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4 marksheet">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto col-12 mx-auto">
                        <div class="card-body row transcript">
                            @if($studentDetails)
                            <div class="col-12 mb-3">
                                <div class="text-center">
                                    <h3 class="mb-0 text-uppercase fw-bold">{{ $config->transcript_title ?? 'Academic Transcript' }}</h3>
                                    <p class="fw-bold mb-1">{{ $examName }}</p>
                                    
                                    @if(isset($maxMarkedSubjects, $studentMarkedSubjects) && empty($hideForMaxRule) && (int)$maxMarkedSubjects > 0)
                                        <div class="mt-2 d-print-none">
                                            <span class="badge bg-info text-white">Counted subjects: {{ $studentMarkedSubjects }} / {{ $maxMarkedSubjects }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <table class="col-8 col-md-8 mb-4">
                                <tbody>
                                    <tr>
                                        <th>Student ID</th>
                                        <td>:</td>
                                        <td colspan="4">{{ !empty($adminId) ? $adminId : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td>:</td>
                                        <td colspan="4">{{ $stdName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Father Name</th>
                                        <td>:</td>
                                        <td colspan="4">{{ $fName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Mother Name</th>
                                        <td>:</td>
                                        <td colspan="4">{{ $mName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Roll Number</th>
                                        <td>:</td>
                                        <td>{{ is_numeric($rollNumber) ? str_pad((string)((int)$rollNumber), 2, '0', STR_PAD_LEFT) : $rollNumber }}</td>
                                        <th>Session</th>
                                        <td>:</td>
                                        <td>{{ $sessionName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Class</th>
                                        <td>:</td>
                                        <td>{{ $className }}</td>
                                        <th>Section</th>
                                        <td>:</td>
                                        <td>{{ $sectionName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Department</th>
                                        <td>:</td>
                                        <td colspan="4">{{ $departmentName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Merit Position</th>
                                        <td>:</td>
                                        <td colspan="4">{{ isset($meritRank) && is_numeric($meritRank) ? str_pad((string)((int)$meritRank), 2, '0', STR_PAD_LEFT) : '-' }}</td>
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
    <div class="alert alert-warning col-12 d-print-none">
        Notice: this student has marks in {{ $studentMarkedSubjects }} subject(s), while class maximum is {{ $maxMarkedSubjects }} for this exam.
        Transcript is shown with available marks.
    </div>
@endif
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

    // Custom transcript subject order:
    // Bangla (excluding Bangladesh), English, Math, General Science,
    // Social Science, Religious Subject, Others, ICT (always last).
    $subjectOrderRank = function($name){
        $n = strtolower(trim((string)$name));
        if($n === '') return 700;
        if((strpos($n, 'information') !== false && strpos($n, 'communication') !== false) || strpos($n, 'ict') !== false){
            return 900;
        }
        if(strpos($n, 'bangla') !== false && strpos($n, 'bangladesh') === false){ return 100; }
        if(strpos($n, 'english') !== false){ return 200; }
        if(strpos($n, 'mathematics') !== false || preg_match('/\bmath\b/', $n)){ return 300; }
        if(strpos($n, 'general science') !== false){ return 400; }
        if(strpos($n, 'social science') !== false || strpos($n, 'bangladesh') !== false || strpos($n, 'bgs') !== false){ return 500; }
        if(strpos($n, 'religion') !== false || strpos($n, 'islam') !== false || strpos($n, 'hindu') !== false || strpos($n, 'buddh') !== false || strpos($n, 'christ') !== false){
            return 600;
        }
        return 700;
    };
    usort($pairedMain, function($a, $b) use ($subjectOrderRank){
        $ra = $subjectOrderRank($a['name'] ?? '');
        $rb = $subjectOrderRank($b['name'] ?? '');
        if($ra !== $rb){ return $ra <=> $rb; }
        return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    });

    // Override subtotal to use paired sums for consistency
    $subtotalMarks = $subtotalMarksPaired;
@endphp
@php
    $pairedRows = array_values(array_filter($pairedMain, function($r){ return !empty($r['paired']); }));
    $singleRows = array_values(array_filter($pairedMain, function($r){ return empty($r['paired']); }));
@endphp

@php $allRows = $pairedMain; @endphp
<table class="table table-bordered col-12 text-center">
    <thead>
        <th>Subject Name</th>
        <th>Theory</th>
        <th>MCQ</th>
        <th>Practical</th>
        <th>Total</th>
        <th>Grade</th>
        <th>Point</th>
    </thead>
    <tbody>
        @forelse($allRows as $row)
            @php
                $numVal = function($v){ return is_numeric($v) ? (float)$v : 0.0; };
                $cqDisp = '-'; $mcqDisp = '-'; $prDisp = '-';
                if(($row['fullCQ'] ?? 0) > 0){
                    if(!empty($row['paired'])){
                        $cq1 = $numVal($row['paper1']['cq'] ?? null); $cq2 = $numVal($row['paper2']['cq'] ?? null);
                        $cqDisp = '(' . $cq1 . ' + ' . $cq2 . ') = ' . ($cq1 + $cq2);
                    } else {
                        $cqDisp = is_numeric($row['cq']) ? $row['cq'] : '-';
                    }
                }
                if(($row['fullMCQ'] ?? 0) > 0){
                    if(!empty($row['paired'])){
                        $m1 = $numVal($row['paper1']['mcq'] ?? null); $m2 = $numVal($row['paper2']['mcq'] ?? null);
                        $mcqDisp = '(' . $m1 . ' + ' . $m2 . ') = ' . ($m1 + $m2);
                    } else {
                        $mcqDisp = is_numeric($row['mcq']) ? $row['mcq'] : '-';
                    }
                }
                if(($row['fullPr'] ?? 0) > 0){
                    if(!empty($row['paired'])){
                        $p1 = $numVal($row['paper1']['pr'] ?? null); $p2 = $numVal($row['paper2']['pr'] ?? null);
                        $prDisp = '(' . $p1 . ' + ' . $p2 . ') = ' . ($p1 + $p2);
                    } else {
                        $prDisp = is_numeric($row['pr']) ? $row['pr'] : '-';
                    }
                }
            @endphp
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $cqDisp }}</td>
                <td>{{ $mcqDisp }}</td>
                <td>{{ $prDisp }}</td>
                <td>{{ $row['total'] }}</td>
                <td>{{ $row['grade'] }}</td>
                <td>
                    @php
                        $gpDisplay = ($row['grade'] === 'F') ? '0.00'
                            : (is_numeric($row['gradePoint']) ? number_format($row['gradePoint'], 2) : ($row['gradePoint'] ?? '-'));
                    @endphp
                    {{ $gpDisplay }}
                </td>
            </tr>
        @empty
            <tr><td colspan="7">No main subjects</td></tr>
        @endforelse
    </tbody>
</table>

@php
    // Collect failed main subject names for display
    $failedMainNames = [];
    if(isset($pairedMain) && count($pairedMain) > 0){
        foreach($pairedMain as $row){
            $hasAny = ($row['total'] !== '-') || ($row['cq'] !== '-') || ($row['mcq'] !== '-') || ($row['pr'] !== '-');
            if(!$hasAny) { continue; }
            if(($row['grade'] ?? '-') === 'F' || !empty($row['fail'])){
                $failedMainNames[] = $row['name'];
            }
        }
    }
    // Ensure unique names in case of duplicates
    $failedMainNames = array_values(array_unique($failedMainNames));
@endphp

<!-- Optional Subject Table -->
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
        @php
            $hasOptional = false;
            // Track failed optional subjects
            $failedOptionalNames = [];
            // Track optional subtotal to include in overall Total Marks
            $optionalSubtotalSum = 0;
            if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count()>0) {
                foreach($studentDetails->marksheet as $ckMark) {
                    $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
                    if($subjectDetails && $subjectDetails->subjectType=="Optional" && (int)$subjectDetails->id === $selectedFourthSubjectId) {
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

                    if($subjectDetails && $subjectDetails->subjectType=="Optional" && (int)$subjectDetails->id === $selectedFourthSubjectId) {
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

                        $cqGradeRow = $cqPercent !== null ? \App\Models\GradeList::forScore((float)$cqPercent) : null;
                        $mcqGradeRow = $mcqPercent !== null ? \App\Models\GradeList::forScore((float)$mcqPercent) : null;
                        $practicalGradeRow = $practicalPercent !== null ? \App\Models\GradeList::forScore((float)$practicalPercent) : null;

                        $cqGrade = $cqGradeRow ? $cqGradeRow->gradeName : '-';
                        $mcqGrade = $mcqGradeRow ? $mcqGradeRow->gradeName : '-';
                        $practicalGrade = $practicalGradeRow ? $practicalGradeRow->gradeName : '-';

                        $totalMarks = null; $grade = '-'; $gradePoint = null;
                        if($hasAnyRow){
                            $totalMarks     = ($subjectMarks ?: 0) + ($objectMarks ?: 0) + ($parcticalMarks ?: 0);
                            $optionalFullMark = ((float)$fullCQ + (float)$fullMCQ + (float)$fullPractical);
                            $optionalPercent = $optionalFullMark > 0 ? (($totalMarks / $optionalFullMark) * 100) : null;
                            $gradeRow = $optionalPercent !== null ? \App\Models\GradeList::forScore((float)$optionalPercent) : null;
                            $grade      = $gradeRow ? $gradeRow->gradeName : '-';
                            $gradePoint = $gradeRow ? (float)$gradeRow->gradePoint : null;
                            // accumulate optional total for final Total Marks display
                            if(is_numeric($totalMarks) && $totalMarks > 0){ $optionalSubtotalSum += (float)$totalMarks; }
                        }

                        // Feature Wise F logic and fail propagation
                        if($isFeatureWise && ($cqGrade === 'F' || $mcqGrade === 'F' || $practicalGrade === 'F')) {
                            $grade = 'F';
                            $gradePoint = 0.00;
                            $hasFail = true;
                        }
                        if($grade === 'F' || (is_numeric($gradePoint) && $gradePoint <= 0)) {
                            $hasFail = true;
                            $failedOptionalNames[] = $subjectDetails->subjectName;
                        }
                        $gradePointDisplay = ($grade === 'F') ? '0.00' : (is_numeric($gradePoint) ? number_format($gradePoint,2) : '-');
                    }
                @endphp
                @if($subjectDetails && $subjectDetails->subjectType=="Optional" && (int)$subjectDetails->id === $selectedFourthSubjectId)
                <tr>
                    <td>{{ $subjectDetails->subjectName }}</td>
                    <td>{{ $subjectMarks !== null ? $subjectMarks : '-' }}</td>
                    <td>{{ $objectMarks !== null ? $objectMarks : '-' }}</td>
                    <td>{{ $parcticalMarks !== null ? $parcticalMarks : '-' }}</td>
                    <td>{{ $totalMarks !== null ? $totalMarks : '-' }}</td>
                    <td>{{ $grade }}</td>
                    <td>{{ $gradePointDisplay }}</td>
                </tr>
                @endif
            @endforeach
        @else
        <tr>
            <td colspan="10">No selected 4th subject data found</td>
        </tr>
        @endif
    </tbody>
</table>

@php
    // Update overall subtotal to include optional subject totals as requested
    if(isset($subtotalMarksPaired)){
        $subtotalMarks = ($subtotalMarksPaired ?: 0) + ($optionalSubtotalSum ?: 0);
    }
    // Prepare final failed subjects list combining main + optional
    $failedSubjectsAll = array_values(array_unique(array_merge($failedMainNames ?? [], $failedOptionalNames ?? [])));
    $failedCount = count($failedSubjectsAll);
@endphp
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
    
    // Optional subject logic (selected 4th subject only)
    $optionalSubjectFound = false;
    $optionalPoint = 0;
    if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count() > 0) {
        foreach($studentDetails->marksheet as $ckMark) {
            $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
            $hasAny = is_numeric($ckMark->subjectMarks) || is_numeric($ckMark->objectMarks) || is_numeric($ckMark->practicalMarks);
            if(!$hasAny){ continue; }
            if($subjectDetails && $subjectDetails->subjectType == "Optional" && (int)$subjectDetails->id === $selectedFourthSubjectId) {
                $optionalSubjectFound = true;
                $subjectMarks   = is_numeric($ckMark->subjectMarks) ? $ckMark->subjectMarks : 0;
                $objectMarks    = is_numeric($ckMark->objectMarks) ? $ckMark->objectMarks : 0;
                $parcticalMarks = is_numeric($ckMark->practicalMarks) ? $ckMark->practicalMarks : 0;
                $totalMarks     = $subjectMarks + $objectMarks + $parcticalMarks;
                $optionalFullMark = (float)($subjectDetails->CQ ?? 0) + (float)($subjectDetails->MCQ ?? 0) + (float)($subjectDetails->Practical ?? 0);
                $optionalPercent = $optionalFullMark > 0 ? (($totalMarks / $optionalFullMark) * 100) : null;
                $gradeRow = $optionalPercent !== null ? \App\Models\GradeList::forScore((float)$optionalPercent) : null;
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
        $finalGradePoint = '0.00';
    } elseif(count($mainGradePoints) > 0) {
        // Find letter grade by average point
        $gradeListRow = \App\Models\GradeList::forGpa((float)$finalGradePoint);
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

@if($failedCount > 0)
<div class="col-12 mb-3 failed-subjects">
    <h4 class="fw-bold text-danger">Failed Subjects ({{ $failedCount }})</h4>
    <ul class="mb-0">
        @foreach($failedSubjectsAll as $fs)
            <li>{{ $fs }}</li>
        @endforeach
    </ul>
</div>
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
                                    <div class="signature-role">Principal/Head Master</div>
                                    <div class="signature-space"></div>
                                    @if(!empty($config?->principalSign))
                                        @php
                                            $signBase = rtrim(config('app.url'), '/').'/public';
                                            $principalSignSrc = preg_match('~^https?://~i', $config->principalSign)
                                                ? $config->principalSign
                                                : $signBase.'/upload/image/cultivation/'.$config->principalSign;
                                        @endphp
                                        <img src="{{ $principalSignSrc }}" alt="Principal Signature" class="sign-image">
                                    @endif
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
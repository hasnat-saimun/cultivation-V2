<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulk Academic Transcript</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 0; }
        .transcript-page { page-break-after: always; page-break-inside: avoid; }
        .transcript-page:last-child { page-break-after: auto; }
        .marksheet .transcript { background: #fff; padding: 10px; border: 2px solid #111827; }
        .marksheet table { width: 100%; border-collapse: collapse; }
        .marksheet table th, .marksheet table td { border: 1px solid #111827; padding: 4px 5px; }
        .marksheet table thead th { background: #f3f4f6; font-weight: 700; }
        .title { text-align: center; margin: 4px 0 8px 0; }
        .title h3 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .title p { margin: 4px 0 0 0; font-weight: 700; }
        .meta-wrap { width: 100%; margin-bottom: 8px; }
        .meta-wrap td { border: 0 !important; vertical-align: top; padding: 0; }
        .meta-left { width: 62%; padding-right: 10px !important; }
        .meta-right { width: 38%; }
        .student-info { margin-bottom: 0; }
        .student-info th { width: auto; text-align: left; white-space: nowrap;border: 0px solid #111827 !important; }
        .student-info td { word-break: break-word;border: 0px solid #111827 !important; }
        .grade-scale th, .grade-scale td { text-align: center; font-size: 10px;border: 1px solid #111827 !important; }
        .section-title { margin: 8px 0 4px 0; font-size: 14px; font-weight: 700; }
        .failed-subjects h4 { margin: 6px 0 2px 0; color: #b91c1c; }
        .failed-subjects ul { margin: 4px 0 0 0; padding-left: 14px; columns: 2; column-gap: 10px; }
        .signature-row { width: 100%; margin-top: 14px; }
        .signature-row td { width: 33.33%; border: 0 !important; text-align: center; vertical-align: bottom; height: 72px; }
        .signature-line { border-top: 1px solid #111827; width: 75%; margin: 0 auto; }
        .small { font-size: 11px; color: #4b5563; }
        .sign-image { height: 40px; width: auto; max-width: 130px; object-fit: contain; margin: 0 auto 6px auto; display: block; }

        .pdf-header { text-align: center !important; }
        .pdf-header .report-header { display: block !important; width: 100% !important; margin: 0 auto 8px auto !important; padding-bottom: 6px !important; }
        .pdf-header .report-header .logo-wrap { width: 100% !important; text-align: center !important; margin: 0 auto 6px auto !important; }
        .pdf-header .report-header .hdr-logo { margin: 0 auto !important; display: inline-block !important; }
    </style>
</head>
<body>
@php
    $num = function($v){ return is_numeric($v) ? (float)$v : 0.0; };
@endphp

@foreach($transcripts as $i => $t)
    @php
        $studentDetails = $t['studentDetails'] ?? null;
        $examId = (int)($exam->id ?? 0);
        $examName = $exam->examName ?? '';
        $meritRank = $t['meritRank'] ?? null;
        $maxMarkedSubjects = (int)($t['maxMarkedSubjects'] ?? 0);
        $studentMarkedSubjects = (int)($t['studentMarkedSubjects'] ?? 0);
        $hideForMaxRule = !empty($t['hideForMaxRule']);

        if($studentDetails){
            $adminId = $studentDetails->stdId ?? ($studentDetails->id ?? null);
            $stdName = trim(($studentDetails->fullName ?? '').' '.($studentDetails->sureName ?? ''));
            $rollNumber = $studentDetails->rollNumber ?? '';
            $fName = $studentDetails->fatherName ?? ($studentDetails->father ?? '');
            $mName = $studentDetails->motherName ?? ($studentDetails->mother ?? '');

            $sessionName = '-';
            if(!empty($studentDetails->sessName)){
                if(is_numeric($studentDetails->sessName)){
                    $sessModel = \App\Models\sessionManage::find((int)$studentDetails->sessName);
                    $sessionName = $sessModel ? ($sessModel->session ?? ('Session-'.$sessModel->id)) : '-';
                } elseif(is_object($studentDetails->sessName)) {
                    $sessionName = $studentDetails->sessName->session ?? '-';
                } else {
                    $sessionName = (string)$studentDetails->sessName;
                }
            }

            $className = '-';
            $classIdCandidates = [];
            if(isset($studentDetails->className) && is_numeric($studentDetails->className)) $classIdCandidates[] = (int)$studentDetails->className;
            $classModel = null;
            foreach($classIdCandidates as $cid){
                $classModel = \App\Models\classManage::find($cid);
                if(!$classModel) $classModel = \App\Models\Classes::find($cid);
                if($classModel) break;
            }
            if($classModel){
                $className = $classModel->className ?? ('Class-'.$classModel->id);
            } elseif(is_string($studentDetails->className) && trim($studentDetails->className) !== '') {
                $className = (string)$studentDetails->className;
            }

            $sectionName = '-';
            $secCandidates = [];
            if(isset($studentDetails->sectionName) && is_numeric($studentDetails->sectionName)) $secCandidates[] = (int)$studentDetails->sectionName;
            if(isset($studentDetails->sectionId) && is_numeric($studentDetails->sectionId)) $secCandidates[] = (int)$studentDetails->sectionId;
            $secModel = null;
            foreach($secCandidates as $sid){
                $secModel = \App\Models\sectionManage::find($sid);
                if($secModel) break;
            }
            if($secModel){
                $sectionName = $secModel->section ?? ('Section-'.$secModel->id);
            } elseif(isset($studentDetails->sectionName) && is_string($studentDetails->sectionName) && trim($studentDetails->sectionName) !== '') {
                $sectionName = (string)$studentDetails->sectionName;
            }
        } else {
            $adminId = '';
            $stdName = '';
            $rollNumber = '';
            $fName = '';
            $mName = '';
            $sessionName = '-';
            $className = '-';
            $sectionName = '-';
        }

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
    @endphp

    <div class="transcript-page marksheet">
        <div class="transcript">
            <div class="pdf-header">
                @include('components.institute-header')
            </div>

            <div class="title">
                <h3>{{ $config->transcript_title ?? 'Academic Transcript' }}</h3>
                <p>{{ $examName }}</p>
            </div>

            @php
                $gradeScale = \App\Models\GradeList::orderBy('maxMark', 'DESC')->orderBy('gradePoint', 'DESC')->get();
            @endphp
            <table class="meta-wrap">
                <tr>
                    <td class="meta-left">
                        <table class="student-info">
                            <tbody>
                                <tr><th>Student ID</th><td>:</td><td colspan="4">{{ !empty($adminId) ? $adminId : '-' }}</td></tr>
                                <tr><th>Name</th><td>:</td><td colspan="4">{{ $stdName }}</td></tr>
                                <tr><th>Father Name</th><td>:</td><td colspan="4">{{ $fName }}</td></tr>
                                <tr><th>Mother Name</th><td>:</td><td colspan="4">{{ $mName }}</td></tr>
                                <tr>
                                    <th>Roll Number</th><td>:</td><td>{{ $rollNumber }}</td>
                                    <th>Session</th><td>:</td><td>{{ $sessionName }}</td>
                                </tr>
                                <tr>
                                    <th>Class</th><td>:</td><td>{{ $className }}</td>
                                    <th>Section</th><td>:</td><td>{{ $sectionName }}</td>
                                </tr>
                                <tr>
                                    <th>Merit Position</th><td>:</td><td colspan="4">{{ isset($meritRank) && is_numeric($meritRank) ? $meritRank : '1' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td class="meta-right">
                        <table class="grade-scale">
                            <thead>
                                <tr>
                                    <th>Range of Marks</th>
                                    <th>Grade</th>
                                    <th>Point</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($gradeScale as $gs)
                                    <tr>
                                        <td>{{ $gs->minMark }} - {{ $gs->maxMark }}</td>
                                        <td>{{ $gs->gradeName }}</td>
                                        <td>{{ number_format((float)$gs->gradePoint, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            @php
                $isFeatureWise = isset($exam) && $exam->passingSystem == 1;
                $finalLetterGrade = '-';
                $hasFail = false;

                $mainRowsRaw = [];
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
                        if(($sub->isReligious ?? false)){
                            if($effectiveReligiousId === 0 || (int)$sub->id !== $effectiveReligiousId){ continue; }
                        }
                        if(($sub->subjectType ?? 'Main') !== 'Main') continue;
                        $base = $baseAlias($sub->alias ?? $sub->subjectName);
                        $mainRowsRaw[] = [
                            'id' => (int)$sub->id,
                            'name' => $sub->subjectName,
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

                $groups = [];
                foreach($mainRowsRaw as $r){
                    $b = $r['base'] ?? null;
                    $key = $b ?: ('single_'.$r['id']);
                    $groups[$key] = $groups[$key] ?? [];
                    $groups[$key][] = $r;
                }

                $pairedMain = [];
                $subtotalMarksPaired = 0;
                foreach($groups as $items){
                    $displayName = preg_replace('/\s*(1st|2nd)\s*Paper$/i','', $items[0]['name']);
                    $fullCQ = 0; $fullMCQ = 0; $fullPr = 0; $cq = 0; $mcq = 0; $pr = 0;
                    foreach($items as $it){
                        $fullCQ += $it['fullCQ'];
                        $fullMCQ += $it['fullMCQ'];
                        $fullPr += $it['fullPr'];
                        $cq += $it['cq'];
                        $mcq += $it['mcq'];
                        $pr += $it['pr'];
                    }
                    $any = ($cq>0) || ($mcq>0) || ($pr>0);
                    $total = $cq + $mcq + $pr;
                    $grade = '-';
                    $gpDisp = '-';
                    $failFw = false;
                    if($any){
                        $cqPct = $fullCQ>0 ? ($cq/$fullCQ)*100 : null;
                        $mcqPct = $fullMCQ>0 ? ($mcq/$fullMCQ)*100 : null;
                        $prPct = $fullPr>0 ? ($pr/$fullPr)*100 : null;
                        $cGrades = [];
                        foreach(['cqPct'=>$cqPct,'mcqPct'=>$mcqPct,'prPct'=>$prPct] as $k=>$v){
                            if($v===null){ $cGrades[$k] = '-'; }
                            else { $row = \App\Models\GradeList::forScore($v); $cGrades[$k] = $row ? $row->gradeName : '-'; }
                        }
                        $fullSum = $fullCQ + $fullMCQ + $fullPr;
                        $percent = $fullSum>0 ? ($total/$fullSum)*100 : null;
                        if($percent!==null){
                            $gRow = \App\Models\GradeList::forScore($percent);
                            $grade = $gRow ? $gRow->gradeName : '-';
                            $gpDisp = $gRow ? number_format($gRow->gradePoint,2) : '-';
                        }
                    }
                    if($total>0){ $subtotalMarksPaired += $total; }

                    $paper1 = isset($items[0]) ? [
                        'cq' => ($items[0]['cq']>0 ? $items[0]['cq'] : '-'),
                        'mcq' => ($items[0]['mcq']>0 ? $items[0]['mcq'] : '-'),
                        'pr' => ($items[0]['pr']>0 ? $items[0]['pr'] : '-'),
                    ] : null;
                    $paper2 = isset($items[1]) ? [
                        'cq' => ($items[1]['cq']>0 ? $items[1]['cq'] : '-'),
                        'mcq' => ($items[1]['mcq']>0 ? $items[1]['mcq'] : '-'),
                        'pr' => ($items[1]['pr']>0 ? $items[1]['pr'] : '-'),
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
                        'fullCQ' => $fullCQ,
                        'fullMCQ' => $fullMCQ,
                        'fullPr' => $fullPr,
                    ];
                }

                $subtotalMarks = $subtotalMarksPaired;
                $failedMainNames = [];
                foreach($pairedMain as $row){
                    $hasAny = ($row['total'] !== '-') || ($row['cq'] !== '-') || ($row['mcq'] !== '-') || ($row['pr'] !== '-');
                    if(!$hasAny) { continue; }
                    if(($row['grade'] ?? '-') === 'F' || !empty($row['fail'])){ $failedMainNames[] = $row['name']; }
                }
                $failedMainNames = array_values(array_unique($failedMainNames));
            @endphp

            <div class="section-title">Main Subject</div>
            <table>
                <thead>
                    <tr>
                        <th>Subject Name</th>
                        <th>Theory</th>
                        <th>MCQ</th>
                        <th>Practical</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>Point</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pairedMain as $row)
                        @php
                            $cqDisp = '-'; $mcqDisp = '-'; $prDisp = '-';
                            if(($row['fullCQ'] ?? 0) > 0){
                                if(!empty($row['paired'])){
                                    $cq1 = $num($row['paper1']['cq'] ?? null); $cq2 = $num($row['paper2']['cq'] ?? null);
                                    $cqDisp = '(' . $cq1 . ' + ' . $cq2 . ') = ' . ($cq1 + $cq2);
                                } else { $cqDisp = is_numeric($row['cq']) ? $row['cq'] : '-'; }
                            }
                            if(($row['fullMCQ'] ?? 0) > 0){
                                if(!empty($row['paired'])){
                                    $m1 = $num($row['paper1']['mcq'] ?? null); $m2 = $num($row['paper2']['mcq'] ?? null);
                                    $mcqDisp = '(' . $m1 . ' + ' . $m2 . ') = ' . ($m1 + $m2);
                                } else { $mcqDisp = is_numeric($row['mcq']) ? $row['mcq'] : '-'; }
                            }
                            if(($row['fullPr'] ?? 0) > 0){
                                if(!empty($row['paired'])){
                                    $p1 = $num($row['paper1']['pr'] ?? null); $p2 = $num($row['paper2']['pr'] ?? null);
                                    $prDisp = '(' . $p1 . ' + ' . $p2 . ') = ' . ($p1 + $p2);
                                } else { $prDisp = is_numeric($row['pr']) ? $row['pr'] : '-'; }
                            }
                            $gpDisplay = ($row['grade'] === 'F') ? '0.00' : (is_numeric($row['gradePoint']) ? number_format($row['gradePoint'], 2) : ($row['gradePoint'] ?? '-'));
                        @endphp
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $cqDisp }}</td>
                            <td>{{ $mcqDisp }}</td>
                            <td>{{ $prDisp }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td>{{ $row['grade'] }}</td>
                            <td>{{ $gpDisplay }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No main subjects</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="section-title">Optional Subject</div>
            <table>
                <thead>
                    <tr>
                        <th>Subject Name</th>
                        <th>Theory</th>
                        <th>MCQ</th>
                        <th>Practical</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>Point</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $hasOptional = false;
                        $failedOptionalNames = [];
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
                                if($subjectDetails && $subjectDetails->subjectType=="Optional" && (int)$subjectDetails->id === $selectedFourthSubjectId) {
                                    $fullCQ = $subjectDetails->CQ ?? 0;
                                    $fullMCQ = $subjectDetails->MCQ ?? 0;
                                    $fullPractical = $subjectDetails->Practical ?? 0;
                                    $hasAnyRow = is_numeric($ckMark->subjectMarks) || is_numeric($ckMark->objectMarks) || is_numeric($ckMark->practicalMarks);
                                    $subjectMarks = $hasAnyRow && is_numeric($ckMark->subjectMarks) ? (float)$ckMark->subjectMarks : null;
                                    $objectMarks = $hasAnyRow && is_numeric($ckMark->objectMarks) ? (float)$ckMark->objectMarks : null;
                                    $parcticalMarks = $hasAnyRow && is_numeric($ckMark->practicalMarks) ? (float)$ckMark->practicalMarks : null;
                                    $totalMarks = null; $grade = '-'; $gradePoint = null;
                                    if($hasAnyRow){
                                        $totalMarks = ($subjectMarks ?: 0) + ($objectMarks ?: 0) + ($parcticalMarks ?: 0);
                                        $optionalFullMark = ((float)$fullCQ + (float)$fullMCQ + (float)$fullPractical);
                                        $optionalPercent = $optionalFullMark > 0 ? (($totalMarks / $optionalFullMark) * 100) : null;
                                        $gradeRow = $optionalPercent !== null ? \App\Models\GradeList::forScore((float)$optionalPercent) : null;
                                        $grade = $gradeRow ? $gradeRow->gradeName : '-';
                                        $gradePoint = $gradeRow ? (float)$gradeRow->gradePoint : null;
                                        if(is_numeric($totalMarks) && $totalMarks > 0){ $optionalSubtotalSum += (float)$totalMarks; }
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
                                    <td>{{ $subjectDetails->subjectName }} (4th)</td>
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
                        <tr><td colspan="7">No selected 4th subject data found</td></tr>
                    @endif
                </tbody>
            </table>

            @php
                $subtotalMarks = ($subtotalMarksPaired ?: 0) + ($optionalSubtotalSum ?: 0);
                $failedSubjectsAll = array_values(array_unique(array_merge($failedMainNames ?? [], $failedOptionalNames ?? [])));
                $failedCount = count($failedSubjectsAll);

                $mainGradePoints = [];
                $hasFail = false;
                foreach($pairedMain as $row) {
                    $hasAny = ($row['cq'] !== '-') || ($row['mcq'] !== '-') || ($row['pr'] !== '-') || ($row['total'] !== '-');
                    if(!$hasAny) { continue; }
                    $gradePoint = ($row['grade'] === 'F') ? 0 : (is_numeric($row['gradePoint']) ? (float)$row['gradePoint'] : 0);
                    if($row['grade'] === 'F'){ $hasFail = true; }
                    $mainGradePoints[] = $gradePoint;
                }

                $optionalSubjectFound = false;
                $optionalPoint = 0;
                if($studentDetails && $studentDetails->marksheet && $studentDetails->marksheet->count() > 0) {
                    foreach($studentDetails->marksheet as $ckMark) {
                        $subjectDetails = \App\Models\Subject::find($ckMark->subjectId);
                        $hasAny = is_numeric($ckMark->subjectMarks) || is_numeric($ckMark->objectMarks) || is_numeric($ckMark->practicalMarks);
                        if(!$hasAny){ continue; }
                        if($subjectDetails && $subjectDetails->subjectType == "Optional" && (int)$subjectDetails->id === $selectedFourthSubjectId) {
                            $optionalSubjectFound = true;
                            $subjectMarks = is_numeric($ckMark->subjectMarks) ? $ckMark->subjectMarks : 0;
                            $objectMarks = is_numeric($ckMark->objectMarks) ? $ckMark->objectMarks : 0;
                            $parcticalMarks = is_numeric($ckMark->practicalMarks) ? $ckMark->practicalMarks : 0;
                            $totalMarks = $subjectMarks + $objectMarks + $parcticalMarks;
                            $optionalFullMark = (float)($subjectDetails->CQ ?? 0) + (float)($subjectDetails->MCQ ?? 0) + (float)($subjectDetails->Practical ?? 0);
                            $optionalPercent = $optionalFullMark > 0 ? (($totalMarks / $optionalFullMark) * 100) : null;
                            $gradeRow = $optionalPercent !== null ? \App\Models\GradeList::forScore((float)$optionalPercent) : null;
                            $optionalPoint = $gradeRow ? $gradeRow->gradePoint : 0;
                        }
                    }
                }

                $optionalBonus = ($optionalSubjectFound && $optionalPoint > 2) ? ($optionalPoint - 2) : 0;
                $finalGradePoint = count($mainGradePoints) > 0 ? round((array_sum($mainGradePoints) + $optionalBonus) / count($mainGradePoints), 2) : '-';
                if($hasFail) {
                    $finalLetterGrade = 'F';
                    $finalGradePoint = '0.00';
                } elseif(count($mainGradePoints) > 0) {
                    $gradeListRow = \App\Models\GradeList::forGpa((float)$finalGradePoint);
                    $finalLetterGrade = $gradeListRow ? $gradeListRow->gradeName : '-';
                } else {
                    $finalLetterGrade = '-';
                    $finalGradePoint = '-';
                }

                $principalSignSrc = null;
                if(!empty($config?->principalSign)){
                    $signBase = rtrim(config('app.url'), '/').'/public';
                    $principalSignSrc = preg_match('~^https?://~i', $config->principalSign)
                        ? $config->principalSign
                        : $signBase.'/upload/image/cultivation/'.$config->principalSign;
                }
            @endphp

            <table style="margin-top:10px;">
                <thead>
                    <tr>
                        <th width="20%">Total Marks: {{ $subtotalMarks }}</th>
                        <th width="20%">Letter Grade: {{ $finalLetterGrade }}</th>
                        <th width="20%">Grade Point: {{ $finalGradePoint }}</th>
                        <th>Remark-</th>
                    </tr>
                </thead>
            </table>

            @if($failedCount > 0)
                <div class="failed-subjects">
                    <h4>Failed Subjects ({{ $failedCount }})</h4>
                    <ul>
                        @foreach($failedSubjectsAll as $fs)
                            <li>{{ $fs }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <table class="signature-row">
                <tr>
                    <td>
                        <div>Guardian</div>
                        <div class="signature-line"></div>
                        <div class="small">Signature</div>
                    </td>
                    <td>
                        <div>Class Teacher</div>
                        <div class="signature-line"></div>
                        <div class="small">Signature</div>
                    </td>
                    <td>
                        <div>Principal/Head Master</div>
                        @if(!empty($principalSignSrc))
                            <img src="{{ $principalSignSrc }}" alt="Principal Signature" class="sign-image">
                        @endif
                        <div class="signature-line"></div>
                        <div class="small">Signature</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

@endforeach
</body>
</html>

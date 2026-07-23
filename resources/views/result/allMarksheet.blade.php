@extends('result.singleinclude')
@section('backTitle')
All Marksheet
@endsection
@section('backIndex')
<style>
    @page { size: Letter landscape; margin: 3mm; }
    html, body { background: #fff; }
    .bg-ash{ background: #fff !important; }
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #fff;
    }   
    .result-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .result-table th, .result-table td { border: 1px solid #111; padding: 4px 6px; text-align: center; vertical-align: middle; font-size: 11px; }
    .result-table thead th { background: #ffffff; font-weight: 700; }
    .result-table .name-col { text-align: left; width: 145px; }
    .result-table .roll-col { width: 52px; }
    .result-table .mini { width: 40px; }
    .result-table .tot-col { width: 56px; }
    .result-table .grade-col { width: 48px; }
    .result-table .fail-col { width: 36px; }
    .result-table .merit-col { width: 56px; }

    .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .summary-table th, .summary-table td { border: 1px solid #111; padding: 6px 8px; text-align: center; font-size: 13px; }
    .summary-table th { background: #ffffff; font-weight: 700; }
    .legend-line { font-size: 12px; margin: 8px 2px 10px; }

    .page-break { break-before: page; page-break-before: always; }

    @media print {
        .navbar,
        .sidebar-main,
        .breadcrumbs-area,
        .footer-wrap-layout1,
        .result-section,
        .result-text,
        .alert,
        form { display: none !important; }
        .bg-ash{ background-color: #fff !important; }

        .container-fluid { margin: 0 !important; padding: 0 !important; }
        .dashboard-content-one, .main-website, .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .main-content { padding-top: 0 !important; }

        .table-responsive { overflow: visible !important; margin: 0 !important; }
        .table, .result-table { page-break-inside: auto !important; width: 100% !important; }
        .table tr, .result-table tr { page-break-inside: avoid !important; page-break-after: auto !important; }
        .table thead, .result-table thead { display: table-header-group !important; }

        .table th, .table td,
        .result-table th, .result-table td {
            border: 1px solid #000 !important;
            padding: 2px 3px !important;
            font-size: 9px !important;
            line-height: 1.1 !important;
            vertical-align: middle !important;
        }
        .table thead th, .result-table thead th, .summary-table th {
            background: #ffffff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .summary-table th, .summary-table td { padding: 4px 6px !important; font-size: 11px !important; }
        .legend-line { margin: 4px 2px 6px !important; font-size: 10px !important; line-height: 1.2 !important; }

        /* Prevent right-side fields from being cut off in print */
        .result-table { table-layout: fixed !important; width: 100% !important; }
        .result-table th, .result-table td { padding: 1px 2px !important; font-size: 8px !important; line-height: 1.05 !important; }
        .result-table .roll-col { width: 30px !important; min-width: 30px !important; max-width: 30px !important; }
        .result-table .name-col {
            text-align: left;
            width: 84px !important;
            min-width: 84px !important;
            max-width: 84px !important;
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }
        .result-table .mini { width: 22px !important; min-width: 22px !important; max-width: 22px !important; }
        .result-table .tot-col { width: 30px !important; min-width: 30px !important; max-width: 30px !important; }
        .result-table .grade-col { width: 26px !important; min-width: 26px !important; max-width: 26px !important; }
        .result-table .fail-col { width: 22px !important; min-width: 22px !important; max-width: 22px !important; }
        .result-table .merit-col { width: 30px !important; min-width: 30px !important; max-width: 30px !important; }
    }
</style>
<div class="main-website">
    <div class="main-content">
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
                    <label class="form-label">Session *</label>
                    <select name="sessionId" class="form-control" required>
                        <option value="">Select</option>
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
                    <label class="form-label">Department</label>
                    <select name="departmentId" class="form-control">
                        <option value="">All</option>
                        @php $departmentList = \App\Models\Department::orderBy('id','ASC')->get(); @endphp
                        @foreach($departmentList as $dept)
                            <option value="{{ $dept->id }}" {{ $dept->id == request('departmentId') ? 'selected' : '' }}>{{ $dept->departmentName }}</option>
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
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="compact" value="1" id="compactChk" {{ request('compact') ? 'checked' : '' }}>
                        <label class="form-check-label" for="compactChk">
                            Compact per-student subjects (hide empty subjects)
                        </label>
                    </div>
                </div>
            </form>
        </div>

        @if(!$examId || !$classId || !$sessionId)
            <div class="alert alert-info container">Please select required filters (Exam, Class & Session) to view results.</div>
        @endif

        @if($examId && $classId && $sessionId)
        @php
            $hasPassSection = !$compactMode ? (count($passResults ?? []) > 0) : (count($passResultsCompact ?? []) > 0);
            $hasFailSection = !$compactMode ? (count($failResults ?? []) > 0) : (count($failResultsCompact ?? []) > 0);
            $hasIncompleteSection = !$compactMode ? (count($incompleteResults ?? []) > 0) : (count($incompleteResultsCompact ?? []) > 0);
            $hasAnySection = $hasPassSection || $hasFailSection || $hasIncompleteSection;
            $hasOnlyIncomplete = $hasIncompleteSection && !$hasPassSection && !$hasFailSection;
        @endphp
        <div class="d-print-none">
            @include('components.result-header')
        </div>
            @php
                // Determine if only failed students are present
                $onlyFailed = $hasFailSection && !$hasPassSection && !$hasIncompleteSection;
            @endphp

            @php
                $optionalSubjectNameMap = [];
                $allRowsForType = array_merge($passResults ?? [], $failResults ?? [], $incompleteResults ?? [], $passResultsCompact ?? [], $failResultsCompact ?? [], $incompleteResultsCompact ?? []);
                foreach($allRowsForType as $row){
                    foreach(($row['subjects'] ?? []) as $sr){
                        if(($sr['type'] ?? null) === 'Optional' && !empty($sr['name'])){ $optionalSubjectNameMap[$sr['name']] = true; }
                    }
                    foreach(($row['subjectsCompact'] ?? []) as $sr){
                        if(($sr['type'] ?? null) === 'Optional' && !empty($sr['name'])){ $optionalSubjectNameMap[$sr['name']] = true; }
                    }
                }
            @endphp
            @if($onlyFailed)
                @php
                    // Build a single-line summary at the very top
                    $topBuckets = [];
                    $source = $compactMode ? ($failResultsCompact ?? []) : ($failResults ?? []);
                    foreach($source as $res){
                        $subFailCount = 0;
                        if(isset($res['subjectFails']) && is_numeric($res['subjectFails'])){
                            $subFailCount = (int) $res['subjectFails'];
                        } else {
                            $subRows = $compactMode ? ($res['subjectsCompact'] ?? []) : ($res['subjects'] ?? []);
                            if(is_array($subRows)){
                                foreach($subRows as $sr){
                                    $total = $sr['total'] ?? null;
                                    $letter = $sr['grade'] ?? null;
                                    $point  = $sr['gradePoint'] ?? null;
                                    if($letter === 'F') { $subFailCount++; continue; }
                                    if(is_numeric($point) && $point <= 0) { $subFailCount++; continue; }
                                    if(is_numeric($total)){
                                        $gl = \App\Models\GradeList::where('minMark','<=',$total)->where('maxMark','>=',$total)->first();
                                        if(($gl?->gradeName) === 'F' || (is_numeric($gl?->gradePoint) && $gl->gradePoint <= 0)){
                                            $subFailCount++;
                                        }
                                    }
                                }
                            }
                        }
                        $topBuckets[$subFailCount] = ($topBuckets[$subFailCount] ?? 0) + 1;
                    }
                    ksort($topBuckets);
                @endphp
                @if(!empty($topBuckets))
                    <div class="container-fluid">
                        <div class="mb-2 small fw-bold text-danger">
                            @php $parts = []; @endphp
                            @foreach($topBuckets as $fc=>$cnt)
                                @php $parts[] = $fc.' Subject'.($fc==1?'':'s')." Failed(".$cnt.")"; @endphp
                            @endforeach
                            {{ implode(', ', $parts) }}
                        </div>
                    </div>
                @endif
            @endif
            <div class="result-section">
                <div class="row">
                    <div class="col-12">
                        <div class="result-text text-center mt-2">
                            @if($exam && $exam->passingSystem == 1)
                                <p class="text-muted mb-0"><small>Feature-wise Passing System Applied</small></p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @php
                // Determine which subjects have at least one mark across all students
                $visibleSubjects = $subjects ?? [];
                try {
                    if(!empty($usingCentralizedTabulation)){
                        $visibleSubjects = $subjects ?? [];
                    } else {
                    $visibleSubjects = [];
                    $allResults = array_merge($passResults ?? [], $failResults ?? [], $incompleteResults ?? []);
                    foreach(($subjects ?? []) as $sub){
                        $hasData = false;
                        foreach($allResults as $res){
                            if(isset($res['subjects']) && is_array($res['subjects'])){
                                foreach($res['subjects'] as $sr){
                                    if( ($sr['name'] ?? null) === ($sub->subjectName ?? null) ){
                                        if(isset($sr['total']) && is_numeric($sr['total'])){ $hasData = true; break 2; }
                                    }
                                }
                            }
                        }
                        if($hasData){ $visibleSubjects[] = $sub; }
                    }
                    }
                } catch (\Throwable $e) {
                    // Fallback to all subjects if any unexpected structure
                    $visibleSubjects = $subjects ?? [];
                }
                $subjectCount = count($visibleSubjects);
            @endphp
            @php
                $subjectShortByIndex = [];
                $usedShortCounts = [];
                foreach($visibleSubjects as $idx => $sub){
                    $name = (string)($sub->subjectName ?? '');
                    $short = null;
                    if(stripos($name, 'Bangla') !== false && stripos($name, '1') !== false){ $short = 'Ban-1'; }
                    elseif(stripos($name, 'Bangla') !== false && stripos($name, '2') !== false){ $short = 'Ban-2'; }
                    elseif(stripos($name, 'English') !== false && stripos($name, '1') !== false){ $short = 'Eng-1'; }
                    elseif(stripos($name, 'English') !== false && stripos($name, '2') !== false){ $short = 'Eng-2'; }
                    elseif(stripos($name, 'Mathematics') !== false || stripos($name, 'Math') !== false){ $short = 'Math'; }
                    elseif(stripos($name, 'Religion') !== false || stripos($name, 'Islam') !== false){ $short = 'Rel'; }
                    elseif(stripos($name, 'Bangladesh') !== false || stripos($name, 'BGS') !== false){ $short = 'BGS'; }
                    elseif(stripos($name, 'Information') !== false || stripos($name, 'ICT') !== false){ $short = 'ICT'; }
                    elseif(stripos($name, 'General Science') !== false || stripos($name, 'Science') !== false){ $short = 'G.Sci'; }
                    else {
                        $words = preg_split('/\s+/', trim($name));
                        $abbr = '';
                        foreach(array_slice($words,0,2) as $w){ $abbr .= strtoupper(substr($w,0,1)); }
                        $short = $abbr ?: strtoupper(substr($name,0,3));
                    }
                    $usedShortCounts[$short] = ($usedShortCounts[$short] ?? 0) + 1;
                    $subjectShortByIndex[$idx] = ($usedShortCounts[$short] === 1) ? $short : ($short.'-'.$usedShortCounts[$short]);
                }
            @endphp
            @php
                $sessionName = optional(\App\Models\sessionManage::find((int)$sessionId))->session ?? ($sessionId ?: '-');
                $className = optional(\App\Models\classManage::find((int)$classId))->className ?? ($classId ?: '-');
                $groupName = $sectionId ? (optional(\App\Models\sectionManage::find((int)$sectionId))->section ?? 'N/A') : 'N/A';
                $examName = optional(\App\Models\Exam::find((int)$examId))->examName ?? '-';

                $totalStudents = \App\Models\newAdmission::where('className', (int)$classId)
                    ->where('sessName', (int)$sessionId)
                    ->when($sectionId, function($q) use ($sectionId){ return $q->where('sectionName', (int)$sectionId); })
                    ->when($departmentId, function($q) use ($departmentId){ return $q->where('departmentName', (int)$departmentId); })
                    ->count();
                $presentCount = !$compactMode
                    ? (count($passResults ?? []) + count($failResults ?? []) + count($incompleteResults ?? []))
                    : (count($passResultsCompact ?? []) + count($failResultsCompact ?? []) + count($incompleteResultsCompact ?? []));
                $absentCount = max(0, (int)$totalStudents - (int)$presentCount);

                $legendParts = [];
                foreach($visibleSubjects as $idx => $sub){
                    $baseName = (string)($sub->subjectName ?? '');
                    $labelName = $baseName.(isset($optionalSubjectNameMap[$baseName]) ? ' (4th)' : '');
                    $legendParts[] = ($subjectShortByIndex[$idx] ?? $baseName).'='.$labelName;
                }
            @endphp
            <div class="d-print-none">
                @include('result.partials.print-summary')
            </div>
            @if($studentsLoaded && (count($passResults) + count($failResults)) === 0)
                <div class="alert alert-warning container">No marks found for the selected filters.</div>
            @endif

            @php
                // Merit ranking by sum of subject totals (Pass + Fail). Dense rank: equal totals share rank.
                $meritRankingMap = [];
                try {
                    $source = !$compactMode
                        ? array_merge($passResults ?? [], $failResults ?? [])
                        : array_merge($passResultsCompact ?? [], $failResultsCompact ?? []);
                    $rankItems = [];
                    foreach ($source as $res) {
                        $key = (string)($res['student']->id ?? $res['student']->stdId ?? '');
                        if($key === '') { continue; }
                        $total = null;
                        if (isset($res['totalMarks']) && is_numeric($res['totalMarks'])) {
                            $total = (float)$res['totalMarks'];
                        } else {
                            $sum = 0; $hasAny = false;
                            $rows = !$compactMode ? ($res['subjects'] ?? []) : ($res['subjectsCompact'] ?? []);
                            if (is_array($rows)) {
                                foreach ($rows as $sr) {
                                    if (isset($sr['total']) && is_numeric($sr['total'])) { $sum += (float)$sr['total']; $hasAny = true; }
                                }
                            }
                            $total = $hasAny ? (float)$sum : 0.0;
                        }
                        $rankItems[] = ['key'=>$key, 'total'=>$total];
                    }
                    usort($rankItems, function($a,$b){
                        if ($a['total'] == $b['total']) return 0;
                        return ($a['total'] > $b['total']) ? -1 : 1; // desc
                    });
                    $rank = 0; $prevTotal = null;
                    foreach ($rankItems as $it) {
                        if ($prevTotal === null || $it['total'] != $prevTotal) { $rank++; $prevTotal = $it['total']; }
                        $meritRankingMap[$it['key']] = $rank;
                    }
                } catch (\Throwable $e) { $meritRankingMap = []; }
            @endphp

            @if(!$compactMode && count($passResults) > 0)
                @php
                    $passRowsPerPage = ($subjectCount >= 18) ? 20 : 22;
                    $passPages = array_chunk($passResults, $passRowsPerPage);
                @endphp

                <div class="d-print-none">
                    <h5 class="mt-4 fw-bold text-success">Passed Students ({{ count($passResults) }})</h5>
                    <div class="table-responsive dark-border mb-5">
                        <table class="w-100 table-striped table-bordered text-center table result-table">
                            <thead>
                                <tr class="table-dark text-dark">
                                    <th rowspan="2" class="roll-col"><b>Roll</b></th>
                                    <th rowspan="2" class="name-col"><b>Name</b></th>
                                    @if(count($visibleSubjects) > 0)
                                        @foreach($visibleSubjects as $idx => $sub)
                                            <th class="mini"><b>{{ $subjectShortByIndex[$idx] ?? ($sub->subjectName ?? '-') }}</b></th>
                                        @endforeach
                                    @else
                                        <th class="mini"><b>No subjects</b></th>
                                    @endif
                                    <th rowspan="2" class="tot-col"><b>Total</b></th>
                                    <th rowspan="2" class="grade-col"><b>Grade</b></th>
                                    <th rowspan="2" class="fail-col"><b>Fail</b></th>
                                    <th rowspan="2" class="merit-col"><b>Class Merit</b></th>
                                </tr>
                                <tr class="table-dark text-dark">
                                    @if(count($visibleSubjects) > 0)
                                        @foreach($visibleSubjects as $idx => $sub)
                                            <th class="mini"><b>-</b></th>
                                        @endforeach
                                    @else
                                        <th class="mini"><b>-</b></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($passResults as $i=>$res)
                                @php $rowByName = []; if(isset($res['subjects'])){ foreach($res['subjects'] as $sr){ $rowByName[$sr['name']] = $sr; } } @endphp
                                <tr>
                                    <td class="roll-col">{{ $res['student']->rollNumber }}</td>
                                    <td class="name-col">{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                    @foreach($visibleSubjects as $sub)
                                        @php $cell = $rowByName[$sub->subjectName] ?? null; @endphp
                                        <td class="subject-td mini">{{ ($cell && is_numeric($cell['total'])) ? $cell['total'] : '' }}</td>
                                    @endforeach
                                    @php
                                        $fails = (int)($res['subjectFails'] ?? 0);
                                        if(empty($usingCentralizedTabulation) && $fails === 0 && isset($res['subjects']) && is_array($res['subjects'])){
                                            foreach($res['subjects'] as $sr){
                                                $l = $sr['grade'] ?? null;
                                                $gp = $sr['gradePoint'] ?? null;
                                                if($l === 'F' || (is_numeric($gp) && (float)$gp <= 0)){ $fails++; }
                                            }
                                        }
                                    @endphp
                                    <td class="tot-col">{{ $res['totalMarks'] }}</td>
                                    <td class="grade-col">{{ $res['finalLetter'] }}</td>
                                    <td class="fail-col">{{ $fails }}</td>
                                    @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                    <td class="merit-col">{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : (is_numeric($res['meritRank'] ?? null) ? str_pad((string)((int)$res['meritRank']), 2, '0', STR_PAD_LEFT) : '-') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-none d-print-block print-pages">
                    @foreach($passPages as $pageIndex => $pageRows)
                        <div class="{{ $pageIndex > 0 ? 'page-break' : '' }}">
                            <h5 class="mt-2 mb-1 fw-bold text-success">Passed Students ({{ count($passResults) }})</h5>
                            @include('components.result-header')
                            @include('result.partials.print-summary')
                            <div class="table-responsive dark-border mb-4">
                                <table class="w-100 table-striped table-bordered text-center table result-table">
                                    <thead>
                                        <tr class="table-dark text-dark">
                                            <th rowspan="2" class="roll-col"><b>Roll</b></th>
                                            <th rowspan="2" class="name-col"><b>Name</b></th>
                                            @if(count($visibleSubjects) > 0)
                                                @foreach($visibleSubjects as $idx => $sub)
                                                    <th class="mini"><b>{{ $subjectShortByIndex[$idx] ?? ($sub->subjectName ?? '-') }}</b></th>
                                                @endforeach
                                            @else
                                                <th class="mini"><b>No subjects</b></th>
                                            @endif
                                            <th rowspan="2" class="tot-col"><b>Total</b></th>
                                            <th rowspan="2" class="grade-col"><b>Grade</b></th>
                                            <th rowspan="2" class="fail-col"><b>Fail</b></th>
                                            <th rowspan="2" class="merit-col"><b>Class Merit</b></th>
                                        </tr>
                                        <tr class="table-dark text-dark">
                                            @if(count($visibleSubjects) > 0)
                                                @foreach($visibleSubjects as $idx => $sub)
                                                    <th class="mini"><b>-</b></th>
                                                @endforeach
                                            @else
                                                <th class="mini"><b>-</b></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($pageRows as $res)
                                        @php $rowByName = []; if(isset($res['subjects'])){ foreach($res['subjects'] as $sr){ $rowByName[$sr['name']] = $sr; } } @endphp
                                        <tr>
                                            <td class="roll-col">{{ $res['student']->rollNumber }}</td>
                                            <td class="name-col">{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                            @foreach($visibleSubjects as $sub)
                                                @php $cell = $rowByName[$sub->subjectName] ?? null; @endphp
                                                <td class="subject-td mini">{{ ($cell && is_numeric($cell['total'])) ? $cell['total'] : '' }}</td>
                                            @endforeach
                                            @php
                                                $fails = (int)($res['subjectFails'] ?? 0);
                                                if(empty($usingCentralizedTabulation) && $fails === 0 && isset($res['subjects']) && is_array($res['subjects'])){
                                                    foreach($res['subjects'] as $sr){
                                                        $l = $sr['grade'] ?? null;
                                                        $gp = $sr['gradePoint'] ?? null;
                                                        if($l === 'F' || (is_numeric($gp) && (float)$gp <= 0)){ $fails++; }
                                                    }
                                                }
                                            @endphp
                                            <td class="tot-col">{{ $res['totalMarks'] }}</td>
                                            <td class="grade-col">{{ $res['finalLetter'] }}</td>
                                            <td class="fail-col">{{ $fails }}</td>
                                            @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                            <td class="merit-col">{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : (is_numeric($res['meritRank'] ?? null) ? str_pad((string)((int)$res['meritRank']), 2, '0', STR_PAD_LEFT) : '-') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!$compactMode && count($failResults) > 0)
                @if($hasPassSection)
                <div class="d-none d-print-block page-break"></div>
                @endif
                @php
                    // Group failed students by number of failed subjects
                    $failedGroups = [];
                    foreach(($failResults ?? []) as $res){
                        $subFailCount = 0;
                        if(isset($res['subjectFails']) && is_numeric($res['subjectFails'])){
                            $subFailCount = (int) $res['subjectFails'];
                        } elseif(isset($res['subjects']) && is_array($res['subjects'])) {
                            foreach($res['subjects'] as $sr){
                                $total = $sr['total'] ?? null;
                                $letter = $sr['grade'] ?? null;
                                $point  = $sr['gradePoint'] ?? null;
                                if($letter === 'F'){
                                    $subFailCount++;
                                    continue;
                                }
                                if(is_numeric($point) && $point <= 0){
                                    $subFailCount++;
                                    continue;
                                }
                                if(is_numeric($total)){
                                    $gl = \App\Models\GradeList::where('minMark','<=',$total)->where('maxMark','>=',$total)->first();
                                    if(($gl?->gradeName) === 'F' || (is_numeric($gl?->gradePoint) && $gl->gradePoint <= 0)){
                                        $subFailCount++;
                                    }
                                }
                            }
                        }
                        if($subFailCount > 0){
                            $failedGroups[$subFailCount] = $failedGroups[$subFailCount] ?? [];
                            $failedGroups[$subFailCount][] = $res;
                        }
                    }
                    ksort($failedGroups);
                @endphp
                @foreach($failedGroups as $fc => $group)
                    @php
                        $failedRowsPerPage = ($subjectCount >= 18) ? 20 : 22;
                        $failedPages = array_chunk($group, $failedRowsPerPage);
                        $failedStartsNewPage = $hasPassSection || !$loop->first;
                    @endphp
                    <div class="d-print-none">
                        <h5 class="mt-4 fw-bold text-danger">{{ $fc }} Subject{{ $fc==1?'':'s' }} Failed ({{ count($group) }})</h5>
                        <div class="table-responsive dark-border mb-5">
                            <table class="w-100 table-striped table-bordered text-center table result-table">
                                <thead>
                                    <tr class="table-dark text-dark">
                                        <th rowspan="2" class="roll-col"><b>Roll</b></th>
                                        <th rowspan="2" class="name-col"><b>Name</b></th>
                                        @if(count($visibleSubjects) > 0)
                                            @foreach($visibleSubjects as $idx => $sub)
                                                <th class="mini"><b>{{ $subjectShortByIndex[$idx] ?? ($sub->subjectName ?? '-') }}</b></th>
                                            @endforeach
                                        @else
                                            <th class="mini"><b>No subjects</b></th>
                                        @endif
                                        <th rowspan="2" class="tot-col"><b>Total</b></th>
                                        <th rowspan="2" class="grade-col"><b>Grade</b></th>
                                        <th rowspan="2" class="fail-col"><b>Fail</b></th>
                                        <th rowspan="2" class="merit-col"><b>Class Merit</b></th>
                                    </tr>
                                    <tr class="table-dark text-dark">
                                        @if(count($visibleSubjects) > 0)
                                            @foreach($visibleSubjects as $idx => $sub)
                                                <th class="mini"><b>-</b></th>
                                            @endforeach
                                        @else
                                            <th class="mini"><b>-</b></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($group as $i=>$res)
                                    @php $rowByName = []; if(isset($res['subjects'])){ foreach($res['subjects'] as $sr){ $rowByName[$sr['name']] = $sr; } } @endphp
                                    <tr>
                                        <td class="roll-col">{{ $res['student']->rollNumber }}</td>
                                        <td class="name-col">{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                        @foreach($visibleSubjects as $sub)
                                            @php
                                                $cell = $rowByName[$sub->subjectName] ?? null;
                                                $cellLetter = $cell['grade'] ?? null;
                                                $cellPoint  = $cell['gradePoint'] ?? null;
                                                $cellIsFail = $cell && (
                                                    $cellLetter === 'F' ||
                                                    (is_numeric($cellPoint) && (float)$cellPoint <= 0)
                                                );
                                                $failedFeatures = [];
                                                if($cellIsFail && $cell){
                                                    if(!empty($cell['hasCQFeature']) && (($cell['cqGrade'] ?? null) === 'F')){ $failedFeatures[] = 'cq'; }
                                                    if(!empty($cell['hasMCQFeature']) && (($cell['mcqGrade'] ?? null) === 'F')){ $failedFeatures[] = 'mcq'; }
                                                    if(!empty($cell['hasPracticalFeature']) && (($cell['prGrade'] ?? null) === 'F')){ $failedFeatures[] = 'pr'; }
                                                }
                                            @endphp
                                            <td class="subject-td mini {{ $cellIsFail ? 'text-danger fw-bold' : '' }}">
                                                {{ ($cell && is_numeric($cell['total'])) ? $cell['total'] : '' }}
                                                @if($cellIsFail && count($failedFeatures) > 0)
                                                    <small class="d-block" style="font-size:9px; line-height:1.1;">{{ implode('/', $failedFeatures) }}</small>
                                                @endif
                                            </td>
                                        @endforeach
                                        @php
                                            $fails = (int)($res['subjectFails'] ?? 0);
                                            if(empty($usingCentralizedTabulation) && $fails === 0 && isset($res['subjects']) && is_array($res['subjects'])){
                                                foreach($res['subjects'] as $sr){
                                                    $l = $sr['grade'] ?? null;
                                                    $gp = $sr['gradePoint'] ?? null;
                                                    if($l === 'F' || (is_numeric($gp) && (float)$gp <= 0)){ $fails++; }
                                                }
                                            }
                                        @endphp
                                        <td class="tot-col">{{ $res['totalMarks'] }}</td>
                                        <td class="grade-col">{{ $res['finalLetter'] }}</td>
                                        <td class="fail-col">{{ $fails }}</td>
                                        @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                        <td class="merit-col">{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : '-' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-none d-print-block print-pages">
                        @foreach($failedPages as $pageIndex => $pageRows)
                            <div class="{{ ($failedStartsNewPage && $pageIndex === 0) || $pageIndex > 0 ? 'page-break' : '' }}">
                                <h5 class="mt-2 mb-1 fw-bold text-danger">{{ $fc }} Subject{{ $fc==1?'':'s' }} Failed ({{ count($group) }})</h5>
                                @include('components.result-header')
                                @include('result.partials.print-summary')
                                <div class="table-responsive dark-border mb-4">
                                    <table class="w-100 table-striped table-bordered text-center table result-table">
                                        <thead>
                                            <tr class="table-dark text-dark">
                                                <th rowspan="2" class="roll-col"><b>Roll</b></th>
                                                <th rowspan="2" class="name-col"><b>Name</b></th>
                                                @if(count($visibleSubjects) > 0)
                                                    @foreach($visibleSubjects as $idx => $sub)
                                                        <th class="mini"><b>{{ $subjectShortByIndex[$idx] ?? ($sub->subjectName ?? '-') }}</b></th>
                                                    @endforeach
                                                @else
                                                    <th class="mini"><b>No subjects</b></th>
                                                @endif
                                                <th rowspan="2" class="tot-col"><b>Total</b></th>
                                                <th rowspan="2" class="grade-col"><b>Grade</b></th>
                                                <th rowspan="2" class="fail-col"><b>Fail</b></th>
                                                <th rowspan="2" class="merit-col"><b>Class Merit</b></th>
                                            </tr>
                                            <tr class="table-dark text-dark">
                                                @if(count($visibleSubjects) > 0)
                                                    @foreach($visibleSubjects as $idx => $sub)
                                                        <th class="mini"><b>-</b></th>
                                                    @endforeach
                                                @else
                                                    <th class="mini"><b>-</b></th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($pageRows as $res)
                                            @php $rowByName = []; if(isset($res['subjects'])){ foreach($res['subjects'] as $sr){ $rowByName[$sr['name']] = $sr; } } @endphp
                                            <tr>
                                                <td class="roll-col">{{ $res['student']->rollNumber }}</td>
                                                <td class="name-col">{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                                @foreach($visibleSubjects as $sub)
                                                    @php
                                                        $cell = $rowByName[$sub->subjectName] ?? null;
                                                        $cellLetter = $cell['grade'] ?? null;
                                                        $cellPoint  = $cell['gradePoint'] ?? null;
                                                        $cellIsFail = $cell && (
                                                            $cellLetter === 'F' ||
                                                            (is_numeric($cellPoint) && (float)$cellPoint <= 0)
                                                        );
                                                        $failedFeatures = [];
                                                        if($cellIsFail && $cell){
                                                            if(!empty($cell['hasCQFeature']) && (($cell['cqGrade'] ?? null) === 'F')){ $failedFeatures[] = 'cq'; }
                                                            if(!empty($cell['hasMCQFeature']) && (($cell['mcqGrade'] ?? null) === 'F')){ $failedFeatures[] = 'mcq'; }
                                                            if(!empty($cell['hasPracticalFeature']) && (($cell['prGrade'] ?? null) === 'F')){ $failedFeatures[] = 'pr'; }
                                                        }
                                                    @endphp
                                                    <td class="subject-td mini {{ $cellIsFail ? 'text-danger fw-bold' : '' }}">
                                                        {{ ($cell && is_numeric($cell['total'])) ? $cell['total'] : '' }}
                                                        @if($cellIsFail && count($failedFeatures) > 0)
                                                            <small class="d-block" style="font-size:9px; line-height:1.1;">{{ implode('/', $failedFeatures) }}</small>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                @php
                                                    $fails = (int)($res['subjectFails'] ?? 0);
                                                    if(empty($usingCentralizedTabulation) && $fails === 0 && isset($res['subjects']) && is_array($res['subjects'])){
                                                        foreach($res['subjects'] as $sr){
                                                            $l = $sr['grade'] ?? null;
                                                            $gp = $sr['gradePoint'] ?? null;
                                                            if($l === 'F' || (is_numeric($gp) && (float)$gp <= 0)){ $fails++; }
                                                        }
                                                    }
                                                @endphp
                                                <td class="tot-col">{{ $res['totalMarks'] }}</td>
                                                <td class="grade-col">{{ $res['finalLetter'] }}</td>
                                                <td class="fail-col">{{ $fails }}</td>
                                                @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                                <td class="merit-col">{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : '-' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endif

            @if(!$compactMode && count($incompleteResults) > 0)
                @php
                    $incompleteRowsPerPage = ($subjectCount >= 18) ? 20 : 22;
                    $incompletePages = array_chunk($incompleteResults, $incompleteRowsPerPage);
                    $incompleteStartsNewPage = $hasPassSection || $hasFailSection;
                @endphp

                <div class="d-print-none">
                    <h5 class="mt-4 fw-bold text-secondary">Incomplete Students ({{ count($incompleteResults) }})</h5>
                    <div class="table-responsive dark-border mb-5">
                        <table class="w-100 table-striped table-bordered text-center table result-table">
                            <thead>
                                <tr class="table-dark text-dark">
                                    <th rowspan="2" class="roll-col"><b>Roll</b></th>
                                    <th rowspan="2" class="name-col"><b>Name</b></th>
                                    @if(count($visibleSubjects) > 0)
                                        @foreach($visibleSubjects as $idx => $sub)
                                            <th class="mini"><b>{{ $subjectShortByIndex[$idx] ?? ($sub->subjectName ?? '-') }}</b></th>
                                        @endforeach
                                    @else
                                        <th class="mini"><b>No subjects</b></th>
                                    @endif
                                    <th rowspan="2" class="tot-col"><b>Total</b></th>
                                    <th rowspan="2" class="grade-col"><b>Grade</b></th>
                                    <th rowspan="2" class="fail-col"><b>Fail</b></th>
                                    <th rowspan="2" class="merit-col"><b>Class Merit</b></th>
                                </tr>
                                <tr class="table-dark text-dark">
                                    @if(count($visibleSubjects) > 0)
                                        @foreach($visibleSubjects as $idx => $sub)
                                            <th class="mini"><b>-</b></th>
                                        @endforeach
                                    @else
                                        <th class="mini"><b>-</b></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($incompleteResults as $i=>$res)
                                @php $rowByName = []; if(isset($res['subjects'])){ foreach($res['subjects'] as $sr){ $rowByName[$sr['name']] = $sr; } } @endphp
                                <tr class="table-secondary">
                                    <td class="roll-col">{{ $res['student']->rollNumber }}</td>
                                    <td class="name-col">{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                    @foreach($visibleSubjects as $sub)
                                        @php $cell = $rowByName[$sub->subjectName] ?? null; @endphp
                                        <td class="subject-td mini">{{ ($cell && is_numeric($cell['total'])) ? $cell['total'] : '' }}</td>
                                    @endforeach
                                    <td class="tot-col">{{ $res['totalMarks'] }}</td>
                                    <td class="grade-col">{{ $res['finalLetter'] }}</td>
                                    <td class="fail-col">{{ (int)($res['subjectFails'] ?? 0) }}</td>
                                    <td class="merit-col">-</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-none d-print-block print-pages">
                    @foreach($incompletePages as $pageIndex => $pageRows)
                        <div class="{{ ($incompleteStartsNewPage && $pageIndex === 0) || $pageIndex > 0 ? 'page-break' : '' }}">
                            <h5 class="mt-2 mb-1 fw-bold text-secondary">Incomplete Students ({{ count($incompleteResults) }})</h5>
                            @include('components.result-header')
                            @include('result.partials.print-summary')
                            <div class="table-responsive dark-border mb-4">
                                <table class="w-100 table-striped table-bordered text-center table result-table">
                                    <thead>
                                        <tr class="table-dark text-dark">
                                            <th rowspan="2" class="roll-col"><b>Roll</b></th>
                                            <th rowspan="2" class="name-col"><b>Name</b></th>
                                            @if(count($visibleSubjects) > 0)
                                                @foreach($visibleSubjects as $idx => $sub)
                                                    <th class="mini"><b>{{ $subjectShortByIndex[$idx] ?? ($sub->subjectName ?? '-') }}</b></th>
                                                @endforeach
                                            @else
                                                <th class="mini"><b>No subjects</b></th>
                                            @endif
                                            <th rowspan="2" class="tot-col"><b>Total</b></th>
                                            <th rowspan="2" class="grade-col"><b>Grade</b></th>
                                            <th rowspan="2" class="fail-col"><b>Fail</b></th>
                                            <th rowspan="2" class="merit-col"><b>Class Merit</b></th>
                                        </tr>
                                        <tr class="table-dark text-dark">
                                            @if(count($visibleSubjects) > 0)
                                                @foreach($visibleSubjects as $idx => $sub)
                                                    <th class="mini"><b>-</b></th>
                                                @endforeach
                                            @else
                                                <th class="mini"><b>-</b></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($pageRows as $res)
                                        @php $rowByName = []; if(isset($res['subjects'])){ foreach($res['subjects'] as $sr){ $rowByName[$sr['name']] = $sr; } } @endphp
                                        <tr class="table-secondary">
                                            <td class="roll-col">{{ $res['student']->rollNumber }}</td>
                                            <td class="name-col">{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                            @foreach($visibleSubjects as $sub)
                                                @php $cell = $rowByName[$sub->subjectName] ?? null; @endphp
                                                <td class="subject-td mini">{{ ($cell && is_numeric($cell['total'])) ? $cell['total'] : '' }}</td>
                                            @endforeach
                                            <td class="tot-col">{{ $res['totalMarks'] }}</td>
                                            <td class="grade-col">{{ $res['finalLetter'] }}</td>
                                            <td class="fail-col">{{ (int)($res['subjectFails'] ?? 0) }}</td>
                                            <td class="merit-col">-</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Compact Mode Rendering: variable subjects per student --}}
            @if($compactMode)
                @if(count($passResultsCompact) > 0)
                    @php
                        $compactPassRowsPerPage = 22;
                        $compactPassPages = array_chunk($passResultsCompact, $compactPassRowsPerPage);
                    @endphp

                    <div class="d-print-none">
                        <h5 class="mt-4 fw-bold text-success">Passed Students ({{ count($passResultsCompact) }})</h5>
                        <div class="table-responsive dark-border mb-5">
                            <table class="w-100 table-striped table-bordered text-center table">
                                <thead>
                                    <tr class="table-dark text-dark">
                                        <th><b>Roll</b></th>
                                        <th><b>Merit</b></th>
                                        <th><b>Name</b></th>
                                        <th class="sid-col"><b>Student ID</b></th>
                                        <th><b>Subjects (with marks)</b></th>
                                        <th><b>Total</b></th>
                                        <th><b>Grade</b></th>
                                        <th><b>GPA</b></th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($passResultsCompact as $i=>$res)
                                    <tr>
                                        <td>{{ $res['student']->rollNumber }}</td>
                                        @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                        <td>{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : (is_numeric($res['meritRank'] ?? null) ? str_pad((string)((int)$res['meritRank']), 2, '0', STR_PAD_LEFT) : '-') }}</td>
                                        <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                        <td class="sid-col">{{ $res['student']->stdId ?? $res['student']->id ?? '-' }}</td>
                                        <td class="text-start">
                                            @if(isset($res['subjectsCompact']) && count($res['subjectsCompact'])>0)
                                                <ul class="mb-0">
                                                    @foreach($res['subjectsCompact'] as $s)
                                                        <li>
                                                            <b>{{ $s['name'] }}{{ (($s['type'] ?? '') === 'Optional') ? ' (4th)' : '' }}</b>: TOTAL {{ $s['total'] }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-muted">No subject marks</span>
                                            @endif
                                        </td>
                                        <td>{{ $res['totalMarks'] }}</td>
                                        <td>{{ $res['finalLetter'] }}</td>
                                        <td>{{ (($res['finalLetter'] ?? '') === 'F') ? '0.00' : (is_numeric($res['finalGpa'] ?? null) ? number_format($res['finalGpa'], 2) : ($res['finalGpa'] ?? '-')) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-none d-print-block print-pages">
                        @foreach($compactPassPages as $pageIndex => $pageRows)
                            <div class="{{ $pageIndex > 0 ? 'page-break' : '' }}">
                                <h5 class="mt-2 mb-1 fw-bold text-success">Passed Students ({{ count($passResultsCompact) }})</h5>
                                @include('components.result-header')
                                @include('result.partials.print-summary')
                                <div class="table-responsive dark-border mb-4">
                                    <table class="w-100 table-striped table-bordered text-center table">
                                        <thead>
                                            <tr class="table-dark text-dark">
                                                <th><b>Roll</b></th>
                                                <th><b>Merit</b></th>
                                                <th><b>Name</b></th>
                                                <th class="sid-col"><b>Student ID</b></th>
                                                <th><b>Subjects (with marks)</b></th>
                                                <th><b>Total</b></th>
                                                <th><b>Grade</b></th>
                                                <th><b>GPA</b></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($pageRows as $res)
                                            <tr>
                                                <td>{{ $res['student']->rollNumber }}</td>
                                                @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                                <td>{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : (is_numeric($res['meritRank'] ?? null) ? str_pad((string)((int)$res['meritRank']), 2, '0', STR_PAD_LEFT) : '-') }}</td>
                                                <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                                <td class="sid-col">{{ $res['student']->stdId ?? $res['student']->id ?? '-' }}</td>
                                                <td class="text-start">
                                                    @if(isset($res['subjectsCompact']) && count($res['subjectsCompact'])>0)
                                                        <ul class="mb-0">
                                                            @foreach($res['subjectsCompact'] as $s)
                                                                <li>
                                                                    <b>{{ $s['name'] }}{{ (($s['type'] ?? '') === 'Optional') ? ' (4th)' : '' }}</b>: TOTAL {{ $s['total'] }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted">No subject marks</span>
                                                    @endif
                                                </td>
                                                <td>{{ $res['totalMarks'] }}</td>
                                                <td>{{ $res['finalLetter'] }}</td>
                                                <td>{{ (($res['finalLetter'] ?? '') === 'F') ? '0.00' : (is_numeric($res['finalGpa'] ?? null) ? number_format($res['finalGpa'], 2) : ($res['finalGpa'] ?? '-')) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(count($failResultsCompact) > 0)
                    @php
                        // Group failed students (compact) by number of failed subjects
                        $failedGroupsC = [];
                        foreach(($failResultsCompact ?? []) as $res){
                            $subFailCount = 0;
                            if(isset($res['subjectFails']) && is_numeric($res['subjectFails'])){
                                $subFailCount = (int) $res['subjectFails'];
                            } elseif(isset($res['subjectsCompact']) && is_array($res['subjectsCompact'])) {
                                foreach($res['subjectsCompact'] as $sr){
                                    $total = $sr['total'] ?? null;
                                    $letter = $sr['grade'] ?? null;
                                    $point  = $sr['gradePoint'] ?? null;
                                    if($letter === 'F'){
                                        $subFailCount++;
                                        continue;
                                    }
                                    if(is_numeric($point) && $point <= 0){
                                        $subFailCount++;
                                        continue;
                                    }
                                    if(is_numeric($total)){
                                        $gl = \App\Models\GradeList::where('minMark','<=',$total)->where('maxMark','>=',$total)->first();
                                        if(($gl?->gradeName) === 'F' || (is_numeric($gl?->gradePoint) && $gl->gradePoint <= 0)){
                                            $subFailCount++;
                                        }
                                    }
                                }
                            }
                            if($subFailCount > 0){
                                $failedGroupsC[$subFailCount] = $failedGroupsC[$subFailCount] ?? [];
                                $failedGroupsC[$subFailCount][] = $res;
                            }
                        }
                        ksort($failedGroupsC);
                    @endphp
                    @foreach($failedGroupsC as $fc => $group)
                        @php
                            $compactFailRowsPerPage = 22;
                            $compactFailPages = array_chunk($group, $compactFailRowsPerPage);
                        @endphp

                        <div class="d-print-none">
                            <h5 class="mt-4 fw-bold text-danger">{{ $fc }} Subject{{ $fc==1?'':'s' }} Failed ({{ count($group) }})</h5>
                            <div class="table-responsive dark-border mb-5">
                                <table class="w-100 table-striped table-bordered text-center table">
                                    <thead>
                                        <tr class="table-dark text-dark">
                                            <th><b>Roll</b></th>
                                            <th><b>Merit</b></th>
                                            <th><b>Name</b></th>
                                            <th class="sid-col"><b>Student ID</b></th>
                                            <th><b>Subjects (with marks)</b></th>
                                            <th><b>Total</b></th>
                                            <th><b>Grade</b></th>
                                            <th><b>GPA</b></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($group as $i=>$res)
                                        <tr>
                                            <td>{{ $res['student']->rollNumber }}</td>
                                            @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                            <td>{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : '-' }}</td>
                                            <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                            <td class="sid-col">{{ $res['student']->stdId ?? $res['student']->id ?? '-' }}</td>
                                            <td class="text-start">
                                                @if(isset($res['subjectsCompact']) && count($res['subjectsCompact'])>0)
                                                    <ul class="mb-0">
                                                        @foreach($res['subjectsCompact'] as $s)
                                                            @php
                                                                $subLetter = $s['grade'] ?? null;
                                                                $subPoint  = $s['gradePoint'] ?? null;
                                                                $subIsFail = ($subLetter === 'F') || (is_numeric($subPoint) && (float)$subPoint <= 0);
                                                            @endphp
                                                            <li>
                                                                <span class="{{ $subIsFail ? 'text-danger fw-bold' : '' }}"><b>{{ $s['name'] }}{{ (($s['type'] ?? '') === 'Optional') ? ' (4th)' : '' }}</b>: TOTAL {{ $s['total'] }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-muted">No subject marks</span>
                                                @endif
                                            </td>
                                            <td>{{ $res['totalMarks'] }}</td>
                                            <td>{{ $res['finalLetter'] }}</td>
                                            <td>{{ (($res['finalLetter'] ?? '') === 'F') ? '0.00' : (is_numeric($res['finalGpa'] ?? null) ? number_format($res['finalGpa'], 2) : ($res['finalGpa'] ?? '-')) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-none d-print-block print-pages">
                            @foreach($compactFailPages as $pageIndex => $pageRows)
                                <div class="{{ (!$loop->parent->first || $pageIndex > 0) ? 'page-break' : '' }}">
                                    <h5 class="mt-2 mb-1 fw-bold text-danger">{{ $fc }} Subject{{ $fc==1?'':'s' }} Failed ({{ count($group) }})</h5>
                                    @include('components.result-header')
                                    @include('result.partials.print-summary')
                                    <div class="table-responsive dark-border mb-4">
                                        <table class="w-100 table-striped table-bordered text-center table">
                                            <thead>
                                                <tr class="table-dark text-dark">
                                                    <th><b>Roll</b></th>
                                                    <th><b>Merit</b></th>
                                                    <th><b>Name</b></th>
                                                    <th class="sid-col"><b>Student ID</b></th>
                                                    <th><b>Subjects (with marks)</b></th>
                                                    <th><b>Total</b></th>
                                                    <th><b>Grade</b></th>
                                                    <th><b>GPA</b></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($pageRows as $res)
                                                <tr>
                                                    <td>{{ $res['student']->rollNumber }}</td>
                                                    @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                                    <td>{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : '-' }}</td>
                                                    <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                                    <td class="sid-col">{{ $res['student']->stdId ?? $res['student']->id ?? '-' }}</td>
                                                    <td class="text-start">
                                                        @if(isset($res['subjectsCompact']) && count($res['subjectsCompact'])>0)
                                                            <ul class="mb-0">
                                                                @foreach($res['subjectsCompact'] as $s)
                                                                    @php
                                                                        $subLetter = $s['grade'] ?? null;
                                                                        $subPoint  = $s['gradePoint'] ?? null;
                                                                        $subIsFail = ($subLetter === 'F') || (is_numeric($subPoint) && (float)$subPoint <= 0);
                                                                    @endphp
                                                                    <li>
                                                                        <span class="{{ $subIsFail ? 'text-danger fw-bold' : '' }}"><b>{{ $s['name'] }}{{ (($s['type'] ?? '') === 'Optional') ? ' (4th)' : '' }}</b>: TOTAL {{ $s['total'] }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <span class="text-muted">No subject marks</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $res['totalMarks'] }}</td>
                                                    <td>{{ $res['finalLetter'] }}</td>
                                                    <td>{{ (($res['finalLetter'] ?? '') === 'F') ? '0.00' : (is_numeric($res['finalGpa'] ?? null) ? number_format($res['finalGpa'], 2) : ($res['finalGpa'] ?? '-')) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif

                @if(count($incompleteResultsCompact) > 0)
                    @php
                        $compactIncRowsPerPage = 22;
                        $compactIncPages = array_chunk($incompleteResultsCompact, $compactIncRowsPerPage);
                    @endphp

                    <div class="d-print-none">
                        <h5 class="mt-4 fw-bold text-secondary">Incomplete Students ({{ count($incompleteResultsCompact) }})</h5>
                        <div class="table-responsive dark-border mb-5">
                            <table class="w-100 table-striped table-bordered text-center table">
                                    <thead>
                                        <tr class="table-dark text-dark">
                                            <th><b>Roll</b></th>
                                            <th><b>Merit</b></th>
                                            <th><b>Name</b></th>
                                            <th class="sid-col"><b>Student ID</b></th>
                                            <th><b>Subjects (with marks)</b></th>
                                            <th><b>Total</b></th>
                                            <th><b>Grade</b></th>
                                            <th><b>GPA</b></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                @foreach($incompleteResultsCompact as $i=>$res)
                                    <tr class="table-secondary">
                                        <td>{{ $res['student']->rollNumber }}</td>
                                        <td>-</td>
                                        <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                        <td class="sid-col">{{ $res['student']->stdId ?? $res['student']->id ?? '-' }}</td>
                                        <td class="text-start">
                                            @if(isset($res['subjectsCompact']) && count($res['subjectsCompact'])>0)
                                                <ul class="mb-0">
                                                    @foreach($res['subjectsCompact'] as $s)
                                                        <li>
                                                            <b>{{ $s['name'] }}{{ (($s['type'] ?? '') === 'Optional') ? ' (4th)' : '' }}</b>: TOTAL {{ $s['total'] }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-muted">No subject marks</span>
                                            @endif
                                        </td>
                                        <td>{{ $res['totalMarks'] }}</td>
                                        <td>{{ $res['finalLetter'] }}</td>
                                        <td>{{ (($res['finalLetter'] ?? '') === 'F') ? '0.00' : (is_numeric($res['finalGpa'] ?? null) ? number_format($res['finalGpa'], 2) : ($res['finalGpa'] ?? '-')) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-none d-print-block print-pages">
                        @foreach($compactIncPages as $pageIndex => $pageRows)
                            <div class="{{ ((count($passResultsCompact)>0 || count($failResultsCompact)>0) && $pageIndex===0) || $pageIndex>0 ? 'page-break' : '' }}">
                                <h5 class="mt-2 mb-1 fw-bold text-secondary">Incomplete Students ({{ count($incompleteResultsCompact) }})</h5>
                                @include('components.result-header')
                                @include('result.partials.print-summary')
                                <div class="table-responsive dark-border mb-4">
                                    <table class="w-100 table-striped table-bordered text-center table">
                                        <thead>
                                            <tr class="table-dark text-dark">
                                                <th><b>Roll</b></th>
                                                <th><b>Merit</b></th>
                                                <th><b>Name</b></th>
                                                <th class="sid-col"><b>Student ID</b></th>
                                                <th><b>Subjects (with marks)</b></th>
                                                <th><b>Total</b></th>
                                                <th><b>Grade</b></th>
                                                <th><b>GPA</b></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($pageRows as $res)
                                            <tr class="table-secondary">
                                                <td>{{ $res['student']->rollNumber }}</td>
                                                <td>-</td>
                                                <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                                <td class="sid-col">{{ $res['student']->stdId ?? $res['student']->id ?? '-' }}</td>
                                                <td class="text-start">
                                                    @if(isset($res['subjectsCompact']) && count($res['subjectsCompact'])>0)
                                                        <ul class="mb-0">
                                                            @foreach($res['subjectsCompact'] as $s)
                                                                <li>
                                                                    <b>{{ $s['name'] }}{{ (($s['type'] ?? '') === 'Optional') ? ' (4th)' : '' }}</b>: TOTAL {{ $s['total'] }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted">No subject marks</span>
                                                    @endif
                                                </td>
                                                <td>{{ $res['totalMarks'] }}</td>
                                                <td>{{ $res['finalLetter'] }}</td>
                                                <td>{{ (($res['finalLetter'] ?? '') === 'F') ? '0.00' : (is_numeric($res['finalGpa'] ?? null) ? number_format($res['finalGpa'], 2) : ($res['finalGpa'] ?? '-')) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        @endif

        
    </div>
</div>
@endsection

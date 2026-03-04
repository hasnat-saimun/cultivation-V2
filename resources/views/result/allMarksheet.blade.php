@extends('result.singleinclude')
@section('backTitle')
All Marksheet
@endsection
@section('backIndex')
<style>
    @page { size: Letter landscape; margin: 3mm; }
    html, body { background: #fff; }
    @media print {
        html, body { background: #fff !important; }
        .main-website, .main-content, .container-fluid { background: #fff !important; }
        .table { border-collapse: collapse !important; }
        .table thead th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .table th, .table td { border: 1px solid #000 !important; padding: 6px !important; vertical-align: middle !important; }
        /* Standardize Name column width for professional print layout */
        .result-table th:nth-child(3), .result-table td:nth-child(3) { width: 220px !important; max-width: 220px !important; white-space: normal !important; word-break: break-word !important; overflow-wrap: anywhere !important; }
        /* Compact tables (without .result-table) also use 3rd column for Name */
        table.table:not(.result-table) th:nth-child(3), table.table:not(.result-table) td:nth-child(3) { width: 220px !important; max-width: 220px !important; white-space: normal !important; word-break: break-word !important; overflow-wrap: anywhere !important; }
        .result-header-band { background: #f3f4f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #000 !important; }
        /* Print: show only marks tables at full width */
        .navbar,
        .sidebar-main,
        .breadcrumbs-area,
        .footer-wrap-layout1,
        .result-section,
        .result-text,
        .alert,
        form { display: none !important; }
        /* Keep header container visible and compact for print */
        .container-fluid { margin: 0 !important; padding: 0 !important; }
        .result-header-band { display: block !important; }
        .dashboard-content-one, .main-website, .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .table-responsive { margin: 0 !important; }
        .table, .result-table { width: 100% !important; }
        /* Force Failed section to start on a new printed page */
        .page-break { break-before: page !important; page-break-before: always !important; }
        /* Print: Student ID column spacing & readability */
        .sid-col {
            font-size: 10px !important;
            white-space: nowrap !important;
            padding: 4px 8px !important;
            text-align: left !important;
            min-width: 90px !important;
            width: 90px !important;
            letter-spacing: 0.1px !important;
        }
        /* Print: Subject header spacing & sizing for better readability */
        .result-table thead th.subject-th { min-width: 60px !important; width: 60px !important; padding: 10px 6px !important; }
        .result-table thead th.subject-th .v-text {
            font-size: 10px !important;
            line-height: 1.2 !important;
            letter-spacing: 0.35px !important;
            margin: 0 2px !important;
            text-align: center !important;
            text-orientation: upright !important;
        }
        /* Print: even tighter for dual-line subject titles */
        .result-table thead th.subject-th.dual { min-width: 65px !important; width: 65px !important; padding: 7px 4px !important; }
        .result-table thead th.subject-th.dual .v-text { font-size: 8.5px !important; line-height: 1.1 !important; letter-spacing: 0.25px !important; }
        /* Print: ensure subject data cells match header width */
        .result-table tbody td.subject-td { min-width: 60px !important; width: 60px !important; padding: 6px 4px !important; }
    }
    /* Professional fixed layout for subject totals grid */
    /* Vertically center all table headers and cells on this page */
    .table th, .table td { vertical-align: middle !important; }
    .result-table { table-layout: fixed; width: 100%; }
    .result-table th, .result-table td { min-width: 88px;font-size: 11px;max-height: 250px; }
    .result-table th:nth-child(1), .result-table td:nth-child(1) { min-width: 60px;font-size: 11px; }
    .result-table th:nth-child(2), .result-table td:nth-child(2) { min-width: 60px;font-size: 11px; }
    .result-table th:nth-child(3), .result-table td:nth-child(3) { min-width: 160px; font-size:13px; }
    /* Screen: Standardize Name column width for professional layout */
    .result-table th:nth-child(3), .result-table td:nth-child(3) { width: 220px; max-width: 220px; white-space: normal; word-break: break-word; overflow-wrap: anywhere; }
    /* Screen: Compact tables (without .result-table) also use 3rd column for Name */
    table.table:not(.result-table) th:nth-child(3), table.table:not(.result-table) td:nth-child(3) { width: 220px; max-width: 220px; white-space: normal; word-break: break-word; overflow-wrap: anywhere; }
    /* Student ID column: consistent width for screen & print */
    .sid-col { font-size: 10px !important; min-width: 90px; width: 90px; }
    /* Slightly tighter margins to accommodate extra column */
    .table-responsive { margin: 8px 0 !important; }
    /* Subject header vertical text: consistent width for screen & print */
    .result-table thead th.subject-th { min-width: 60px; width: 60px; padding: 10px 6px !important; }
    .result-table thead th.subject-th .v-text {
        writing-mode: vertical-rl !important;
        transform: rotate(180deg) !important; /* keep upright after vertical-rl */
        font-size: 10px; line-height: 1.2; letter-spacing: 0.35px; margin: 0 2px;
        white-space: nowrap; text-align: center; text-orientation: upright;
        display: inline-block;
    }
    /* Dual-line subject titles: same sizing for screen & print */
    .result-table thead th.subject-th.dual { min-width: 56px; width: 56px; padding: 7px 4px !important; }
    .result-table thead th.subject-th.dual .v-text { font-size: 8.5px; line-height: 1.1; letter-spacing: 0.25px; }
    /* Subject data cells: align width with headers (overrides generic min-width) */
    .result-table tbody td.subject-td { min-width: 60px; width: 60px; padding: 6px 4px; }
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
        <div class="{{ (!$hasOnlyIncomplete && $hasAnySection) ? 'd-print-block' : 'd-print-none' }}">
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
                } catch (\Throwable $e) {
                    // Fallback to all subjects if any unexpected structure
                    $visibleSubjects = $subjects ?? [];
                }
                $subjectCount = count($visibleSubjects);
            @endphp
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
                <h5 class="mt-4 fw-bold text-success">Passed Students ({{ count($passResults) }})</h5>
                <div class="table-responsive dark-border mb-5">
                    <table class="w-100 table-striped table-bordered text-center table result-table">
                        <tr class="table-dark text-dark">
                            <th rowspan="2" class="sid-col"><b>Student ID</b></th>
                            <th rowspan="2"><b>Roll</b></th>
                            <th rowspan="2"><b>Name</b></th>
                            <th colspan="{{ max($subjectCount, 1) }}"><b>Subject Totals</b></th>
                            <th rowspan="2"><b>Total</b></th>
                            <th rowspan="2"><b>Grade</b></th>
                            <th rowspan="2"><b>GPA</b></th>
                            <th rowspan="2"><b>Merit</b></th>
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($visibleSubjects) > 0)
                                @foreach($visibleSubjects as $sub)
                                    @php
                                        $subjectTitle = ($sub->subjectName ?? '').(isset($optionalSubjectNameMap[$sub->subjectName ?? '']) ? ' (4th)' : '');
                                        $words = preg_split('/\s+/', trim($subjectTitle));
                                        $subjectDisplay = (count($words) > 3)
                                            ? implode(' ', array_slice($words, 3)).'<br>'.implode(' ', array_slice($words, 0, 3))
                                            : $subjectTitle;
                                    @endphp
                                    @php $isDual = (count($words) > 3); @endphp
                                    <th colspan="1" class="subject-th {{ $isDual ? 'dual' : '' }}"><span class="v-text"><b>{!! $subjectDisplay !!}</b></span></th>
                                @endforeach
                            @else
                                <th><b>No subjects</b></th>
                            @endif
                        </tr>
                        @foreach($passResults as $i=>$res)
                            @php $rowByName = []; if(isset($res['subjects'])){ foreach($res['subjects'] as $sr){ $rowByName[$sr['name']] = $sr; } } @endphp
                            <tr>
                                <td class="sid-col">{{ $res['student']->stdId ?? $res['student']->id ?? '-' }}</td>
                                <td>{{ $res['student']->rollNumber }}</td>
                                <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                @foreach($visibleSubjects as $sub)
                                    @php $cell = $rowByName[$sub->subjectName] ?? null; @endphp
                                    <td class="subject-td">{{ ($cell && is_numeric($cell['total'])) ? $cell['total'] : '' }}</td>
                                @endforeach
                                <td>{{ $res['totalMarks'] }}</td>
                                <td>{{ $res['finalLetter'] }}</td>
                                <td>{{ (($res['finalLetter'] ?? '') === 'F') ? '0.00' : (is_numeric($res['finalGpa'] ?? null) ? number_format($res['finalGpa'], 2) : ($res['finalGpa'] ?? '-')) }}</td>
                                @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                <td>{{ $meritRankingMap[$rowKey] ?? ($res['meritRank'] ?? '-') }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            @if(!$compactMode && count($failResults) > 0)
                @if($hasPassSection)
                <div class="d-none d-print-block page-break">
                    @include('components.result-header')
                </div>
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
                    @if(!$loop->first)
                    <div class="d-none d-print-block page-break">
                        @include('components.result-header')
                    </div>
                    @endif
                    <h5 class="mt-4 fw-bold text-danger">{{ $fc }} Subject{{ $fc==1?'':'s' }} Failed ({{ count($group) }})</h5>
                    <div class="table-responsive dark-border mb-5">
                        <table class="w-100 table-striped table-bordered text-center table result-table">
                            <tr class="table-dark text-dark">
                                <th rowspan="2" class="sid-col"><b>Student ID</b></th>
                                <th rowspan="2"><b>Roll</b></th>
                                <th rowspan="2"><b>Name</b></th>
                                <th colspan="{{ max($subjectCount, 1) }}"><b>Subject Totals</b></th>
                                <th rowspan="2"><b>Total</b></th>
                                <th rowspan="2"><b>Grade</b></th>
                                <th rowspan="2"><b>GPA</b></th>
                                <th rowspan="2"><b>Merit</b></th>
                            </tr>
                            <tr class="table-dark text-dark">
                                @if(count($visibleSubjects) > 0)
                                    @foreach($visibleSubjects as $sub)
                                        @php
                                            $subjectTitle = ($sub->subjectName ?? '').(isset($optionalSubjectNameMap[$sub->subjectName ?? '']) ? ' (4th)' : '');
                                            $words = preg_split('/\s+/', trim($subjectTitle));
                                            $subjectDisplay = (count($words) > 3)
                                                ? implode(' ', array_slice($words, 3)).'<br>'.implode(' ', array_slice($words, 0, 3))
                                                : $subjectTitle;
                                        @endphp
                                        @php $isDual = (count($words) > 3); @endphp
                                        <th colspan="1" class="subject-th {{ $isDual ? 'dual' : '' }}"><span class="v-text"><b>{!! $subjectDisplay !!}</b></span></th>
                                    @endforeach
                                @else
                                    <th><b>No subjects</b></th>
                                @endif
                            </tr>
                            @foreach($group as $i=>$res)
                                @php $rowByName = []; if(isset($res['subjects'])){ foreach($res['subjects'] as $sr){ $rowByName[$sr['name']] = $sr; } } @endphp
                                <tr>
                                    <td class="sid-col">{{ $res['student']->stdId ?? $res['student']->id ?? '-' }}</td>
                                    <td>{{ $res['student']->rollNumber }}</td>
                                    <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
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
                                        <td class="subject-td {{ $cellIsFail ? 'text-danger fw-bold' : '' }}">
                                            {{ ($cell && is_numeric($cell['total'])) ? $cell['total'] : '' }}
                                            @if($cellIsFail && count($failedFeatures) > 0)
                                                <small class="d-block" style="font-size:9px; line-height:1.1;">{{ implode('/', $failedFeatures) }}</small>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td>{{ $res['totalMarks'] }}</td>
                                    <td>{{ $res['finalLetter'] }}</td>
                                    <td>{{ (($res['finalLetter'] ?? '') === 'F') ? '0.00' : (is_numeric($res['finalGpa'] ?? null) ? number_format($res['finalGpa'], 2) : ($res['finalGpa'] ?? '-')) }}</td>
                                    @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                    <td>{{ $meritRankingMap[$rowKey] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endforeach
            @endif

            @if(!$compactMode && count($incompleteResults) > 0)
                <div class="d-none d-print-block page-break">
                    @include('components.result-header')
                </div>
                <h5 class="mt-4 fw-bold text-secondary">Incomplete Students ({{ count($incompleteResults) }})</h5>
                <div class="table-responsive dark-border mb-5">
                    <table class="w-100 table-striped table-bordered text-center table result-table">
                        <tr class="table-dark text-dark">
                            <th rowspan="2"><b>Roll</b></th>
                            <th rowspan="2"><b>Merit</b></th>
                            <th rowspan="2"><b>Name</b></th>
                            <th rowspan="2" class="sid-col"><b>Student ID</b></th>
                            <th colspan="{{ max($subjectCount, 1) }}"><b>Subject Totals</b></th>
                            <th rowspan="2"><b>Total</b></th>
                            <th rowspan="2"><b>Grade</b></th>
                            <th rowspan="2"><b>GPA</b></th>
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($visibleSubjects) > 0)
                                @foreach($visibleSubjects as $sub)
                                    @php
                                        $subjectTitle = ($sub->subjectName ?? '').(isset($optionalSubjectNameMap[$sub->subjectName ?? '']) ? ' (4th)' : '');
                                        $words = preg_split('/\s+/', trim($subjectTitle));
                                        $subjectDisplay = (count($words) > 3)
                                            ? implode(' ', array_slice($words, 3)).'<br>'.implode(' ', array_slice($words, 0, 3))
                                            : $subjectTitle;
                                    @endphp
                                    @php $isDual = (count($words) > 3); @endphp
                                    <th colspan="1" class="subject-th {{ $isDual ? 'dual' : '' }}"><span class="v-text"><b>{!! $subjectDisplay !!}</b></span></th>
                                @endforeach
                            @else
                                <th><b>No subjects</b></th>
                            @endif
                        </tr>
                        @foreach($incompleteResults as $i=>$res)
                            @php $rowByName = []; if(isset($res['subjects'])){ foreach($res['subjects'] as $sr){ $rowByName[$sr['name']] = $sr; } } @endphp
                            <tr class="table-secondary">
                                <td>{{ $res['student']->rollNumber }}</td>
                                <td>-</td>
                                <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                <td class="sid-col">{{ $res['student']->stdId ?? $res['student']->id ?? '-' }}</td>
                                @foreach($visibleSubjects as $sub)
                                    @php $cell = $rowByName[$sub->subjectName] ?? null; @endphp
                                    <td class="subject-td">{{ ($cell && is_numeric($cell['total'])) ? $cell['total'] : '' }}</td>
                                @endforeach
                                <td>{{ $res['totalMarks'] }}</td>
                                <td>{{ $res['finalLetter'] }}</td>
                                <td>{{ (($res['finalLetter'] ?? '') === 'F') ? '0.00' : (is_numeric($res['finalGpa'] ?? null) ? number_format($res['finalGpa'], 2) : ($res['finalGpa'] ?? '-')) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            {{-- Compact Mode Rendering: variable subjects per student --}}
            @if($compactMode)
                @if(count($passResultsCompact) > 0)
                    <h5 class="mt-4 fw-bold text-success">Passed Students ({{ count($passResultsCompact) }})</h5>
                    <div class="table-responsive dark-border mb-5">
                        <table class="w-100 table-striped table-bordered text-center table">
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
                            @foreach($passResultsCompact as $i=>$res)
                                <tr>
                                    <td>{{ $res['student']->rollNumber }}</td>
                                    @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                    <td>{{ $meritRankingMap[$rowKey] ?? ($res['meritRank'] ?? '-') }}</td>
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
                        </table>
                    </div>
                @endif

                @if(count($failResultsCompact) > 0)
                    @if($hasPassSection)
                    <div class="d-none d-print-block page-break">
                        @include('components.result-header')
                    </div>
                    @endif
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
                        @if(!$loop->first)
                        <div class="d-none d-print-block page-break">
                            @include('components.result-header')
                        </div>
                        @endif
                        <h5 class="mt-4 fw-bold text-danger">{{ $fc }} Subject{{ $fc==1?'':'s' }} Failed ({{ count($group) }})</h5>
                        <div class="table-responsive dark-border mb-5">
                            <table class="w-100 table-striped table-bordered text-center table">
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
                                @foreach($group as $i=>$res)
                                    <tr>
                                        <td>{{ $res['student']->rollNumber }}</td>
                                        @php $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? ''); @endphp
                                        <td>{{ $meritRankingMap[$rowKey] ?? '-' }}</td>
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
                            </table>
                        </div>
                    @endforeach
                @endif

                @if(count($incompleteResultsCompact) > 0)
                    <div class="d-none d-print-block page-break">
                        @include('components.result-header')
                    </div>
                    <h5 class="mt-4 fw-bold text-secondary">Incomplete Students ({{ count($incompleteResultsCompact) }})</h5>
                    <div class="table-responsive dark-border mb-5">
                        <table class="w-100 table-striped table-bordered text-center table">
                            <tr class="table-dark text-dark">
                                <th><b>Roll</b></th>
                                <th><b>Merit</b></th>
                                <th><b>Name</b></th>
                                <th><b>Subjects (with marks)</b></th>
                                <th><b>Total</b></th>
                                <th><b>Grade</b></th>
                                <th><b>GPA</b></th>
                            </tr>
                            @foreach($incompleteResultsCompact as $i=>$res)
                                <tr class="table-secondary">
                                    <td>{{ $res['student']->rollNumber }}</td>
                                    <td>-</td>
                                    <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
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
                        </table>
                    </div>
                @endif
            @endif
        @endif

        
    </div>
</div>
@endsection
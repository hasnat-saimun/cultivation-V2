@extends('result.singleinclude')
@section('backTitle')
At a glance result
@endsection
@section('backIndex')
<style>
    @page { size: Letter landscape; margin: 3mm; }
    html, body { background: #fff !important; }
    .glance-wrap { background: #fff; margin-top: 14px; }
    .glance-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .glance-table th, .glance-table td { border: 1px solid #111; padding: 4px 6px; text-align: center; vertical-align: middle; font-size: 11px; }
    .glance-table thead th { background: #eceaea; font-weight: 700; }
    .glance-table .name-col { text-align: left; width: 245px; }
    .glance-table .roll-col { width: 52px; }
    .glance-table .mini { width: 40px; }
    .glance-table .tot-col { width: 56px; }
    .glance-table .grade-col { width: 48px; }
    .glance-table .fail-col { width: 36px; }
    .glance-table .merit-col { width: 56px; }

    .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .summary-table th, .summary-table td { border: 1px solid #111; padding: 6px 8px; text-align: center; font-size: 13px; }
    .summary-table th { background: #eceaea; font-weight: 700; }

    .legend-line { font-size: 12px; margin: 8px 2px 10px; }
    .page-break { break-before: page; page-break-before: always; }

    @media print {
        html, body { background: #fff !important; }
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
        .glance-wrap { margin-top: 10px !important; }

        .table-responsive { overflow: visible !important; }
        .glance-table { page-break-inside: auto !important; }
        .glance-table tr { page-break-inside: avoid !important; page-break-after: auto !important; }

        .glance-table th, .glance-table td { border: 1px solid #000 !important; padding: 2px 3px !important; font-size: 9px !important; line-height: 1.1 !important; }
        .glance-table thead th, .summary-table th { background: #eceaea !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .summary-table th, .summary-table td { padding: 4px 6px !important; font-size: 11px !important; }
        .legend-line { margin: 4px 2px 6px !important; font-size: 10px !important; line-height: 1.2 !important; }

        .print-pages .report-header { margin-bottom: 6px !important; }
        .print-pages .glance-wrap { margin-top: 4px !important; }
    }
</style>

<div class="main-website">
    <div class="main-content">
        <div class="container-fluid mb-4">
            <form method="GET" action="{{ route('atGlanceResult') }}" class="row g-2 align-items-end">
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
                <div class="col-md-1">
                    <button class="btn btn-success w-100">Load</button>
                </div>
                <div class="col-md-1">
                    @if($studentsLoaded)
                        <a href="{{ route('atGlanceResult') }}" class="btn btn-warning w-100">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        @if(!$examId || !$classId || !$sessionId)
            <div class="alert alert-info container">Please select required filters (Exam, Class & Session) to view results.</div>
        @endif

        @if($examId && $classId && $sessionId)
            @php
                $allRows = array_merge($passResults ?? [], $failResults ?? [], $incompleteResults ?? []);
                usort($allRows, function($a, $b){
                    $ra = (int)($a['student']->rollNumber ?? 0);
                    $rb = (int)($b['student']->rollNumber ?? 0);
                    if($ra === $rb){ return 0; }
                    return $ra < $rb ? -1 : 1;
                });

                $visibleSubjects = [];
                foreach(($subjects ?? []) as $sub){
                    $hasData = false;
                    foreach($allRows as $res){
                        foreach(($res['subjects'] ?? []) as $sr){
                            if(($sr['name'] ?? null) === ($sub->subjectName ?? null)){
                                if(($sr['total'] ?? '-') !== '-' || ($sr['cq'] ?? '-') !== '-' || ($sr['mcq'] ?? '-') !== '-' || ($sr['practical'] ?? '-') !== '-'){
                                    $hasData = true; break 2;
                                }
                            }
                        }
                    }
                    if($hasData){ $visibleSubjects[] = $sub; }
                }

                $totalStudents = \App\Models\newAdmission::where('className', (int)$classId)
                    ->where('sessName', (int)$sessionId)
                    ->when($sectionId, function($q) use ($sectionId){ return $q->where('sectionName', (int)$sectionId); })
                    ->when($departmentId, function($q) use ($departmentId){ return $q->where('departmentName', (int)$departmentId); })
                    ->count();
                $presentCount = count($allRows);
                $absentCount = max(0, (int)$totalStudents - (int)$presentCount);

                $sessionName = optional(\App\Models\sessionManage::find((int)$sessionId))->session ?? ($sessionId ?: '-');
                $className = optional(\App\Models\classManage::find((int)$classId))->className ?? ($classId ?: '-');
                $groupName = $sectionId ? (optional(\App\Models\sectionManage::find((int)$sectionId))->section ?? 'N/A') : 'N/A';
                $examName = optional(\App\Models\Exam::find((int)$examId))->examName ?? '-';

                $subjectLegend = [];
                $subjectShortByName = [];
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
                    $uniqueShort = ($usedShortCounts[$short] === 1) ? $short : ($short.'-'.$usedShortCounts[$short]);

                    if(!isset($subjectShortByName[$name])){
                        $subjectShortByName[$name] = $uniqueShort;
                    }
                    $subjectShortByIndex[$idx] = $uniqueShort;
                    $subjectLegend[] = ['short' => $uniqueShort, 'full' => $name];
                }

                $legendParts = [];
                foreach($subjectLegend as $pair){
                    $legendParts[] = ($pair['short'] ?? '').'='.($pair['full'] ?? '');
                }

                $meritRankingMap = [];
                $rankItems = [];
                foreach ($allRows as $res) {
                    $key = (string)($res['student']->id ?? $res['student']->stdId ?? '');
                    if($key === '') { continue; }
                    $total = isset($res['totalMarks']) && is_numeric($res['totalMarks']) ? (float)$res['totalMarks'] : 0.0;
                    $rankItems[] = ['key'=>$key, 'total'=>$total];
                }
                usort($rankItems, function($a,$b){
                    if ($a['total'] == $b['total']) return 0;
                    return ($a['total'] > $b['total']) ? -1 : 1;
                });
                $rank = 0; $prevTotal = null;
                foreach ($rankItems as $it) {
                    if ($prevTotal === null || $it['total'] != $prevTotal) { $rank++; $prevTotal = $it['total']; }
                    $meritRankingMap[$it['key']] = $rank;
                }

                $subjectSubColumnCount = 0;
                foreach($visibleSubjects as $sub){
                    $hasCQ = (float)($sub->CQ ?? 0) > 0;
                    $hasMCQ = (float)($sub->MCQ ?? 0) > 0;
                    $hasPR = (float)($sub->Practical ?? 0) > 0;
                    $subjectSubColumnCount += (($hasCQ?1:0) + ($hasMCQ?1:0) + ($hasPR?1:0));
                    if(!$hasCQ && !$hasMCQ && !$hasPR){ $subjectSubColumnCount += 1; }
                }
                $totalColumns = 2 + $subjectSubColumnCount + 4;

                $server = \App\Models\ServerConfig::orderBy('id','DESC')->first();
                $logoUrl = null;
                if(!empty($server?->logo)){
                    $base = rtrim(config('app.url'), '/').'/public';
                    $logoUrl = preg_match('~^https?://~i', $server->logo) ? $server->logo : $base.'/upload/image/cultivation/'.$server->logo;
                }

                $hasAnySection = count($allRows) > 0;

                $density = (int)($subjectSubColumnCount ?? 0);
                if($density >= 20){
                    $firstPageRows = 10;
                    $nextPageRows = 22;
                } elseif($density >= 14){
                    $firstPageRows = 11;
                    $nextPageRows = 24;
                } else {
                    $firstPageRows = 12;
                    $nextPageRows = 26;
                }
                $printPages = [];
                if(count($allRows) > 0){
                    $firstChunk = array_slice($allRows, 0, $firstPageRows);
                    if(count($firstChunk) > 0){
                        $printPages[] = $firstChunk;
                    }
                    $remainingRows = array_slice($allRows, $firstPageRows);
                    foreach(array_chunk($remainingRows, $nextPageRows) as $chunk){
                        if(count($chunk) > 0){
                            $printPages[] = $chunk;
                        }
                    }
                }
            @endphp

            <div class="row">
                <div class="col-12">
                    <div class="d-print-none">
                        @include('components.result-header')
                    </div>
                </div>
                <div class="col-12">
                    @if($studentsLoaded && count($allRows) === 0)
                        <div class="alert alert-warning container">No marks found for the selected filters.</div>
                    @else
                        <div class="d-none d-print-block print-pages">
                            @foreach($printPages as $pageIndex => $pageRows)
                                <div class="{{ $pageIndex > 0 ? 'page-break' : '' }}">
                                    @include('components.result-header')
                                    @include('result.partials.print-summary')

                                    <div class="container-fluid glance-wrap mb-4">
                                        <div class="table-responsive">
                                            <table class="glance-table">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2" class="roll-col">Roll</th>
                                                        <th rowspan="2" class="name-col">Name</th>
                                                        @foreach($visibleSubjects as $idx => $sub)
                                                            @php
                                                                $hasCQ = (float)($sub->CQ ?? 0) > 0;
                                                                $hasMCQ = (float)($sub->MCQ ?? 0) > 0;
                                                                $hasPR = (float)($sub->Practical ?? 0) > 0;
                                                                $colspan = ($hasCQ?1:0) + ($hasMCQ?1:0) + ($hasPR?1:0);
                                                                if($colspan < 1){ $colspan = 1; }
                                                            @endphp
                                                            <th colspan="{{ $colspan }}">{{ $subjectShortByIndex[$idx] ?? ($subjectShortByName[$sub->subjectName] ?? $sub->subjectName) }}</th>
                                                        @endforeach
                                                        <th rowspan="2" class="tot-col">Total</th>
                                                        <th rowspan="2" class="grade-col">Grade</th>
                                                        <th rowspan="2" class="fail-col">Fail</th>
                                                        <th rowspan="2" class="merit-col">Class Merit</th>
                                                    </tr>
                                                    <tr>
                                                        @foreach($visibleSubjects as $sub)
                                                            @php
                                                                $hasCQ = (float)($sub->CQ ?? 0) > 0;
                                                                $hasMCQ = (float)($sub->MCQ ?? 0) > 0;
                                                                $hasPR = (float)($sub->Practical ?? 0) > 0;
                                                            @endphp
                                                            @if($hasCQ)<th class="mini">CQ</th>@endif
                                                            @if($hasMCQ)<th class="mini">MCQ</th>@endif
                                                            @if($hasPR)<th class="mini">PR</th>@endif
                                                            @if(!$hasCQ && !$hasMCQ && !$hasPR)<th class="mini">-</th>@endif
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($pageRows as $res)
                                                        @php
                                                            $rowByName = [];
                                                            foreach(($res['subjects'] ?? []) as $sr){ $rowByName[$sr['name']] = $sr; }
                                                            $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? '');
                                                            $fails = (int)($res['subjectFails'] ?? 0);
                                                            if($fails === 0){
                                                                foreach(($res['subjects'] ?? []) as $sr){
                                                                    $l = $sr['grade'] ?? null;
                                                                    $gp = $sr['gradePoint'] ?? null;
                                                                    if($l === 'F' || (is_numeric($gp) && (float)$gp <= 0)){ $fails++; }
                                                                }
                                                            }
                                                        @endphp
                                                        <tr>
                                                            <td class="roll-col">{{ $res['student']->rollNumber }}</td>
                                                            <td class="name-col">{{ trim(($res['student']->fullName ?? '').' '.($res['student']->sureName ?? '')) }}</td>
                                                            @foreach($visibleSubjects as $sub)
                                                                @php
                                                                    $cell = $rowByName[$sub->subjectName] ?? null;
                                                                    $hasCQ = (float)($sub->CQ ?? 0) > 0;
                                                                    $hasMCQ = (float)($sub->MCQ ?? 0) > 0;
                                                                    $hasPR = (float)($sub->Practical ?? 0) > 0;
                                                                @endphp
                                                                @if($hasCQ)
                                                                    <td class="mini">@if($cell && is_numeric($cell['cq'] ?? null)){{ $cell['cq'] }}@endif</td>
                                                                @endif
                                                                @if($hasMCQ)
                                                                    <td class="mini">@if($cell && is_numeric($cell['mcq'] ?? null)){{ $cell['mcq'] }}@endif</td>
                                                                @endif
                                                                @if($hasPR)
                                                                    <td class="mini">@if($cell && is_numeric($cell['practical'] ?? null)){{ $cell['practical'] }}@endif</td>
                                                                @endif
                                                                @if(!$hasCQ && !$hasMCQ && !$hasPR)
                                                                    <td class="mini">{{ ($cell && is_numeric($cell['total'] ?? null)) ? $cell['total'] : '' }}</td>
                                                                @endif
                                                            @endforeach
                                                            <td class="tot-col">{{ $res['totalMarks'] }}</td>
                                                            <td class="grade-col">{{ $res['finalLetter'] }}</td>
                                                            <td class="fail-col">{{ $fails }}</td>
                                                            <td class="merit-col">{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="container-fluid glance-wrap mb-4 d-print-none">
                            @include('result.partials.print-summary')
                            <div class="table-responsive">
                                <table class="glance-table">
                                    <thead>
                                        <tr>
                                            <th rowspan="2" class="roll-col">Roll</th>
                                            <th rowspan="2" class="name-col">Name</th>
                                            @foreach($visibleSubjects as $idx => $sub)
                                                @php
                                                    $hasCQ = (float)($sub->CQ ?? 0) > 0;
                                                    $hasMCQ = (float)($sub->MCQ ?? 0) > 0;
                                                    $hasPR = (float)($sub->Practical ?? 0) > 0;
                                                    $colspan = ($hasCQ?1:0) + ($hasMCQ?1:0) + ($hasPR?1:0);
                                                    if($colspan < 1){ $colspan = 1; }
                                                @endphp
                                                <th colspan="{{ $colspan }}">{{ $subjectShortByIndex[$idx] ?? ($subjectShortByName[$sub->subjectName] ?? $sub->subjectName) }}</th>
                                            @endforeach
                                            <th rowspan="2" class="tot-col">Total</th>
                                            <th rowspan="2" class="grade-col">Grade</th>
                                            <th rowspan="2" class="fail-col">Fail</th>
                                            <th rowspan="2" class="merit-col">Class Merit</th>
                                        </tr>
                                        <tr>
                                            @foreach($visibleSubjects as $sub)
                                                @php
                                                    $hasCQ = (float)($sub->CQ ?? 0) > 0;
                                                    $hasMCQ = (float)($sub->MCQ ?? 0) > 0;
                                                    $hasPR = (float)($sub->Practical ?? 0) > 0;
                                                @endphp
                                                @if($hasCQ)<th class="mini">CQ</th>@endif
                                                @if($hasMCQ)<th class="mini">MCQ</th>@endif
                                                @if($hasPR)<th class="mini">PR</th>@endif
                                                @if(!$hasCQ && !$hasMCQ && !$hasPR)<th class="mini">-</th>@endif
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allRows as $res)
                                            @php
                                                $rowByName = [];
                                                foreach(($res['subjects'] ?? []) as $sr){ $rowByName[$sr['name']] = $sr; }
                                                $rowKey = (string)($res['student']->id ?? $res['student']->stdId ?? '');
                                                $fails = (int)($res['subjectFails'] ?? 0);
                                                if($fails === 0){
                                                    foreach(($res['subjects'] ?? []) as $sr){
                                                        $l = $sr['grade'] ?? null;
                                                        $gp = $sr['gradePoint'] ?? null;
                                                        if($l === 'F' || (is_numeric($gp) && (float)$gp <= 0)){ $fails++; }
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td class="roll-col">{{ $res['student']->rollNumber }}</td>
                                                <td class="name-col">{{ trim(($res['student']->fullName ?? '').' '.($res['student']->sureName ?? '')) }}</td>
                                                @foreach($visibleSubjects as $sub)
                                                    @php
                                                        $cell = $rowByName[$sub->subjectName] ?? null;
                                                        $hasCQ = (float)($sub->CQ ?? 0) > 0;
                                                        $hasMCQ = (float)($sub->MCQ ?? 0) > 0;
                                                        $hasPR = (float)($sub->Practical ?? 0) > 0;
                                                    @endphp
                                                    @if($hasCQ)
                                                        <td class="mini">@if($cell && is_numeric($cell['cq'] ?? null)){{ $cell['cq'] }}@endif</td>
                                                    @endif
                                                    @if($hasMCQ)
                                                        <td class="mini">@if($cell && is_numeric($cell['mcq'] ?? null)){{ $cell['mcq'] }}@endif</td>
                                                    @endif
                                                    @if($hasPR)
                                                        <td class="mini">@if($cell && is_numeric($cell['practical'] ?? null)){{ $cell['practical'] }}@endif</td>
                                                    @endif
                                                    @if(!$hasCQ && !$hasMCQ && !$hasPR)
                                                        <td class="mini">{{ ($cell && is_numeric($cell['total'] ?? null)) ? $cell['total'] : '' }}</td>
                                                    @endif
                                                @endforeach
                                                <td class="tot-col">{{ $res['totalMarks'] }}</td>
                                                <td class="grade-col">{{ $res['finalLetter'] }}</td>
                                                <td class="fail-col">{{ $fails }}</td>
                                                <td class="merit-col">{{ is_numeric($meritRankingMap[$rowKey] ?? null) ? str_pad((string)((int)$meritRankingMap[$rowKey]), 2, '0', STR_PAD_LEFT) : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            

        @endif
    </div>
</div>
@endsection

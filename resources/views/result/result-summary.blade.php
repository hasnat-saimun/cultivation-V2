@extends('result.singleinclude')
@section('backTitle')
Result summary
@endsection
@section('backIndex')
<style>
    @page { size: Letter landscape; margin: 3mm; }
    html, body { background: #fff !important; }
    .summary-wrap { background: #fff; margin-top: 14px; }
    .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .summary-table th, .summary-table td { border: 1px solid #111; padding: 6px 8px; text-align: center; font-size: 13px; }
    .summary-table th { background: #eceaea; font-weight: 700; }
    .summary-table .left { text-align: left; }
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
        .summary-wrap { margin-top: 8px !important; }

        .summary-table { page-break-inside: auto !important; }
        .summary-table tr { page-break-inside: avoid !important; page-break-after: auto !important; }
        .summary-table th, .summary-table td { border: 1px solid #000 !important; padding: 3px 5px !important; font-size: 10px !important; line-height: 1.15 !important; }
        .summary-table th { background: #eceaea !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .print-pages .report-header { margin-bottom: 6px !important; }
    }
</style>

<div class="main-website">
    <div class="main-content">
        <div class="container-fluid mb-4">
            <form method="GET" action="{{ route('result.summary') }}" class="row g-2 align-items-end">
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
                        <a href="{{ route('result.summary') }}" class="btn btn-warning w-100">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        @if(!$examId || !$classId || !$sessionId)
            <div class="alert alert-info container">Please select required filters (Exam, Class & Session) to view summary.</div>
        @else
            @if(!$hasData)
                <div class="alert alert-warning container">No marks found for the selected filters.</div>
            @else
                @php
                    $present = (int)($overallSummary['present'] ?? 0);
                    $pass = (int)($overallSummary['pass'] ?? 0);
                    $fail = (int)($overallSummary['fail'] ?? 0);
                    $passRate = $present > 0 ? round(($pass / $present) * 100, 2) : 0;
                    $failRate = $present > 0 ? round(($fail / $present) * 100, 2) : 0;

                    $subjectRowsPerPage = 22;
                    $subjectPages = array_chunk($subjectStats ?? [], $subjectRowsPerPage);
                    if(count($subjectPages) === 0){
                        $subjectPages = [[]];
                    }

                    $sessionName = optional(\App\Models\sessionManage::find((int)$sessionId))->session ?? ($sessionId ?: '-');
                    $className = optional(\App\Models\classManage::find((int)$classId))->className ?? ($classId ?: '-');
                    $groupName = $sectionId ? (optional(\App\Models\sectionManage::find((int)$sectionId))->section ?? 'N/A') : 'N/A';
                    $examName = optional(\App\Models\Exam::find((int)$examId))->examName ?? '-';

                    $failureSummaryParts = [];
                    foreach(($failureBuckets ?? []) as $failedSubjectCount => $studentCount){
                        $failureSummaryParts[] = $failedSubjectCount.' Subject'.((int)$failedSubjectCount === 1 ? '' : 's').'-'.str_pad((string)$studentCount, 2, '0', STR_PAD_LEFT);
                    }
                    $failureSummaryLine = count($failureSummaryParts) > 0 ? implode(', ', $failureSummaryParts) : 'No failed-subject bucket found.';
                @endphp

                <div class="d-print-none">
                    @include('components.result-header')
                </div>

                <div class="d-none d-print-block print-pages">
                    @foreach($subjectPages as $pageIndex => $pageRows)
                        <div class="{{ $pageIndex > 0 ? 'page-break' : '' }}">
                            @include('components.result-header')

                            <div class="container-fluid summary-wrap mb-2">
                                <table class="summary-table">
                                    <thead>
                                        <tr>
                                            <th>Academic Year</th>
                                            <th>Class</th>
                                            <th>Group</th>
                                            <th>Exam</th>
                                            <th>Total Student</th>
                                            <th>Present</th>
                                            <th>Absent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ $sessionName }}</td>
                                            <td>{{ $className }}</td>
                                            <td>{{ $groupName }}</td>
                                            <td>{{ $examName }}</td>
                                            <td>{{ $overallSummary['total'] ?? 0 }}</td>
                                            <td>{{ $overallSummary['present'] ?? 0 }}</td>
                                            <td>{{ $overallSummary['absent'] ?? 0 }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="container-fluid summary-wrap mb-2">
                                <table class="summary-table">
                                    <thead>
                                        <tr>
                                            <th>Pass</th>
                                            <th>Fail</th>
                                            <th>Incomplete</th>
                                            <th>Pass Rate (%)</th>
                                            <th>Fail Rate (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ $overallSummary['pass'] ?? 0 }}</td>
                                            <td>{{ $overallSummary['fail'] ?? 0 }}</td>
                                            <td>{{ $overallSummary['incomplete'] ?? 0 }}</td>
                                            <td>{{ number_format($passRate, 2) }}</td>
                                            <td>{{ number_format($failRate, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="container-fluid summary-wrap mb-2">
                                <h5 class="mb-2">Failure Subject Count Summary</h5>
                                <div class="mb-2"><strong>{{ $failureSummaryLine }}</strong></div>
                                <table class="summary-table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Students</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($failureBuckets ?? [] as $failedSubjectCount => $studentCount)
                                            <tr>
                                                <td class="left">{{ $failedSubjectCount }} Subject{{ (int)$failedSubjectCount === 1 ? '' : 's' }}</td>
                                                <td>{{ str_pad((string)$studentCount, 2, '0', STR_PAD_LEFT) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2">No failed-subject bucket found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="container-fluid summary-wrap mb-2">
                                <h5 class="mb-2">Subject-wise Pass/Fail Summary</h5>
                                <div class="table-responsive">
                                    <table class="summary-table">
                                        <thead>
                                            <tr>
                                                <th>SL</th>
                                                <th class="left">Subject</th>
                                                <th>Appeared</th>
                                                <th>Pass</th>
                                                <th>Fail</th>
                                                <th>Missing</th>
                                                <th>Pass Rate (%)</th>
                                                <th>Fail Rate (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($pageRows as $localIndex => $row)
                                                <tr>
                                                    <td>{{ ($pageIndex * $subjectRowsPerPage) + $localIndex + 1 }}</td>
                                                    <td class="left">{{ $row['subjectName'] }}</td>
                                                    <td>{{ $row['appeared'] }}</td>
                                                    <td>{{ $row['pass'] }}</td>
                                                    <td>{{ $row['fail'] }}</td>
                                                    <td>{{ $row['missing'] }}</td>
                                                    <td>{{ number_format((float)$row['passRate'], 2) }}</td>
                                                    <td>{{ number_format((float)$row['failRate'], 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8">No subject-wise data found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="container-fluid summary-wrap mb-4 d-print-none">
                    <h5 class="mb-2">Overall Result Summary</h5>
                    <div class="table-responsive">
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Total Student</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Pass</th>
                                    <th>Fail</th>
                                    <th>Incomplete</th>
                                    <th>Pass Rate (%)</th>
                                    <th>Fail Rate (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $overallSummary['total'] ?? 0 }}</td>
                                    <td>{{ $overallSummary['present'] ?? 0 }}</td>
                                    <td>{{ $overallSummary['absent'] ?? 0 }}</td>
                                    <td>{{ $overallSummary['pass'] ?? 0 }}</td>
                                    <td>{{ $overallSummary['fail'] ?? 0 }}</td>
                                    <td>{{ $overallSummary['incomplete'] ?? 0 }}</td>
                                    <td>{{ number_format($passRate, 2) }}</td>
                                    <td>{{ number_format($failRate, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h5 class="mb-2 mt-3">Subject-wise Pass/Fail Summary</h5>
                    <div class="table-responsive">
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th class="left">Subject</th>
                                    <th>Appeared</th>
                                    <th>Pass</th>
                                    <th>Fail</th>
                                    <th>Missing</th>
                                    <th>Pass Rate (%)</th>
                                    <th>Fail Rate (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subjectStats as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="left">{{ $row['subjectName'] }}</td>
                                        <td>{{ $row['appeared'] }}</td>
                                        <td>{{ $row['pass'] }}</td>
                                        <td>{{ $row['fail'] }}</td>
                                        <td>{{ $row['missing'] }}</td>
                                        <td>{{ number_format((float)$row['passRate'], 2) }}</td>
                                        <td>{{ number_format((float)$row['failRate'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">No subject-wise data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h5 class="mb-2 mt-3">Failure Subject Count Summary</h5>
                    <div class="mb-2"><strong>{{ $failureSummaryLine }}</strong></div>
                    <div class="table-responsive">
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($failureBuckets ?? [] as $failedSubjectCount => $studentCount)
                                    <tr>
                                        <td class="left">{{ $failedSubjectCount }} Subject{{ (int)$failedSubjectCount === 1 ? '' : 's' }}</td>
                                        <td>{{ str_pad((string)$studentCount, 2, '0', STR_PAD_LEFT) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2">No failed-subject bucket found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

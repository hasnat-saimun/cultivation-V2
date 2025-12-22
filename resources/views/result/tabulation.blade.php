@extends('result.singleinclude')
@section('backTitle')
Tabulation Sheet
@endsection
@section('backIndex')
<style>
    @page { size: legal landscape; margin: 5mm; }
    html, body { background: #fff; }
    .tabulation .card { border: none; }
    .tab-header { margin-bottom: 8px; }
    .main-website, .main-content, .container-fluid { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
    .dashboard-page-one, .dashboard-content-one, #wrapper, .wrapper { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; background: #fff !important; }
    @media print {
        html, body { background: #fff !important; }
        body { margin: 0 !important; }
        .main-website, .main-content, .container-fluid { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
        .dashboard-page-one, .dashboard-content-one, #wrapper, .wrapper { width: 100% !important; max-width: none !important; padding: 0 !important; margin: 0 !important; }
        .tab-header { display: none !important; }
        .row { margin: 0 !important; }
        [class^="col"], .col, .col-1, .col-2, .col-3, .col-4, .col-5, .col-6, .col-7, .col-8, .col-9, .col-10, .col-11, .col-12 { padding: 0 !important; }
        .table-responsive { overflow: visible !important; }
        .table { border-collapse: collapse !important; table-layout: fixed !important; font-size: 11px !important; width: 100% !important; }
        .table thead { display: table-header-group !important; }
        .table tfoot { display: table-footer-group !important; }
        tr { page-break-inside: avoid !important; }
        .table thead th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; white-space: nowrap !important; }
        .table th, .table td { border: 1px solid #000 !important; padding: 4px !important; }
        .table-striped tr { background: transparent !important; }
    }
    .table th:nth-child(1), .table td:nth-child(1) { width: 26mm; }
    .table th:nth-child(2), .table td:nth-child(2) { text-align: left; width: 80mm; }
</style>
<div class="main-website">
    <div class="main-content">
        @include('components.institute-header')
        <div class="container-fluid tab-header">
            <form method="GET" action="{{ route('tabulationSheet') }}" class="row g-2 align-items-end">
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
                    <label class="form-label">Session</label>
                    <select name="sessionId" class="form-control">
                        <option value="">All</option>
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
                    <button class="btn btn-success w-100">Load Tabulation</button>
                </div>
                <div class="col-md-2">
                    @if($studentsLoaded)
                        <a href="{{ route('tabulationSheet') }}" class="btn btn-warning w-100">Reset</a>
                    @endif
                </div>
                <div class="col-md-12 d-flex justify-content-end">
                    <button class="btn btn-primary btn-sm d-print-none" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                </div>
            </form>
        </div>

        @if(!$examId || !$classId)
            <div class="alert alert-info container">Please select required filters (Exam & Class) to view tabulation.</div>
        @endif

        @if($examId && $classId)
            <div class="result-section">
                <div class="row">
                    <div class="col-12">
                        <div class="result-text text-center mt-2">
                            @php $headingText = 'Tabulation Sheet' . (($summary['exam'] ?? null) ? (' - ' . $summary['exam']->examName) : ''); @endphp
                            <h4 class="fw-bold">{{ strlen($headingText) > 32 ? 'Horizontali' : $headingText }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            @php $subjectCount = count($subjects); @endphp
            @if($studentsLoaded && count($rows) === 0)
                <div class="alert alert-warning container">No marks found for the selected filters.</div>
            @endif

            <div class="table-responsive dark-border mb-5 tabulation">
                <table class="w-100 table-striped table-bordered text-center table">
                    <thead>
                        <tr class="table-dark text-dark">
                            <th rowspan="3"><b>Roll</b></th>
                            <th rowspan="3"><b>Name</b></th>
                            <th colspan="{{ max($subjectCount*6, 1) }}"><b>Subject</b></th>
                            <th rowspan="3"><b>Total</b></th>
                            <th rowspan="3"><b>Grade</b></th>
                            <th rowspan="3"><b>GPA</b></th>
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th colspan="6"><b>{{ $sub->subjectName }}</b></th>
                                @endforeach
                            @else
                                <th><b>No subjects</b></th>
                            @endif
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th><b>CQ</b></th>
                                    <th><b>MCQ</b></th>
                                    <th><b>P</b></th>
                                    <th><b>TOTAL</b></th>
                                    <th><b>GRADE</b></th>
                                    <th><b>POINT</b></th>
                                @endforeach
                            @else
                                <th><b>-</b></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $i=>$res)
                            <tr>
                                <td>{{ $res['student']->rollNumber }}</td>
                                <td class="text-start">{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                @foreach($res['subjects'] as $sres)
                                    <td>{{ $sres['cq'] }}</td>
                                    <td>{{ $sres['mcq'] }}</td>
                                    <td>{{ $sres['practical'] }}</td>
                                    <td>{{ $sres['total'] }}</td>
                                    <td>{{ $sres['grade'] }}</td>
                                    <td>{{ $sres['gradePoint'] }}</td>
                                @endforeach
                                <td>{{ $res['totalMarks'] }}</td>
                                <td>{{ $res['finalLetter'] }}</td>
                                <td>{{ $res['finalGpa'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

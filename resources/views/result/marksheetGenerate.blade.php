@extends('result.include')

@section('backTitle')
Marksheet Generate
@endsection

@section('backIndex')
<style>
    @page { size: A4 portrait; margin: 8mm; }
    html, body { background: #fff; }
    @media print {
        html, body { background: #fff !important; margin: 0 !important; }
        #wrapper, .wrapper, .dashboard-page-one, .dashboard-content-one {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .d-print-none, .no-print { display: none !important; }
        .breadcrumbs-area, .header-menu-one, .navbar, .sidebar-main, .footer-wrap-layout1 {
            display: none !important;
        }
        .marksheet { border: 0 !important; margin: 0 !important; padding: 0 !important; }
        .marksheet .card { box-shadow: none !important; border: none !important; }
        .marksheet .transcript { border: 1px solid #e5e7eb !important; }
        .transcript-page { width: 100% !important; page-break-inside: avoid !important; break-inside: avoid !important; }
        .signature-row { width: 100% !important; }
        .marksheet table.table, .marksheet table.table-bordered { border-collapse: collapse !important; }
        .marksheet table.table thead th, .marksheet table.table-bordered thead th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .marksheet table.table th, .marksheet table.table td, .marksheet table.table-bordered th, .marksheet table.table-bordered td { border: 1px solid #000 !important; }
        .report-header { margin-bottom: 4px !important; padding-bottom: 3px !important; border-bottom: 1px solid #cbd5e1 !important; }
        .report-header .hdr-logo { height: 42px !important; width: 42px !important; }
        .report-header h2 { font-size: 17px !important; margin: 0 !important; }
        .report-header p { font-size: 10px !important; margin: 1px 0 !important; }
        .failed-subjects { font-size: 10px !important; }
        .failed-subjects h4 { margin-bottom: 4px !important; }
        .failed-subject-grid { margin: 4px 0 !important; }
        .transcript-footer,
        .failed-subjects,
        .grading-table-wrap,
        .signature-row { page-break-inside: avoid !important; break-inside: avoid !important; }
    }
    .marksheet .transcript { background: #fff; padding: 10px 12px; border: 1px solid #e5e7eb; }
    .marksheet table.table, .marksheet table.table-bordered { font-size: 11px; border-collapse: collapse; }
    .marksheet table.table thead th, .marksheet table.table-bordered thead th { background: #f3f4f6; font-weight: 700; }
    .marksheet table.table th, .marksheet table.table td, .marksheet table.table-bordered th, .marksheet table.table-bordered td { padding: 4px 5px; border: 1px solid #2d3748; }
    .marksheet h3 { margin-top: 5px; margin-bottom: 4px; font-size: 16px; }
    .signature-row { width: 100%; margin-top: 7px; page-break-inside: avoid; break-inside: avoid; }
    .marksheet .signature-row td { width: 33.333%; height: 48px; padding: 0 10px; border: 0; text-align: center; vertical-align: bottom; }
    .signature-line { width: 75%; margin: 0 auto; border-top: 1px solid #111827; }
    .sign-image { height: 44px; width: auto; max-width: 140px; object-fit: contain; margin-bottom: 8px; }
    .meta-wrap { width: 100%; margin-bottom: 4px; border-collapse: collapse; }
    .marksheet .meta-wrap > tbody > tr > td { border: 0; vertical-align: top; padding: 0; }
    .meta-left { width: 62%; padding-right: 7px !important; }
    .meta-right { width: 38%; }
    .student-info { width: 100%; border-collapse: collapse; }
    .marksheet .student-info th, .marksheet .student-info td { border: 0; padding: 2px 4px; line-height: 1.2; }
    .student-info th { width: auto; text-align: left; white-space: nowrap; }
    .student-info td { word-break: break-word; overflow-wrap: anywhere; }
    .failed-subjects { margin: 0; padding: 5px 7px; border-left: 3px solid #b91c1c; background: #fff7f7; }
    .failed-subjects h4 { margin: 0 0 3px; font-size: 12px; line-height: 1.2; }
    .failed-subject-grid { width: 100%; margin: 0; table-layout: fixed; border-collapse: collapse; page-break-inside: avoid; }
    .marksheet .failed-subject-grid td { width: 33.333%; border: 0; padding: 1px 10px 1px 0; vertical-align: top; line-height: 1.25; overflow-wrap: anywhere; word-break: normal; }
    .failed-subject-empty { visibility: hidden; }
    .grading-table-wrap { border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden; background: #fff; }
    .grading-table-title { padding: 4px 6px; color: #fff; background: #334155; text-align: center; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .grading-table { width: 100%; border-collapse: collapse; margin: 0; font-size: 10px; text-align: center; }
    .marksheet .grading-table th, .marksheet .grading-table td { padding: 2px 4px; border: 0; border-bottom: 1px solid #e2e8f0; }
    .grading-table th { color: #334155; background: #f1f5f9; font-weight: 700; }
    .grading-table tbody tr:nth-child(even) { background: #f8fafc; }
    .grading-table tbody tr:last-child td { border-bottom: 0; }
    .grading-letter, .grading-point { font-weight: 700; }
    .transcript-footer { page-break-inside: avoid; break-inside: avoid; }
    .report-header { width: 100%; text-align: center; margin: 0 0 3px; padding-bottom: 3px; border-bottom: 1px solid #cbd5e1; }
    .report-header .hdr-logo { display: inline-block; max-height: 42px; max-width: 64px; margin-bottom: 2px; object-fit: contain; }
    .report-header h2 { margin: 0; font-size: 17px; font-weight: 700; }
    .report-header p { margin: 1px 0; font-size: 10px; }
    .title { text-align: center; margin: 2px 0 4px; }
    .title h3 { margin: 0; font-size: 15px; font-weight: 700; text-transform: uppercase; }
    .title p { margin: 2px 0 0; font-weight: 700; }
    .small { font-size: 9px; color: #4b5563; }
</style>

<div class="container-fluid d-print-none no-print mb-2 text-end">
    <button type="button" class="btn btn-warning btn-sm" onclick="window.print()">
        <i class="fas fa-print"></i> Print
    </button>
</div>

<div class="row gutters-20 mb-2 marksheet transcript-page">
    <div class="card height-auto col-12 mx-auto">
        <div class="card-body row transcript">
            @include('result.partials.transcript-header', ['header' => [
                'title' => $transcriptView['title'],
                'examName' => $transcriptView['examName'],
                'institute' => $transcriptView['institute'],
            ]])
            @include('result.partials.transcript-information', [
                'identity' => [
                    'studentId' => $transcriptView['studentId'],
                    'studentName' => $transcriptView['studentName'],
                    'fatherName' => $transcriptView['fatherName'],
                    'motherName' => $transcriptView['motherName'],
                    'rollNumber' => $transcriptView['rollNumber'],
                ],
                'metadata' => [
                    'sessionName' => $transcriptView['sessionName'],
                    'className' => $transcriptView['className'],
                    'sectionName' => $transcriptView['sectionName'],
                    'departmentName' => $transcriptView['departmentName'],
                ],
                'meritRank' => $transcriptView['meritRank'] ?? '-',
                'gradeLegend' => $transcriptView['gradeLegend'],
            ])

            @if(!empty($hideForMaxRule))
                <div class="alert alert-warning col-12 d-print-none">
                    Notice: this student has marks in {{ $studentMarkedSubjects }} subject(s), while class maximum is {{ $maxMarkedSubjects }} for this exam.
                    Transcript is shown with available marks.
                </div>
            @endif

            @if(isset($transcriptResult['curriculumStatus']) && empty($transcriptResult['curriculumStatus']['configured']))
                <div class="alert alert-danger col-12 d-print-none">
                    Curriculum is not configured for this session/class/section/department scope. Main subject list cannot be derived.
                </div>
            @endif

            <h3 class="mt-2 mb-1 fw-bold">Main Subject</h3>
            <table class="table table-bordered col-12 text-center">
                <thead>
                    <tr><th>Subject Name</th><th>Theory</th><th>MCQ</th><th>Practical</th><th>Total</th><th>Grade</th><th>Point</th></tr>
                </thead>
                <tbody>
                    @forelse($transcriptResult['mainRows'] as $row)
                        <tr data-subject-id="{{ $row['id'] }}" data-status="{{ $row['status'] }}">
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['cq'] }}</td>
                            <td>{{ $row['mcq'] }}</td>
                            <td>{{ $row['practical'] }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td>{{ $row['grade'] }}</td>
                            <td>{{ $row['gradePoint'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No main subjects</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h3 class="mt-2 mb-1 fw-bold">Optional Subject</h3>
            <table class="table table-bordered col-12 text-center">
                <thead>
                    <tr><th>Subject Name</th><th>Theory</th><th>M.C.Q</th><th>Practical</th><th>Total</th><th>Grade</th><th>Point</th></tr>
                </thead>
                <tbody>
                    @forelse($transcriptResult['optionalRows'] as $row)
                        <tr data-subject-id="{{ $row['id'] }}" data-status="{{ $row['status'] }}">
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['cq'] }}</td>
                            <td>{{ $row['mcq'] }}</td>
                            <td>{{ $row['practical'] }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td>{{ $row['grade'] }}</td>
                            <td>{{ $row['gradePoint'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No selected 4th subject data found</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if($transcriptResult['classification'] === 'Incomplete')
                <div class="alert alert-warning col-12">
                    Incomplete: missing marks for {{ implode(', ', $transcriptResult['missingSubjects']) }}.
                </div>
            @elseif($transcriptResult['classification'] === 'Absent')
                <div class="alert alert-warning col-12">
                    No marks were entered for this student in this exam.
                </div>
            @endif

            <table class="col-12 mb-4 table table-bordered">
                <thead>
                    <tr>
                        <th width="20%">Total Marks: {{ $transcriptResult['totalMarks'] }}</th>
                        <th width="20%">Letter Grade: {{ $transcriptResult['letterGrade'] }}</th>
                        <th width="20%">Grade Point: {{ $transcriptResult['gpaDisplay'] }}</th>
                        <th>Remark- {{ $transcriptResult['classification'] === 'Absent' ? 'Absent' : $transcriptResult['status'] }}</th>
                    </tr>
                </thead>
            </table>

            <div class="transcript-footer col-12">
                @include('result.partials.failed-subjects', [
                    'result' => $transcriptResult,
                    'containerClass' => 'mb-1',
                    'headingClass' => 'fw-bold text-danger',
                ])

            @include('result.partials.transcript-signatures', [
                'principalSignatureUrl' => $transcriptView['principalSignatureUrl'],
            ])
            </div>
        </div>
    </div>
</div>
@endsection

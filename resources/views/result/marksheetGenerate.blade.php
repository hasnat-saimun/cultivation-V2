@extends('result.include')

@section('backTitle')
Marksheet Generate
@endsection

@section('backIndex')
<style>
    @page { size: A4 portrait; margin: 8mm; }
    html, body { background: #fff; }
    @media print {
        html, body { background: #fff !important; }
        #wrapper, .wrapper, .dashboard-page-one, .dashboard-content-one { background: #fff !important; }
        .d-print-none { display: none !important; }
        .breadcrumbs-area, .header-menu-one, .navbar { display: none !important; }
        .marksheet .card { box-shadow: none !important; border: none !important; }
        .marksheet .transcript { border: none !important; }
        .transcript-page { page-break-inside: avoid !important; break-inside: avoid !important; }
        .signature-row { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 12px !important; width: 100% !important; }
        .marksheet table.table, .marksheet table.table-bordered { border-collapse: collapse !important; }
        .marksheet table.table thead th, .marksheet table.table-bordered thead th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .marksheet table.table th, .marksheet table.table td, .marksheet table.table-bordered th, .marksheet table.table-bordered td { border: 1px solid #000 !important; }
        .report-header { margin-bottom: 4px !important; padding-bottom: 3px !important; border-bottom: 1px solid #cbd5e1 !important; }
        .report-header .hdr-logo { height: 42px !important; width: 42px !important; }
        .report-header .name { font-size: 17px !important; margin: 2px 0 !important; }
        .report-header .subline, .report-header .contacts { font-size: 10px !important; margin-top: 1px !important; }
        .transcript-information-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) !important;
            gap: 10px !important;
            align-items: start !important;
        }
        .student-information,
        .grading-information {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }
        .grading-information table {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
            width: 100% !important;
        }
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
    .signature-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 9px; width: 100%; }
    .signature-box { display: flex; flex-direction: column; justify-content: flex-end; align-items: center; min-height: 58px; page-break-inside: avoid; }
    .signature-space { height: 30px; }
    .signature-line { width: 80%; border-bottom: 1px solid #2d3748; }
    .signature-role { font-weight: 600; margin-bottom: 6px; }
    .signature-label { margin-top: 6px; font-size: 11px; color: #4a5568; }
    .sign-image { height: 44px; width: auto; max-width: 140px; object-fit: contain; margin-bottom: 8px; }
    .student-info th { width: 140px; white-space: nowrap; }
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
    .report-header { display: block; width: 100%; text-align: center; margin: 0 auto 8px; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb; }
    .report-header .hdr-logo { height: 60px; width: 60px; object-fit: contain; display: inline-block; margin: 0; }
    .report-header .logo-wrap { width: 100%; display: block; text-align: center; margin: 0 auto 6px; }
    .report-header .name { font-weight: 700; margin-bottom: 0; }
    .report-header .subline { font-size: 12px; color: #6b7280; }
    .report-header .contacts { font-size: 12px; }
    .transcript-information-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr);
        gap: 24px;
        align-items: start;
    }
    .student-information,
    .grading-information {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .student-information .student-info,
    .grading-information .grading-table {
        width: 100%;
        margin-bottom: 0;
    }
    .grading-information .grading-table th,
    .grading-information .grading-table td {
        white-space: nowrap;
    }
    @media (max-width: 992px) {
        .transcript-information-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }
</style>

<div class="report-header">
    @if($transcriptView['institute']['logoUrl'])
        <div class="logo-wrap">
            <img class="hdr-logo" src="{{ $transcriptView['institute']['logoUrl'] }}" alt="Institute Logo">
        </div>
    @endif
    <h4 class="name my-2">{{ $transcriptView['institute']['name'] }}</h4>
    <div class="subline mt-4">{{ $transcriptView['institute']['address'] }}</div>
    <div class="contacts">
        @if($transcriptView['institute']['mobile'])
            <span><i class="fa fa-phone"></i> {{ $transcriptView['institute']['mobile'] }}</span>
        @endif
        @if($transcriptView['institute']['email'])
            <span style="margin-left:12px;"><i class="fa fa-envelope-o"></i> {{ $transcriptView['institute']['email'] }}</span>
        @endif
    </div>
</div>

<div class="container-fluid d-print-none mb-2 text-end">
    <button type="button" class="btn btn-warning btn-sm" onclick="window.print()">
        <i class="fas fa-print"></i> Print
    </button>
</div>

<div class="row gutters-20 mb-2 marksheet transcript-page">
    <div class="card height-auto col-12 mx-auto">
        <div class="card-body row transcript">
            <div class="col-12 mb-3">
                <div class="text-center">
                    <h3 class="mb-0 text-uppercase fw-bold">{{ $transcriptView['title'] }}</h3>
                    <p class="fw-bold mb-1">{{ $transcriptView['examName'] }}</p>
                    @if((int) $maxMarkedSubjects > 0 && empty($hideForMaxRule))
                        <div class="mt-2 d-print-none">
                            <span class="badge bg-info text-white">Counted subjects: {{ $studentMarkedSubjects }} / {{ $maxMarkedSubjects }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-12 mb-2 transcript-information-grid">
                <div class="student-information">
                    <table class="student-info">
                        <tbody>
                            <tr><th>Student ID</th><td>:</td><td colspan="4">{{ $transcriptView['studentId'] ?: '-' }}</td></tr>
                            <tr><th>Name</th><td>:</td><td colspan="4">{{ $transcriptView['studentName'] }}</td></tr>
                            <tr><th>Father Name</th><td>:</td><td colspan="4">{{ $transcriptView['fatherName'] }}</td></tr>
                            <tr><th>Mother Name</th><td>:</td><td colspan="4">{{ $transcriptView['motherName'] }}</td></tr>
                            <tr>
                                <th>Roll Number</th><td>:</td><td>{{ $transcriptView['rollNumber'] }}</td>
                                <th>Session</th><td>:</td><td>{{ $transcriptView['sessionName'] }}</td>
                            </tr>
                            <tr>
                                <th>Class</th><td>:</td><td>{{ $transcriptView['className'] }}</td>
                                <th>Section</th><td>:</td><td>{{ $transcriptView['sectionName'] }}</td>
                            </tr>
                            <tr><th>Department</th><td>:</td><td colspan="4">{{ $transcriptView['departmentName'] }}</td></tr>
                            <tr><th>Merit Position</th><td>:</td><td colspan="4">{{ $transcriptView['meritRank'] ?? '-' }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="grading-information">
                    @include('result.partials.grading-table', ['gradeLegend' => $transcriptView['gradeLegend']])
                </div>
            </div>

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
                    @if($transcriptView['principalSignatureUrl'])
                        <img src="{{ $transcriptView['principalSignatureUrl'] }}" alt="Principal Signature" class="sign-image">
                    @endif
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature</div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

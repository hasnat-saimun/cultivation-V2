@extends('result.include')

@section('backTitle')
Marksheet Generate
@endsection

@section('backIndex')
<style>
    @page { size: A4 portrait; margin: 12mm; }
    html, body { background: #fff; }
    @media print {
        html, body { background: #fff !important; }
        #wrapper, .wrapper, .dashboard-page-one, .dashboard-content-one { background: #fff !important; }
        .d-print-none { display: none !important; }
        .breadcrumbs-area, .header-menu-one, .navbar { display: none !important; }
        .marksheet .card { box-shadow: none !important; border: none !important; }
        .marksheet .transcript { border: none !important; }
        .signature-row { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 16px !important; width: 100% !important; }
        .marksheet table.table, .marksheet table.table-bordered { border-collapse: collapse !important; }
        .marksheet table.table thead th, .marksheet table.table-bordered thead th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .marksheet table.table th, .marksheet table.table td, .marksheet table.table-bordered th, .marksheet table.table-bordered td { border: 1px solid #000 !important; }
        .report-header { gap: 6px !important; margin-bottom: 6px !important; padding-bottom: 4px !important; border-bottom: 1px solid #cbd5e1 !important; }
        .report-header .hdr-logo { height: 48px !important; }
        .report-header .name { font-size: 18px !important; }
        .report-header .subline, .report-header .contacts { font-size: 11px !important; }
        .failed-subjects { font-size: 11px !important; }
        .failed-subjects h4 { margin-bottom: 4px !important; }
        .failed-subjects ul { columns: 2 !important; column-gap: 12px !important; list-style-position: inside !important; margin: 4px 0 !important; padding-left: 0 !important; }
        .failed-subjects li { break-inside: avoid !important; margin: 0 0 3px !important; padding: 0 !important; }
    }
    .marksheet .transcript { background: #fff; padding: 16px; border: 1px solid #e5e7eb; }
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
    .student-info th { width: 140px; white-space: nowrap; }
    .student-info td { word-break: break-word; overflow-wrap: anywhere; }
    .failed-subjects h4 { margin-bottom: 6px; }
    .failed-subjects ul { margin: 6px 0; }
    .report-header { display: block; width: 100%; text-align: center; margin: 0 auto 8px; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb; }
    .report-header .hdr-logo { height: 60px; width: 60px; object-fit: contain; display: inline-block; margin: 0; }
    .report-header .logo-wrap { width: 100%; display: block; text-align: center; margin: 0 auto 6px; }
    .report-header .name { font-weight: 700; margin-bottom: 0; }
    .report-header .subline { font-size: 12px; color: #6b7280; }
    .report-header .contacts { font-size: 12px; }
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

<div class="row gutters-20 mb-4 marksheet">
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

            <table class="col-8 col-md-8 mb-4 student-info">
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

            <table class="col-4 col-md-4 mb-4 table-bordered text-center">
                <thead>
                    <tr><th>Range of Marks</th><th>Grade</th><th>Point</th></tr>
                </thead>
                <tbody>
                    @forelse($transcriptView['gradeLegend'] as $grade)
                        <tr><td>{{ $grade['range'] }}</td><td>{{ $grade['grade'] }}</td><td>{{ $grade['point'] }}</td></tr>
                    @empty
                        <tr><td colspan="3">No grading legend configured</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if(!empty($hideForMaxRule))
                <div class="alert alert-warning col-12 d-print-none">
                    Notice: this student has marks in {{ $studentMarkedSubjects }} subject(s), while class maximum is {{ $maxMarkedSubjects }} for this exam.
                    Transcript is shown with available marks.
                </div>
            @endif

            <h3 class="mt-4 mb-2 fw-bold">Main Subject</h3>
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

            <h3 class="mt-4 mb-2 fw-bold">Optional Subject</h3>
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

            @if($transcriptResult['missingSubjectCount'] > 0)
                <div class="alert alert-warning col-12">
                    Incomplete: missing marks for {{ implode(', ', $transcriptResult['missingSubjects']) }}.
                </div>
            @endif

            <table class="col-12 mb-4 table table-bordered">
                <thead>
                    <tr>
                        <th width="20%">Total Marks: {{ $transcriptResult['totalMarks'] }}</th>
                        <th width="20%">Letter Grade: {{ $transcriptResult['letterGrade'] }}</th>
                        <th width="20%">Grade Point: {{ $transcriptResult['gpaDisplay'] }}</th>
                        <th>Remark- {{ $transcriptResult['status'] }}</th>
                    </tr>
                </thead>
            </table>

            @if($transcriptResult['failedSubjectCount'] > 0)
                <div class="col-12 mb-3 failed-subjects">
                    <h4 class="fw-bold text-danger">Failed Subjects ({{ $transcriptResult['failedSubjectCount'] }})</h4>
                    <ul class="mb-0">
                        @foreach($transcriptResult['failedSubjects'] as $failedSubject)
                            <li>{{ $failedSubject }}</li>
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
@endsection

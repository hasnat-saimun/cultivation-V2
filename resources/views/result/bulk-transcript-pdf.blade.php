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
        .report-header { text-align: center; margin-bottom: 8px; padding-bottom: 6px; }
        .hdr-logo { display: inline-block; max-height: 70px; max-width: 90px; margin-bottom: 5px; }
        .report-header h2 { margin: 0; font-size: 20px; }
        .report-header p { margin: 2px 0; }
        .title { text-align: center; margin: 4px 0 8px 0; }
        .title h3 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .title p { margin: 4px 0 0 0; font-weight: 700; }
        .meta-wrap { width: 100%; margin-bottom: 8px; }
        .meta-wrap td { border: 0 !important; vertical-align: top; padding: 0; }
        .meta-left { width: 62%; padding-right: 10px !important; }
        .meta-right { width: 38%; }
        .student-info th { width: auto; text-align: left; white-space: nowrap; border: 0 !important; }
        .student-info td { word-break: break-word; border: 0 !important; }
        .grade-scale th, .grade-scale td { text-align: center; font-size: 10px; }
        .section-title { margin: 8px 0 4px 0; font-size: 14px; font-weight: 700; }
        .failed-subjects h4 { margin: 6px 0 2px 0; color: #b91c1c; }
        .failed-subject-grid { width: 100%; margin: 4px 0 0; table-layout: fixed; border-collapse: collapse; page-break-inside: avoid; }
        .marksheet .failed-subject-grid td { width: 33.333%; border: 0; padding: 2px 10px 2px 0; vertical-align: top; overflow-wrap: anywhere; word-break: normal; }
        .failed-subject-empty { visibility: hidden; }
        .signature-row { width: 100%; margin-top: 14px; }
        .signature-row td { width: 33.33%; border: 0 !important; text-align: center; vertical-align: bottom; height: 72px; }
        .signature-line { border-top: 1px solid #111827; width: 75%; margin: 0 auto; }
        .small { font-size: 11px; color: #4b5563; }
        .sign-image { height: 40px; width: auto; max-width: 130px; object-fit: contain; margin: 0 auto 6px; display: block; }
        @media print { .print-button { display: none; } }
    </style>
</head>
<body>
@foreach($transcripts as $transcript)
    <div class="transcript-page marksheet">
        <div class="transcript">
            <div class="report-header">
                @if(!empty($bulkView['institute']['logoUrl']))
                    <img src="{{ $bulkView['institute']['logoUrl'] }}" alt="Institute Logo" class="hdr-logo">
                @endif
                <h2>{{ $bulkView['institute']['name'] }}</h2>
                @if(!empty($bulkView['institute']['address']))<p>{{ $bulkView['institute']['address'] }}</p>@endif
                @if(!empty($bulkView['institute']['mobile']) || !empty($bulkView['institute']['email']))
                    <p>{{ $bulkView['institute']['mobile'] }}{{ !empty($bulkView['institute']['mobile']) && !empty($bulkView['institute']['email']) ? ' | ' : '' }}{{ $bulkView['institute']['email'] }}</p>
                @endif
            </div>

            <div class="title">
                <h3>{{ $bulkView['title'] }}</h3>
                <p>{{ $bulkView['examName'] }}</p>
            </div>

            <table class="meta-wrap">
                <tr>
                    <td class="meta-left">
                        <table class="student-info"><tbody>
                            <tr><th>Student ID</th><td>:</td><td colspan="4">{{ $transcript['studentIdentity']['studentId'] ?: '-' }}</td></tr>
                            <tr><th>Name</th><td>:</td><td colspan="4">{{ $transcript['studentIdentity']['studentName'] }}</td></tr>
                            <tr><th>Father Name</th><td>:</td><td colspan="4">{{ $transcript['studentIdentity']['fatherName'] }}</td></tr>
                            <tr><th>Mother Name</th><td>:</td><td colspan="4">{{ $transcript['studentIdentity']['motherName'] }}</td></tr>
                            <tr><th>Roll Number</th><td>:</td><td>{{ $transcript['studentIdentity']['rollNumber'] }}</td><th>Session</th><td>:</td><td>{{ $transcript['metadata']['sessionName'] }}</td></tr>
                            <tr><th>Class</th><td>:</td><td>{{ $transcript['metadata']['className'] }}</td><th>Section</th><td>:</td><td>{{ $transcript['metadata']['sectionName'] }}</td></tr>
                            <tr><th>Department</th><td>:</td><td colspan="4">{{ $transcript['metadata']['departmentName'] }}</td></tr>
                            <tr><th>Merit Position</th><td>:</td><td colspan="4">{{ $transcript['meritRank'] ?? '01' }}</td></tr>
                        </tbody></table>
                    </td>
                    <td class="meta-right">
                        <table class="grade-scale">
                            <thead><tr><th>Range of Marks</th><th>Grade</th><th>Point</th></tr></thead>
                            <tbody>
                            @foreach($bulkView['gradeLegend'] as $grade)
                                <tr><td>{{ $grade['range'] }}</td><td>{{ $grade['grade'] }}</td><td>{{ $grade['point'] }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="section-title">Main Subject</div>
            <table>
                <thead><tr><th>Subject Name</th><th>Theory</th><th>MCQ</th><th>Practical</th><th>Total</th><th>Grade</th><th>Point</th></tr></thead>
                <tbody>
                @forelse($transcript['result']['mainRows'] as $row)
                    <tr><td>{{ $row['name'] }}</td><td>{{ $row['cq'] }}</td><td>{{ $row['mcq'] }}</td><td>{{ $row['practical'] }}</td><td>{{ $row['total'] }}</td><td>{{ $row['grade'] }}</td><td>{{ $row['gradePoint'] }}</td></tr>
                @empty
                    <tr><td colspan="7">No main subjects</td></tr>
                @endforelse
                </tbody>
            </table>

            <div class="section-title">Optional Subject</div>
            <table>
                <thead><tr><th>Subject Name</th><th>Theory</th><th>MCQ</th><th>Practical</th><th>Total</th><th>Grade</th><th>Point</th></tr></thead>
                <tbody>
                @forelse($transcript['result']['optionalRows'] as $row)
                    <tr><td>{{ $row['name'] }} (4th)</td><td>{{ $row['cq'] }}</td><td>{{ $row['mcq'] }}</td><td>{{ $row['practical'] }}</td><td>{{ $row['total'] }}</td><td>{{ $row['grade'] }}</td><td>{{ $row['gradePoint'] }}</td></tr>
                @empty
                    <tr><td colspan="7">No selected 4th subject data found</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($transcript['result']['missingSubjectCount'] > 0)
                <div class="small">Incomplete: missing marks for {{ implode(', ', $transcript['result']['missingSubjects']) }}.</div>
            @endif

            <table style="margin-top:10px;">
                <thead><tr>
                    <th width="20%">Total Marks: {{ $transcript['result']['totalMarks'] }}</th>
                    <th width="20%">Letter Grade: {{ $transcript['result']['letterGrade'] }}</th>
                    <th width="20%">Grade Point: {{ $transcript['result']['gpaDisplay'] }}</th>
                    <th>Remark- {{ $transcript['result']['status'] }}</th>
                </tr></thead>
            </table>

            @include('result.partials.failed-subjects', ['result' => $transcript['result']])

            <table class="signature-row"><tr>
                <td><div>Guardian</div><div class="signature-line"></div><div class="small">Signature</div></td>
                <td><div>Class Teacher</div><div class="signature-line"></div><div class="small">Signature</div></td>
                <td>
                    <div>Principal/Head Master</div>
                    @if(!empty($bulkView['principalSignatureUrl']))<img src="{{ $bulkView['principalSignatureUrl'] }}" alt="Principal Signature" class="sign-image">@endif
                    <div class="signature-line"></div><div class="small">Signature</div>
                </td>
            </tr></table>
        </div>
    </div>
@endforeach
</body>
</html>

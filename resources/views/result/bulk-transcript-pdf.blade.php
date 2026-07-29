<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulk Academic Transcript</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; margin: 0; }
        .transcript-page { page-break-after: always; break-after: page; page-break-inside: avoid; break-inside: avoid; }
        .transcript-page:last-child { page-break-after: auto; }
        .marksheet .transcript { background: #fff; padding: 7px 9px; border: 1px solid #334155; }
        .marksheet table { width: 100%; border-collapse: collapse; }
        .marksheet table th, .marksheet table td { border: 1px solid #64748b; padding: 3px 4px; line-height: 1.2; }
        .marksheet table thead th { background: #f3f4f6; font-weight: 700; }
        .report-header { text-align: center; margin-bottom: 3px; padding-bottom: 3px; border-bottom: 1px solid #cbd5e1; }
        .hdr-logo { display: inline-block; max-height: 42px; max-width: 64px; margin-bottom: 2px; }
        .report-header h2 { margin: 0; font-size: 17px; }
        .report-header p { margin: 1px 0; }
        .title { text-align: center; margin: 2px 0 4px; }
        .title h3 { margin: 0; font-size: 15px; text-transform: uppercase; }
        .title p { margin: 2px 0 0; font-weight: 700; }
        .meta-wrap { width: 100%; margin-bottom: 4px; }
        .meta-wrap td { border: 0 !important; vertical-align: top; padding: 0; }
        .meta-left { width: 62%; padding-right: 7px !important; }
        .meta-right { width: 38%; }
        .student-info th { width: auto; text-align: left; white-space: nowrap; border: 0 !important; }
        .student-info td { word-break: break-word; border: 0 !important; }
        .section-title { margin: 4px 0 2px; font-size: 12px; font-weight: 700; }
        .grading-table-wrap { border: 1px solid #cbd5e1; overflow: hidden; background: #fff; page-break-inside: avoid; break-inside: avoid; }
        .grading-table-title { padding: 3px 5px; color: #fff; background: #334155; text-align: center; font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .grading-table { width: 100%; border-collapse: collapse; margin: 0; font-size: 9px; text-align: center; }
        .marksheet .grading-table th, .marksheet .grading-table td { padding: 2px 3px; border: 0; border-bottom: 1px solid #e2e8f0; }
        .grading-table th { color: #334155; background: #f1f5f9; font-weight: 700; }
        .grading-table tbody tr:nth-child(even) { background: #f8fafc; }
        .grading-table tbody tr:last-child td { border-bottom: 0; }
        .grading-letter, .grading-point { font-weight: 700; }
        .failed-subjects { margin: 4px 0 0; padding: 4px 6px; border-left: 3px solid #b91c1c; background: #fff7f7; page-break-inside: avoid; break-inside: avoid; }
        .failed-subjects h4 { margin: 0 0 2px; color: #b91c1c; font-size: 11px; line-height: 1.2; }
        .failed-subject-grid { width: 100%; margin: 0; table-layout: fixed; border-collapse: collapse; page-break-inside: avoid; }
        .marksheet .failed-subject-grid td { width: 33.333%; border: 0; padding: 1px 8px 1px 0; vertical-align: top; line-height: 1.2; overflow-wrap: anywhere; word-break: normal; }
        .failed-subject-empty { visibility: hidden; }
        .transcript-footer { page-break-inside: avoid; break-inside: avoid; }
        .signature-row { width: 100%; margin-top: 7px; page-break-inside: avoid; break-inside: avoid; }
        .signature-row td { width: 33.33%; border: 0 !important; text-align: center; vertical-align: bottom; height: 48px; }
        .signature-line { border-top: 1px solid #111827; width: 75%; margin: 0 auto; }
        .small { font-size: 9px; color: #4b5563; }
        .sign-image { height: 30px; width: auto; max-width: 110px; object-fit: contain; margin: 0 auto 3px; display: block; }
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
                        @include('result.partials.grading-table', ['gradeLegend' => $bulkView['gradeLegend']])
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

            <div class="transcript-footer">
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
    </div>
@endforeach
</body>
</html>

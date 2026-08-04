<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $labels['subject'] }} - Subject Marksheet</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 297mm; margin: 0; padding: 0; color: #173238; background: #fff; font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; line-height: 1.25; }
        .report { width: 297mm; margin: 0; padding: 0; }
        .actions { margin: 0 auto 8px; max-width: 1120px; text-align: right; }
        .btn { display: inline-block; margin-left: 6px; padding: 8px 13px; border: 1px solid #176b6b; border-radius: 5px; color: #176b6b; background: #fff; text-decoration: none; font-weight: 700; cursor: pointer; }
        .btn-primary { color: #fff; background: #176b6b; }
        .report-page { width: 297mm; margin: 0; padding: 9mm; overflow: hidden; }
        .page-sheet { position: relative; width: 279mm; min-height: 191mm; margin: 0; padding: 0; }
        .header-table, .meta-table, .marks-table, .signature-table, .footer-table { width: 100%; border-collapse: collapse; }
        .header-table td { height: 18mm; border: 0; padding: 1.2mm 2mm 2mm; vertical-align: middle; }
        .logo-cell { width: 20mm; text-align: left; }
        .logo { display: block; width: auto; max-width: 17mm; height: auto; max-height: 15mm; margin: 0 auto 0 0; }
        .header-copy { text-align: center; vertical-align: middle; }
        .header-spacer { width: 20mm; }
        .institute { margin: 0; color: #123f46; font-size: 16pt; line-height: 1.12; }
        .title { margin: 1.2mm 0 0; color: #176b6b; font-size: 10.5pt; line-height: 1.15; text-transform: uppercase; letter-spacing: .4pt; }
        .meta-table { margin: 1.5mm 0 2.5mm; border: .3mm solid #b9cfd2; background: #f5f9f9; }
        .meta-table td { width: 25%; padding: 2mm 2.5mm; border: 0; text-align: left; line-height: 1.25; white-space: normal; vertical-align: middle; }
        .meta-table b { color: #234b51; }
        .marks-table { table-layout: fixed; }
        .marks-table thead { display: table-header-group; }
        .marks-table tr { break-inside: avoid; page-break-inside: avoid; }
        .marks-table th, .marks-table td { border: .25mm solid #78999e; padding: 1.15mm 1.8mm; line-height: 1.18; vertical-align: middle; }
        .marks-table th { padding-top: 1.4mm; padding-bottom: 1.4mm; color: #fff; background: #176b6b; font-size: 8.3pt; text-transform: uppercase; letter-spacing: .1pt; }
        .marks-table td { text-align: center; }
        .marks-table td.name { text-align: left; overflow-wrap: break-word; }
        .col-sl { width: 4%; } .col-roll { width: 6%; } .col-id { width: 12%; } .col-name { width: 33%; }
        .col-mark { width: 9%; } .col-total { width: 10%; }
        .page-bottom { position: absolute; right: 0; bottom: 0; left: 0; }
        .signature-table { margin-top: 4mm; page-break-inside: avoid; }
        .signature-table td { width: 50%; padding: 0 12mm; border: 0; text-align: center; vertical-align: top; }
        .signature-line { display: inline-block; width: 54mm; padding-top: 1.2mm; border-top: .25mm solid #526f74; line-height: 1.15; }
        .footer-table { margin-top: 3mm; border-top: .25mm solid #d4e0e2; color: #5f7478; font-size: 8pt; line-height: 1.15; }
        .footer-table td { border: 0; padding: 1.5mm .5mm 0; vertical-align: bottom; }
        .footer-page { text-align: right; }
        @media print {
            .no-print { display: none !important; }
            html, body, .report { width: 297mm; margin: 0; padding: 0; background: #fff; }
        }
    </style>
</head>
<body>
@php
    $legacyComponents = $subject->CQ === null && $subject->MCQ === null && $subject->Practical === null;
    $showCq = $legacyComponents || (float) ($subject->CQ ?? 0) > 0;
    $showMcq = !$legacyComponents && (float) ($subject->MCQ ?? 0) > 0;
    $showPractical = !$legacyComponents && (float) ($subject->Practical ?? 0) > 0;
@endphp
@unless($pdfMode)
    <div class="actions no-print">
        <button class="btn btn-primary" type="button" onclick="window.print()">Print</button>
        <a class="btn" href="{{ route('teacher.results.subject-marksheet.pdf', request()->query()) }}">Download PDF</a>
    </div>
@endunless

<main class="report">
@forelse($reportPages as $pageRows)
    <section class="report-page">
      <div class="page-sheet">
        <table class="header-table" role="presentation">
            <tr>
                <td class="logo-cell">@if(filled($instituteLogoUrl))<img class="logo" src="{{ $instituteLogoUrl }}" alt="">@endif</td>
                <td class="header-copy"><h1 class="institute">{{ $instituteName }}</h1><h2 class="title">Subject Marksheet</h2></td>
                <td class="header-spacer"></td>
            </tr>
        </table>
        <table class="meta-table" role="presentation">
            <tr><td><b>Exam:</b> {{ $labels['exam'] }}</td><td><b>Session:</b> {{ $labels['session'] }}</td><td><b>Class:</b> {{ $labels['class'] }}</td><td><b>Section:</b> {{ $labels['section'] }}</td></tr>
            <tr><td><b>Department:</b> {{ $labels['department'] }}</td><td><b>Subject:</b> {{ $labels['subject'] }}@if(filled($subjectCode)) ({{ $subjectCode }})@endif</td><td><b>Teacher:</b> {{ $teacher->adminName ?: $teacher->adminUser }}</td><td><b>Status:</b> {{ $status }}</td></tr>
        </table>
        <table class="marks-table" aria-label="{{ $labels['subject'] }} subject marksheet">
            <thead><tr>
                <th class="col-sl">SL</th><th class="col-roll">Roll</th><th class="col-id">Student ID</th><th class="col-name">Student Name</th>
                @if($showCq)<th class="col-mark">CQ</th>@endif
                @if($showMcq)<th class="col-mark">MCQ</th>@endif
                @if($showPractical)<th class="col-mark">Practical</th>@endif
                <th class="col-total">Total</th>
            </tr></thead>
            <tbody>
            @foreach($pageRows as $row)
                <tr>
                    <td>{{ $row['sl'] }}</td>
                    <td>{{ filled($row['roll']) ? $row['roll'] : '—' }}</td>
                    <td>{{ filled($row['student_id']) ? $row['student_id'] : '—' }}</td>
                    <td class="name">{{ $row['name'] }}</td>
                    @if($showCq)<td>{{ $row['cq'] === null || $row['cq'] === '' ? '—' : $row['cq'] }}</td>@endif
                    @if($showMcq)<td>{{ $row['mcq'] === null || $row['mcq'] === '' ? '—' : $row['mcq'] }}</td>@endif
                    @if($showPractical)<td>{{ $row['practical'] === null || $row['practical'] === '' ? '—' : $row['practical'] }}</td>@endif
                    <td>{{ $row['total'] === null ? '—' : $row['total'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
       <div class="page-bottom">
        <table class="signature-table" role="presentation"><tr><td><span class="signature-line">Teacher Signature</span></td><td><span class="signature-line">Head Teacher Signature</span></td></tr></table>
        <table class="footer-table" role="presentation"><tr><td>Printed: {{ $printedAt->format('d M Y, h:i A') }}</td><td class="footer-page">Page {{ $loop->iteration }} of {{ $reportPages->count() }}</td></tr></table>
       </div>
      </div>
    </section>
@empty
    <p>No students are available for this assigned scope.</p>
@endforelse
</main>
</body>
</html>

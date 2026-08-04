<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $card['name'] }} ID Card</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #172033; font-family: DejaVu Sans, Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .actions { max-width: 190mm; margin: 8mm auto 4mm; text-align: right; }
        .actions a,.actions button { display:inline-block; margin-left:5px; padding:8px 13px; border:1px solid #155eaa; border-radius:5px; background:#fff; color:#155eaa; text-decoration:none; cursor:pointer; font-weight:700; }
        .actions .primary { background:#155eaa; color:#fff; }
        .print-sheet { width: 189mm; margin: 0 auto; padding: 16mm 0; background:#fff; text-align:center; }
        .id-card-set { display:block; width:85.60mm; margin:0 auto; text-align:left; }
        .id-card { width:85.60mm; height:53.98mm; border:.35mm solid #a7b3c4; border-radius:3mm; overflow:hidden; background:#fff; page-break-inside:avoid; }
        .portrait .id-card { width:53.98mm; height:85.60mm; }
        .portrait .id-card-set { width:53.98mm; }
        .card-gap { height:7mm; }
        .header { width:100%; height:13mm; border-collapse:collapse; background:#155eaa; color:#fff; }
        .header td { padding:1.5mm 2mm; vertical-align:middle; }
        .logo-cell { width:12mm; }
        .logo { display:block; max-width:9mm; max-height:9mm; margin:auto; }
        .school { font-size:10pt; font-weight:800; line-height:1.1; }
        .card-title { font-size:6.8pt; text-transform:uppercase; letter-spacing:.4pt; opacity:.9; }
        .front-body { width:100%; height:33.5mm; border-collapse:collapse; }
        .front-body td { padding:2mm; vertical-align:middle; }
        .photo-cell { width:23mm; text-align:center; }
        .photo { width:18mm; height:22mm; border:.3mm solid #a7b3c4; border-radius:1.5mm; object-fit:cover; }
        .student-name { max-width:52mm; margin-bottom:1.2mm; font-size:9pt; font-weight:800; white-space:normal; overflow-wrap:anywhere; }
        .detail { width:100%; border-collapse:collapse; font-size:6.8pt; }
        .detail td { padding:.45mm 1mm .45mm 0; border-bottom:.15mm solid #e0e5ec; }
        .detail .label { width:16mm; color:#586579; }
        .footer { height:7.5mm; padding:1.4mm 2mm; background:#172033; color:#fff; font-size:6.5pt; }
        .back-body { height:33.5mm; padding:2.5mm; font-size:7pt; }
        .back-table { width:100%; border-collapse:collapse; }
        .back-table td { padding:1mm; vertical-align:top; }
        .back-table .label { width:21mm; color:#586579; }
        .signature { margin-top:4mm; text-align:right; }
        .signature span { display:inline-block; width:30mm; border-top:.25mm solid #68758a; padding-top:1mm; text-align:center; }
        .portrait .front-body { height:65mm; }
        .portrait .front-body,.portrait .front-body tbody,.portrait .front-body tr,.portrait .front-body td { display:block; width:100%; height:auto; text-align:center; }
        .portrait .photo-cell { padding-top:3mm; }
        .portrait .student-name { margin:1mm auto; }
        .portrait .detail { width:45mm; margin:auto; text-align:left; }
        .portrait .back-body { height:65mm; }
        @media print {
            body { background:#fff; }
            .no-print { display:none !important; }
            .print-sheet { width:189mm; margin:0; padding:16mm 0; }
        }
    </style>
</head>
<body>
@unless($pdfMode)
<div class="actions no-print">
    <a href="{{ route('studentList') }}">Back</a>
    <a href="{{ route('stdIdCard', ['stdId' => $std->id, 'format' => $format === 'portrait' ? 'landscape' : 'portrait']) }}">{{ $format === 'portrait' ? 'Landscape' : 'Portrait' }}</a>
    <a href="{{ route('stdIdCard.pdf', ['stdId' => $std->id, 'format' => $format]) }}">Download PDF</a>
    <button class="primary" type="button" onclick="window.print()">Print</button>
</div>
@endunless
<main class="print-sheet {{ $format }}" id="printArea">
    <div class="id-card-set">
        <section class="id-card id-front" aria-label="Student ID card front">
            <table class="header" role="presentation"><tr><td class="logo-cell">@if($branding['logoUrl'])<img class="logo" src="{{ $branding['logoUrl'] }}" alt="">@endif</td><td><div class="school">{{ $branding['name'] }}</div><div class="card-title">Student Identity Card</div></td></tr></table>
            <table class="front-body" role="presentation"><tr><td class="photo-cell">@if($card['photoUrl'])<img class="photo" src="{{ $card['photoUrl'] }}" alt="Student photo">@endif</td><td><div class="student-name">{{ $card['name'] }}</div><table class="detail"><tr><td class="label">Student ID</td><td>{{ $card['studentId'] }}</td></tr><tr><td class="label">Roll</td><td>{{ $card['roll'] ?: '-' }}</td></tr><tr><td class="label">Class / Section</td><td>{{ $card['class'] }} / {{ $card['section'] }}</td></tr><tr><td class="label">Session</td><td>{{ $card['sessionText'] }}</td></tr></table></td></tr></table>
            <div class="footer">{{ $branding['address'] ?: 'Official student identity card' }}</div>
        </section>
        <div class="card-gap"></div>
        <section class="id-card id-back" aria-label="Student ID card back">
            <table class="header" role="presentation"><tr><td><div class="school">Guardian &amp; Contact Information</div><div class="card-title">{{ $branding['name'] }}</div></td></tr></table>
            <div class="back-body"><table class="back-table"><tr><td class="label">Guardian</td><td>{{ $card['guardianName'] }}</td></tr><tr><td class="label">Relation</td><td>{{ $card['guardianRelation'] }}</td></tr><tr><td class="label">Guardian Mobile</td><td>{{ $card['guardianPhone'] }}</td></tr><tr><td class="label">Student Contact</td><td>{{ $card['contact'] }}</td></tr><tr><td class="label">Department</td><td>{{ $card['department'] }}</td></tr><tr><td class="label">Valid Until</td><td>{{ $card['validity'] }}</td></tr></table><div class="signature"><span>Authorized Signature</span></div></div>
            <div class="footer">If found, contact {{ $branding['phone'] ?: $card['guardianPhone'] }}</div>
        </section>
    </div>
</main>
</body>
</html>

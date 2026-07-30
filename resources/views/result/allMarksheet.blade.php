@extends('result.singleinclude')
@section('backTitle')
All Marksheet
@endsection
@section('backIndex')
<style>
    @page { size: A4 landscape; margin: 5mm; }
    .result-table{width:100%;border-collapse:collapse;table-layout:fixed}.result-table th,.result-table td{border:1px solid #111;padding:3px 4px;text-align:center;font-size:9.15px;line-height:1.26;overflow-wrap:anywhere}.result-table thead{display:table-header-group}.result-table tr{page-break-inside:avoid}.sl-col{width:28px}.roll-col{width:40px}.id-col{width:84px}.name-col{text-align:left!important;width:210px;min-width:210px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.subject-outcome{white-space:nowrap}.failed-subject-cell{background:#fee2e2!important;border-color:#ef4444!important;color:#991b1b;font-weight:700}
    .result-page-header{margin-bottom:14px}.header-surface,.header-meta-row{background:#fff;border:1px solid #d7dde7;border-radius:10px}.header-surface{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:14px;padding:12px 14px}.header-logo-wrap{width:66px;height:66px;border:1px solid #d7dde7;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#fff;flex-shrink:0}.header-logo-image{max-width:54px;max-height:54px;object-fit:contain}.header-logo-fallback{font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em}.header-identity{min-width:0}.header-kicker{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;margin-bottom:2px}.header-institute-name{margin:0;font-size:24px;line-height:1.1;font-weight:800;color:#111827}.header-contact{margin-top:4px;font-size:12px;line-height:1.35;color:#4b5563}.header-title-block{text-align:right;min-width:220px}.header-report-label{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;margin-bottom:3px}.header-report-title{margin:0;font-size:19px;line-height:1.15;font-weight:700;color:#111827}.header-report-timestamp{margin-top:6px;font-size:11px;color:#374151}.header-meta-row{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:10px 14px;margin-top:10px}.header-meta-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px 14px;flex:1}.header-meta-item{min-width:0}.header-meta-label{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;margin-bottom:2px}.header-meta-value{font-size:13px;line-height:1.25;font-weight:600;color:#111827;word-break:break-word}.header-actions{display:flex;align-items:center;justify-content:flex-end;min-width:fit-content}
    .result-print-page{break-after:page;page-break-after:always;min-height:185mm;position:relative}.result-print-page:last-child{break-after:auto;page-break-after:auto}.page-footer{text-align:right;font-size:9px;margin-top:4px}
    .result-signatures{display:flex;justify-content:space-around;gap:50px;margin-top:20px;break-inside:avoid;page-break-inside:avoid}.result-signature{text-align:center;min-width:180px;padding-top:35px}.result-signature-line{border-top:1px solid #111}.result-signature-image{height:35px;max-width:120px;object-fit:contain}
    @media (max-width: 820px){.header-surface{grid-template-columns:auto 1fr}.header-title-block{grid-column:1 / -1;text-align:left}.header-meta-row{flex-direction:column}.header-meta-grid{grid-template-columns:repeat(2,minmax(0,1fr));width:100%}.header-actions{width:100%;justify-content:flex-start}}
    @media print{*,*::before,*::after{background:transparent!important;background-image:none!important;box-shadow:none!important}html,body,main,.container,.container-fluid,.container-fluid.mb-4,.card,.card-body,.row,.col,.main-website,.main-content,.print-wrapper,.page-wrapper,.report-wrapper,.result-wrapper,.d-print-block,.result-print-page,.result-page-header,.header-surface,.header-meta-row,.table-responsive,.result-table,.result-table th,.result-table td,.d-print-block [class^="bg-"],.d-print-block [class*=" bg-"],.d-print-block [class*="bg-"]{background:#fff!important;background-image:none!important;box-shadow:none!important}.result-page-header{break-inside:avoid;page-break-inside:avoid;margin-bottom:14px}.header-surface{display:grid!important;grid-template-columns:auto 1fr auto!important;align-items:center!important;gap:14px!important;padding:12px 14px!important}.header-meta-row{display:flex!important;align-items:flex-start!important;justify-content:space-between!important;gap:12px!important;padding:10px 14px!important;margin-top:10px!important}.header-meta-grid{display:grid!important;grid-template-columns:repeat(5,minmax(0,1fr))!important;gap:8px 14px!important;flex:1}.header-title-block{text-align:right!important;min-width:220px!important}.header-actions{display:none!important}.header-institute-name{font-size:24px!important}.header-report-title{font-size:19px!important}.header-report-timestamp{display:none!important}.header-contact{font-size:12px!important}.header-meta-value{font-size:13px!important}.result-table th,.result-table td{padding:1.75px 3px;font-size:8.75px;line-height:1.17}.failed-subject-cell{background:#fff!important;background-image:none!important;color:#8b1d1d!important;border:2px solid #8b1d1d!important;font-weight:700}.navbar,.sidebar-main,.breadcrumbs-area,.footer-wrap-layout1,form,.d-print-none{display:none!important}.d-print-block{display:block!important}.container-fluid,.main-content,.dashboard-content-one{margin:0!important;padding:0!important;width:100%!important}}
</style>
<div class="main-website"><div class="main-content"><div class="container-fluid mb-4">
    @include('result.partials.result-report-filter', [
        'filterAction' => route('allMarksheet'),
        'showCompactOption' => true,
    ])
    @if(!$examId || !$classId || !$sessionId)
        <div class="alert alert-info">Please select required filters (Exam, Class & Session) to view results.</div>
    @else
        <div class="d-print-none">
            @include('result.partials.passive-result-header', ['headerVariant' => 'subject-wise'])
            @if($studentsLoaded && empty($tabulationRows))<div class="alert alert-warning">No marks found for the selected filters.</div>@endif
            <h5 class="mt-4 text-success">All Subject Pass ({{ count($reportSections['Pass']) }})</h5>
            <div class="table-responsive mb-3">@include('result.partials.tabulation-table', ['tableRows' => $reportSections['Pass'], 'tableMode' => 'pass'])</div>
            @foreach($failedGroups as $failedCount => $failedRows)
                <h5 class="mt-4 text-danger">Failed in {{ $failedCount }} Subject{{ $failedCount === 1 ? '' : 's' }} ({{ count($failedRows) }})</h5>
                <div class="table-responsive mb-3">@include('result.partials.tabulation-table', ['tableRows' => $failedRows, 'tableMode' => 'fail'])</div>
            @endforeach
            <h5 class="mt-4 text-warning">Incomplete ({{ count($reportSections['Incomplete']) }})</h5>
            <div class="table-responsive mb-3">@include('result.partials.tabulation-table', ['tableRows' => $reportSections['Incomplete'], 'tableMode' => 'incomplete'])</div>
            <h5 class="mt-4 text-danger">Absent ({{ count($reportSections['Absent']) }})</h5>
            <div class="table-responsive mb-3">@include('result.partials.tabulation-table', ['tableRows' => $reportSections['Absent'], 'tableMode' => 'absent'])</div>
        </div>
        <div class="d-none d-print-block">
            @foreach($subjectWisePages as $page)
                <section class="result-print-page">
                    @include('result.partials.passive-result-header', ['headerVariant' => 'subject-wise'])
                    <h5>{{ $page['title'] }}</h5>
                    @include('result.partials.tabulation-table', [
                        'tableRows' => $page['rows'],
                        'slStart' => $page['slStart'] ?? 1,
                        'tableMode' => str_contains(strtolower($page['title']), 'incomplete')
                            ? 'incomplete'
                            : (str_contains(strtolower($page['title']), 'absent') ? 'absent' : (str_contains(strtolower($page['title']), 'fail') ? 'fail' : 'pass')),
                    ])
                    @include('result.partials.passive-signatures')
                    <div class="page-footer">Page {{ $page['pageNumber'] }} of {{ $page['pageCount'] }}</div>
                </section>
            @endforeach
        </div>
    @endif
</div></div></div>
@endsection

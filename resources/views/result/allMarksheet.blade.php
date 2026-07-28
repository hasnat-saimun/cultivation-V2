@extends('result.singleinclude')
@section('backTitle')
All Marksheet
@endsection
@section('backIndex')
<style>
    @page { size: A4 landscape; margin: 5mm; }
    .result-table{width:100%;border-collapse:collapse;table-layout:fixed}.result-table th,.result-table td{border:1px solid #111;padding:3px;text-align:center;font-size:9px;overflow-wrap:anywhere}.result-table thead{display:table-header-group}.result-table tr{page-break-inside:avoid}.name-col{text-align:left!important;min-width:110px}.subject-outcome{white-space:nowrap}
    .result-print-page{break-after:page;page-break-after:always;min-height:185mm;position:relative}.result-print-page:last-child{break-after:auto;page-break-after:auto}.page-footer{text-align:right;font-size:9px;margin-top:4px}
    .result-signatures{display:flex;justify-content:space-around;gap:50px;margin-top:20px;break-inside:avoid;page-break-inside:avoid}.result-signature{text-align:center;min-width:180px;padding-top:35px}.result-signature-line{border-top:1px solid #111}.result-signature-image{height:35px;max-width:120px;object-fit:contain}
    @media print{.navbar,.sidebar-main,.breadcrumbs-area,.footer-wrap-layout1,form,.d-print-none{display:none!important}.d-print-block{display:block!important}.container-fluid,.main-content{margin:0!important;padding:0!important;width:100%!important}}
</style>
<div class="main-website"><div class="main-content"><div class="container-fluid mb-4">
    <form method="GET" action="{{ route('allMarksheet') }}" class="row g-2 align-items-end d-print-none">
        @include('result.partials.result-filters')
        <div class="col-md-2"><button class="btn btn-primary w-100">Show Result</button></div>
        <div class="col-12"><label><input type="checkbox" name="compact" value="1" {{ $compactMode ? 'checked' : '' }}> Compact per-student subjects</label></div>
    </form>
    @if(!$examId || !$classId || !$sessionId)
        <div class="alert alert-info">Please select required filters (Exam, Class & Session) to view results.</div>
    @else
        <div class="d-print-none">
            @include('result.partials.passive-result-header')
            @if($studentsLoaded && empty($tabulationRows))<div class="alert alert-warning">No marks found for the selected filters.</div>@endif
            <h5 class="mt-4 text-success">All Subject Pass ({{ count($reportSections['Pass']) }})</h5>
            <div class="table-responsive mb-4">@include('result.partials.tabulation-table', ['tableRows' => $reportSections['Pass']])</div>
            @foreach($failedGroups as $failedCount => $failedRows)
                <h5 class="mt-4 text-danger">Failed in {{ $failedCount }} Subject{{ $failedCount === 1 ? '' : 's' }} ({{ count($failedRows) }})</h5>
                <div class="table-responsive mb-4">@include('result.partials.tabulation-table', ['tableRows' => $failedRows])</div>
            @endforeach
            <h5 class="mt-4 text-warning">Incomplete ({{ count($reportSections['Incomplete']) }})</h5>
            <div class="table-responsive mb-4">@include('result.partials.tabulation-table', ['tableRows' => $reportSections['Incomplete']])</div>
            <h5 class="mt-4 text-danger">Absent ({{ count($reportSections['Absent']) }})</h5>
            <div class="table-responsive mb-4">@include('result.partials.tabulation-table', ['tableRows' => $reportSections['Absent']])</div>
        </div>
        <div class="d-none d-print-block">
            @foreach($subjectWisePages as $page)
                <section class="result-print-page">
                    @include('result.partials.passive-result-header')
                    <h5>{{ $page['title'] }}</h5>
                    @include('result.partials.tabulation-table', ['tableRows' => $page['rows']])
                    @include('result.partials.passive-signatures')
                    <div class="page-footer">Page {{ $page['pageNumber'] }} of {{ $page['pageCount'] }}</div>
                </section>
            @endforeach
        </div>
    @endif
</div></div></div>
@endsection

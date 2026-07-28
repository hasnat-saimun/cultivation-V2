@extends('result.singleinclude')
@section('backTitle')
At a Glance Result
@endsection
@section('backIndex')
<style>
    @page{size:A4 landscape;margin:5mm}.glance-table{width:100%;border-collapse:collapse;table-layout:fixed}.glance-table th,.glance-table td{border:1px solid #111;padding:2px;text-align:center;font-size:8px;overflow-wrap:anywhere}.glance-table thead{display:table-header-group}.glance-table tr{page-break-inside:avoid}.name-col{text-align:left!important;min-width:100px}.mini{min-width:22px}
    .result-print-page{break-after:page;page-break-after:always;min-height:185mm}.result-print-page:last-child{break-after:auto;page-break-after:auto}.page-footer{text-align:right;font-size:9px;margin-top:4px}.result-signatures{display:flex;justify-content:space-around;gap:50px;margin-top:18px;break-inside:avoid;page-break-inside:avoid}.result-signature{text-align:center;min-width:180px;padding-top:30px}.result-signature-line{border-top:1px solid #111}.result-signature-image{height:35px;max-width:120px;object-fit:contain}
    @media print{.navbar,.sidebar-main,.breadcrumbs-area,.footer-wrap-layout1,form,.d-print-none{display:none!important}.d-print-block{display:block!important}}
</style>
<div class="main-website"><div class="main-content"><div class="container-fluid mb-4">
    <form method="GET" action="{{ route('atGlanceResult') }}" class="row g-2 align-items-end d-print-none">
        @include('result.partials.result-filters')
        <div class="col-md-2"><button class="btn btn-primary w-100">Show Result</button></div>
    </form>
    @if(!$examId || !$classId || !$sessionId)
        <div class="alert alert-info">Please select required filters (Exam, Class & Session) to view results.</div>
    @else
        <div class="d-print-none">
            @include('result.partials.passive-result-header')
            @if($studentsLoaded && empty($tabulationRows))<div class="alert alert-warning">No marks found for the selected filters.</div>
            @else<div class="table-responsive">@include('result.partials.glance-table', ['tableRows' => $glanceRows])</div>@endif
        </div>
        <div class="d-none d-print-block">
            @foreach($glancePages as $page)
                <section class="result-print-page">
                    @include('result.partials.passive-result-header')
                    @include('result.partials.glance-table', ['tableRows' => $page['rows']])
                    @include('result.partials.passive-signatures')
                    <div class="page-footer">Page {{ $page['pageNumber'] }} of {{ $page['pageCount'] }}</div>
                </section>
            @endforeach
        </div>
    @endif
</div></div></div>
@endsection

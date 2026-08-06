@extends('result.singleinclude')
@section('backTitle')
Result Summary
@endsection
@section('backIndex')
<style>
    @page{size:A4 portrait;margin:8mm}.summary-table{width:100%;border-collapse:collapse;margin-bottom:12px}.summary-table th,.summary-table td{border:1px solid #111;padding:5px;text-align:center;font-size:10px}.summary-table thead{display:table-header-group}.summary-table tr{page-break-inside:avoid}.left{text-align:left!important}
    .result-print-page{break-after:page;page-break-after:always;min-height:270mm}.result-print-page:last-child{break-after:auto;page-break-after:auto}.page-footer{text-align:right;font-size:9px;margin-top:4px}.result-signatures{display:flex;justify-content:space-around;gap:35px;margin-top:18px;break-inside:avoid;page-break-inside:avoid}.result-signature{text-align:center;min-width:150px;padding-top:30px}.result-signature-line{border-top:1px solid #111}.result-signature-image{height:35px;max-width:120px;object-fit:contain}
    @media print{.navbar,.sidebar-main,.breadcrumbs-area,.footer-wrap-layout1,form,.d-print-none{display:none!important}.d-print-block{display:block!important}}
</style>
<div class="main-website"><div class="main-content"><div class="container-fluid mb-4">
    <form method="GET" action="{{ route('result.summary') }}" class="row g-2 align-items-end d-print-none">
        @include('result.partials.result-filters', ['showGenderFilter' => true])
        <div class="col-md-2"><button class="btn btn-primary w-100">Show Summary</button></div>
    </form>
    @if(!$examId || !$classId || !$sessionId)
        <div class="alert alert-info">Please select required filters (Exam, Class & Session) to view summary.</div>
    @else
        <div class="d-print-none">
            @include('result.partials.passive-result-header', ['headerVariant' => 'result-summary'])
            @if(!$hasData)<div class="alert alert-warning">No result data found for the selected filters.</div>@endif
            @include('result.partials.summary-overall')
            @include('result.partials.summary-subjects', ['subjectRows' => $subjectStats])
            @include('result.partials.summary-distributions')
            @include('result.partials.passive-signatures')
        </div>
        <div class="d-none d-print-block">
            @foreach($summaryView['subjectPages'] as $page)
                <section class="result-print-page">
                    @include('result.partials.passive-result-header', ['headerVariant' => 'result-summary'])
                    @include('result.partials.summary-overall')
                    @include('result.partials.summary-subjects', ['subjectRows' => $page['subjectRows']])
                    @if($page['pageNumber'] === $page['pageCount'])
                        @include('result.partials.summary-distributions')
                    @endif
                    @include('result.partials.passive-signatures')
                    <div class="page-footer">Page {{ $page['pageNumber'] }} of {{ $page['pageCount'] }}</div>
                </section>
            @endforeach
        </div>
    @endif
</div></div></div>
@endsection

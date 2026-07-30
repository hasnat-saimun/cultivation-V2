@extends('result.singleinclude')

@section('backTitle')
At-a-Glance Result
@endsection

@section('backIndex')
@include('result.partials.result-report-shell-styles', ['printMargin' => '8mm'])
<style>
    .glance-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .glance-table th,
    .glance-table td {
        border: 1px solid #1f2937;
        padding: 3px 4px;
        text-align: center;
        font-size: 9px;
        line-height: 1.25;
        vertical-align: middle;
        overflow-wrap: anywhere;
    }
    .glance-table thead { display: table-header-group; }
    .glance-table thead th {
        background: #eef2f7;
        color: #111827;
        font-weight: 700;
    }
    .glance-table tbody tr:nth-child(even) { background: #f8fafc; }
    .glance-table tr { page-break-inside: avoid; break-inside: avoid; }
    .glance-table .sl-col { width: 30px; }
    .glance-table .roll-col { width: 40px; }
    .glance-table .id-col { width: 76px; }
    .glance-table .name-col {
        width: 125px;
        min-width: 125px;
        text-align: left !important;
        white-space: normal;
        word-break: normal;
    }
    .glance-table .summary-col { min-width: 44px; }
    .glance-table .status-col { min-width: 58px; }
    .glance-table .mini { min-width: 24px; }
    .glance-table-empty {
        padding: 28px 12px !important;
        color: #6b7280;
        font-size: 11px !important;
        text-align: center !important;
    }

    @media print {
        .glance-table th,
        .glance-table td {
            padding: 2px 3px;
            font-size: 8px;
            line-height: 1.18;
            border: 1px solid #111 !important;
        }
        .glance-table thead th { background: #eef2f7 !important; }
        .glance-table tbody tr:nth-child(even) { background: #fff !important; }
    }
</style>

<div class="main-website">
    <div class="main-content">
        <div class="container-fluid mb-4">
            @include('result.partials.result-report-filter', [
                'filterAction' => route('atGlanceResult'),
            ])

            @include('result.partials.result-report-empty-state')

            @if($examId && $classId && $sessionId)
                <div class="print-report d-print-none">
                    @include('result.partials.passive-result-header', ['headerVariant' => 'at-a-glance'])

                    @if(!$studentsLoaded || !empty($tabulationRows))
                        <div class="result-report-card table-responsive">
                            @include('result.partials.glance-table', [
                                'tableRows' => $glanceRows,
                                'showEmptyState' => true,
                            ])
                        </div>
                    @endif
                </div>

                <div class="print-report d-none d-print-block">
                    @forelse($glancePages as $page)
                        <section class="result-print-page">
                            @include('result.partials.passive-result-header', ['headerVariant' => 'at-a-glance'])
                            @include('result.partials.glance-table', [
                                'tableRows' => $page['rows'],
                                'slStart' => $page['slStart'] ?? 1,
                            ])
                            @include('result.partials.passive-signatures')
                            <div class="page-footer">Page {{ $page['pageNumber'] }} of {{ $page['pageCount'] }}</div>
                        </section>
                    @empty
                        <section class="result-print-page">
                            @include('result.partials.passive-result-header', ['headerVariant' => 'at-a-glance'])
                            @include('result.partials.glance-table', [
                                'tableRows' => [],
                                'showEmptyState' => true,
                            ])
                        </section>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

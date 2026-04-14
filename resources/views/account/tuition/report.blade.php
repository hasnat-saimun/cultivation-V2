@extends('account.include')
@section('backTitle')
Report
@endsection
@section('backIndex')
<style>
    .invoice-wrap{background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 20px rgba(15,23,42,0.06);}
    .invoice-head{display:flex;justify-content:space-between;gap:16px;border-bottom:1px dashed #e5e7eb;padding:18px 22px;align-items:flex-start;}
    .invoice-title{font-size:18px;font-weight:700;letter-spacing:.5px;}
    .invoice-meta{font-size:13px;color:#475569;}
    .invoice-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:16px;padding:14px 22px;}
    .invoice-card{border:1px solid #eef2f7;border-radius:8px;padding:12px 14px;background:#f8fafc;}
    .invoice-card h6{font-size:12px;text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;color:#64748b;}
    .invoice-card p{margin:0 0 6px 0;font-size:14px;color:#0f172a;}
    .invoice-table{padding:0 22px 6px 22px;}
    .invoice-table table{width:100%;border-collapse:collapse;}
    .invoice-table th{background:#0f172a;color:#fff;font-size:13px;padding:10px;border:1px solid #0f172a;}
    .invoice-table td{border:1px solid #e5e7eb;padding:10px;font-size:14px;}
    .invoice-total{display:flex;justify-content:flex-end;padding:10px 22px 18px 22px;}
    .invoice-total .total-box{min-width:220px;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;background:#fff;}
    .invoice-total .row{display:flex;justify-content:space-between;margin:0 !important;font-size:14px;}
    .invoice-total .grand{font-weight:700;font-size:16px;color:#0f172a;border-top:1px dashed #e5e7eb;padding-top:6px;margin-top:6px;}
    .invoice-sign{display:flex;justify-content:space-between;gap:16px;padding:20px 22px 26px 22px;}
    .invoice-sign .line{border-top:1px solid #0f172a;margin-top:28px;min-width:220px;text-align:center;font-size:13px;color:#0f172a;}
    @media print{
        .d-print-none{display:none!important;}
        html, body{background:#ffffff!important;}
        .wrapper, .dashboard-page-one, .card, .card-body{background:#ffffff!important;}
        .invoice-wrap{box-shadow:none;border:1px solid #e5e7eb;background:#ffffff!important;}
        *{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    }
</style>
<div class="row gutters-20 mb-4">
    <div class="col-12 mx-auto">
        <div class="row" id="report">
            @if(!empty($singleView))
                @php
                    $stdData = \App\Models\newAdmission::where(['stdId'=>$singleView->stdId])->first();
                    if(!empty($stdData)):
                        $sessionData= \App\Models\sessionManage::find($stdData->sessName);
                        $classData = \App\Models\classManage::find($stdData->className);
                        $sectionData = \App\Models\sectionManage::find($stdData->sectionName);
                    endif;
                @endphp
                @if(!empty($stdData))
                <div class="receipt-main col-12 mx-auto">
                    <div class="invoice-wrap">
                        @include('components.institute-header')
                        <div class="invoice-head">
                            <div>
                                <div class="invoice-title">Student Fees Invoice</div>
                                <div class="invoice-meta">Invoice #{{ $singleView->id }} · {{ $singleView->created_at->format('d-M-Y') }}</div>
                            </div>
                            <div class="invoice-meta text-end">
                                <div><strong>Student ID:</strong> {{ $stdData->stdId }}</div>
                                <div><strong>Roll:</strong> {{ $stdData->rollNumber }}</div>
                                <div><strong>Fee Month:</strong> {{ !empty($singleView->fee_month) ? \Carbon\Carbon::parse($singleView->fee_month)->format('M Y') : '-' }}</div>
                            </div>
                        </div>
                        <div class="invoice-grid">
                            <div class="invoice-card">
                                <h6>Student</h6>
                                <p><strong>{{ $stdData->fullName }}</strong></p>
                                <p><strong>Class:</strong> {{ $classData->className ?? '-' }}</p>
                                <p><strong>Section:</strong> {{ $sectionData->section ?? '-' }}</p>
                                <p><strong>Session:</strong> {{ $sessionData->session ?? '-' }}</p>
                            </div>
                            <div class="invoice-card">
                                <h6>Contact</h6>
                                <p><strong>Mobile:</strong> {{ $stdData->phone ?? '-' }}</p>
                                <p><strong>Email:</strong> {{ $stdData->mail ?? '-' }}</p>
                            </div>
                        </div>
                        @php
                            $setupAmount = (float)($singleView->due_amount ?? $singleView->amount ?? 0);
                            $collectedAmount = (float)($singleView->paid_amount ?? $singleView->amount ?? 0);
                            $dueAmount = max(0, $setupAmount - $collectedAmount);
                            $status = $singleView->payment_status ?? ($collectedAmount >= $setupAmount && $setupAmount > 0 ? 'paid' : ($collectedAmount > 0 ? 'partial' : 'unpaid'));
                        @endphp
                        <div class="invoice-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Description</th>
                                        <th style="width:140px;">Setup Amount</th>
                                        <th style="width:140px;">Collected Amount</th>
                                        <th style="width:120px;">Due Amount</th>
                                        <th style="width:110px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @php
                                    $feesData = \App\Models\feesManager::find($singleView->feesType);
                                    if(!empty($feesData)):
                                        $feesName = $feesData->feesName;
                                    else:
                                        $feesName="-";
                                    endif;
                                @endphp
                                    <tr>
                                        <td>{{ !empty($singleView->fee_month) ? \Carbon\Carbon::parse($singleView->fee_month)->format('M Y') : '-' }}</td>
                                        <td>{{ $feesName }}</td>
                                        <td>{{ number_format($setupAmount, 2) }}</td>
                                        <td>{{ number_format($collectedAmount, 2) }}</td>
                                        <td>{{ number_format($dueAmount, 2) }}</td>
                                        <td>{{ ucfirst($status) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="invoice-total">
                            <div class="total-box">
                                <div class="row"><span>Setup Amount</span><span>{{ number_format($setupAmount, 2) }}</span></div>
                                <div class="row"><span>Collected Amount</span><span>{{ number_format($collectedAmount, 2) }}</span></div>
                                <div class="row grand"><span>Due Amount</span><span>{{ number_format($dueAmount, 2) }}</span></div>
                            </div>
                        </div>
                        <div class="invoice-sign">
                            <div class="line">Guardian Sign</div>
                            <div class="line">Cash Incharge</div>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-2 d-grid gap-2 mt-4">
                            <button class="btn btn-success btn-lg my-2 d-print-none" onclick="printDiv('report')"><i class="fa-regular fa-print"></i> Print</button>
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-info">
                    Sorry! No student data found with your query
                </div>
                @endif
            @else
            <div class="alert alert-info">
                Sorry! No data found
            </div>
            @endif
            
        </div>
    </div>
</div>
<script type="text/javascript">
    function printDiv(e){
        window.print();
    }
</script>
@endsection

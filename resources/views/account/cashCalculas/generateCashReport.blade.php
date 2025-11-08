@extends('account.include')
@section('backTitle')
Report
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row" id="report">
            @if(!empty($feesList))
                @php
                    $sumDebit = $feesList->where('transaction','Debit')->sum('amount');
                    $sumCredit = $feesList->where('transaction','Credit')->sum('amount');
                    $balanceDue = $sumCredit-$sumDebit;
                        // Between text only when report is explicitly By Date Range
                        $betweenText = null;
                        $reportType = $reportType ?? null;
                        if($reportType === 'range' && !empty($fromDate) && !empty($toDate)){
                            $minDate = \Carbon\Carbon::parse($fromDate)->format('d-M-Y');
                            $maxDate = \Carbon\Carbon::parse($toDate)->format('d-M-Y');
                            $betweenText = "Between {$minDate} to {$maxDate}";
                        }
                @endphp 
                @if(!empty($feesList))
                <div class="receipt-main col-12 mx-auto">
                    @include('components.institute-header')
                    @if(!empty($betweenText))
                    <div class="alert alert-info py-2">
                        <strong>Report:</strong> {{ $betweenText }}
                    </div>
                    @endif
                    <div class="receipt-header receipt-header-mid row">
                        <div class="col-xs-4 col-sm-4 col-md-4">
                            <div class="receipt-left">
                                <p><b>Generate Date : </b> {{date('d-M-Y')}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        @php
                            $creditItems = $feesList->where('transaction','Credit');
                            $debitItems = $feesList->where('transaction','Debit');
                            $result = $sumCredit - $sumDebit;
                            $isProfit = $result > 0;
                            $isLoss = $result < 0;
                            $resultLabel = $isProfit ? 'Profit' : ($isLoss ? 'Loss' : 'Balanced');
                            $sign = $isProfit ? '+' : ($isLoss ? '-' : '');
                        @endphp

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-2 mb-3 bg-light">
                                    <h5 class="fw-bold mb-2">Credit Transactions</h5>
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Date</th>
                                                <th>Purpose</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($creditItems as $cr)
                                                <tr>
                                                    <td>{{ $cr->created_at->format('Y-m-d') }}</td>
                                                    <td>{{ $cr->source }}</td>
                                                    <td class="text-end">{{ number_format($cr->amount, 2) }}/-</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No credit transactions.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-end">Total Credit</th>
                                                <th class="text-end">{{ number_format($sumCredit, 2) }}/-</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-2 mb-3 bg-light">
                                    <h5 class="fw-bold mb-2">Debit Transactions</h5>
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Date</th>
                                                <th>Purpose</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($debitItems as $dr)
                                                <tr>
                                                    <td>{{ $dr->created_at->format('Y-m-d') }}</td>
                                                    <td>{{ $dr->source }}</td>
                                                    <td class="text-end">{{ number_format($dr->amount, 2) }}/-</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No debit transactions.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-end">Total Debit</th>
                                                <th class="text-end">{{ number_format($sumDebit, 2) }}/-</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 p-3 border rounded bg-white">
                            <div class="row align-items-center">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <span class="badge bg-success-subtle text-success fw-semibold">Credit: {{ number_format($sumCredit,2) }}/-</span>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <span class="badge bg-danger-subtle text-danger fw-semibold">Debit: {{ number_format($sumDebit,2) }}/-</span>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    @if($isProfit)
                                        <span class="badge bg-success text-white fs-6">{{ $sign }}{{ number_format(abs($result),2) }}/- ({{ $resultLabel }})</span>
                                    @elseif($isLoss)
                                        <span class="badge bg-danger text-white fs-6">{{ $sign }}{{ number_format(abs($result),2) }}/- ({{ $resultLabel }})</span>
                                    @else
                                        <span class="badge bg-secondary text-white fs-6">0.00/- (Balanced)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="receipt-header receipt-header-mid receipt-footer row ">
                            <div class="col-xs-6 col-sm-6 col-md-6 text-start mt-5">
                                    <p><u>Gurdian Sign</u></p>
                            </div>
                            <div class="col-xs-6 col-sm-6 col-md-6 text-right mt-5">
                                    <p><u>Cash Incharge</u></p>
                            </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-2 d-grid gap-2 mt-5">
                            <a href="{{ route('cashDateReport') }}" class="btn btn-secondary btn-lg my-4 d-print-none">&larr; Go Back</a>
                        </div>
                        <div class="col-2 d-grid gap-2 mt-5">
                            <button class="btn btn-success btn-lg my-4 d-print-none" onclick="printDiv('report')"><i class="fa-regular fa-print"></i> Print</button>
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
        var printContents = document.getElementById(e).innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
    }
</script>
@endsection

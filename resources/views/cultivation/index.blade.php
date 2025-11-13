@extends('cultivation.include')
@section('backTitle')
Dashboard
@endsection
@section('backIndex')
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    @isset($summary)
    <div class="col-xl-2 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-blue">
                        <i class="flaticon-calendar text-blue"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Today</div>
                        <div class="item-number"><span>{{ $today ?? date('Y-m-d') }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-green ">
                        <i class="flaticon-check text-green"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Present</div>
                        <div class="item-number"><span class="counter" data-num="{{ $summary['present'] }}">{{ $summary['present'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-red">
                        <i class="flaticon-cancel text-red"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Absent</div>
                        <div class="item-number"><span class="counter" data-num="{{ $summary['absent'] }}">{{ $summary['absent'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-yellow">
                        <i class="flaticon-alarm text-orange"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Late</div>
                        <div class="item-number"><span class="counter" data-num="{{ $summary['late'] }}">{{ $summary['late'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light">
                        <i class="flaticon-document text-dark"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Excused</div>
                        <div class="item-number"><span class="counter" data-num="{{ $summary['excused'] }}">{{ $summary['excused'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light">
                        <i class="flaticon-classmates text-dark"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Total</div>
                        <div class="item-number"><span class="counter" data-num="{{ $summary['total'] }}">{{ $summary['total'] }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endisset
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-green ">
                        <i class="flaticon-classmates text-green"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Students</div>
                        <div class="item-number"><span class="counter" data-num="{{ $metrics['students'] ?? 0 }}">{{ number_format($metrics['students'] ?? 0) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-blue">
                        <i class="flaticon-multiple-users-silhouette text-blue"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Teachers</div>
                        <div class="item-number"><span class="counter" data-num="{{ $metrics['teachers'] ?? 0 }}">{{ number_format($metrics['teachers'] ?? 0) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-yellow">
                        <i class="flaticon-couple text-orange"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Parents</div>
                        <div class="item-number"><span class="counter" data-num="{{ $metrics['parents'] ?? 0 }}">{{ number_format($metrics['parents'] ?? 0) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-5">
                    @php
                        $p = $metrics['monthlyProfitLoss'] ?? 0;
                        $profitClass = $p > 0 ? 'bg-light-green' : ($p < 0 ? 'bg-light-red' : 'bg-light');
                        $iconClass = $p > 0 ? 'fa-arrow-trend-up text-green' : ($p < 0 ? 'fa-arrow-trend-down text-red' : 'fa-minus text-dark');
                    @endphp
                    <div class="item-icon {{ $profitClass }}">
                        <i class="fa-solid {{ $iconClass }}" style="font-size:26px;"></i>
                    </div>
                </div>
                <div class="col-7">
                    <div class="item-content">
                        <div class="item-title">Month Profit/Loss</div>
                        <div class="item-number">
                            <span class="{{ $p>0 ? 'text-success' : ($p<0 ? 'text-danger' : 'text-muted') }}">{{ $p<0 ? '-' : '' }}$ {{ number_format(abs($p),2) }}</span>
                        </div>
                        <div class="small mt-1 text-muted">
                            <span>Income: ${{ number_format($metrics['monthlyProfitIncome'] ?? 0,2) }}</span> &middot;
                            <span>Expense: ${{ number_format($metrics['monthlyProfitExpense'] ?? 0,2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
<div class="row gutters-20 mb-4">
    <div class="col-md-6 col-12">
        <a href="{{ route('attendanceIndex') }}" class="btn btn-primary btn-lg w-100">Mark Attendance</a>
    </div>
    <div class="col-md-6 col-12">
        <a href="{{ route('attendanceReport') }}" class="btn btn-outline-primary btn-lg w-100">Attendance Report</a>
    </div>
    @isset($isTeacher)
        @if($isTeacher)
            <div class="col-12 mt-2"><small class="text-muted">Showing your classes only.</small></div>
        @endif
    @endisset
    @isset($summary)
    <div class="col-lg-8 col-12 mt-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Cash Management — This Month</strong>
                <span class="text-muted small">Debit vs Credit</span>
            </div>
            <div class="card-body">
                <canvas id="cashChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-12 mt-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column justify-content-center">
                @php $p = $metrics['monthlyProfitLoss'] ?? 0; @endphp
                <div class="d-flex align-items-center mb-2">
                    <div class="me-2">
                        <i class="fa-solid {{ $p>0?'fa-arrow-trend-up text-success':($p<0?'fa-arrow-trend-down text-danger':'fa-minus text-muted') }}" style="font-size:28px;"></i>
                    </div>
                    <div>
                        <div class="text-muted">Month Profit/Loss</div>
                        <div class="h4 mb-0 {{ $p>0 ? 'text-success' : ($p<0 ? 'text-danger' : 'text-muted') }}">{{ $p<0?'-':'' }}$ {{ number_format(abs($p),2) }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-between text-muted">
                    <div>Income<br><strong>$ {{ number_format($metrics['monthlyProfitIncome'] ?? 0,2) }}</strong></div>
                    <div>Expense<br><strong>$ {{ number_format($metrics['monthlyProfitExpense'] ?? 0,2) }}</strong></div>
                </div>
            </div>
        </div>
    </div>
    @endisset
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
@if(!empty($metrics['cashChart']))
<script>
    (function(){
        const el = document.getElementById('cashChart');
        if(!el) return;
        const labels = @json($metrics['cashChart']['labels'] ?? []);
        const income = @json($metrics['cashChart']['income'] ?? []);
        const expense = @json($metrics['cashChart']['expense'] ?? []);
        new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {label:'Credit', data: income, backgroundColor:'#1a9d55'},
                    {label:'Debit', data: expense, backgroundColor:'#d04949'}
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero:true } }
            }
        });
    })();
</script>
@endif
@endsection
@extends('cultivation.include')
@section('backTitle')
Dashboard
@endsection
@section('backIndex')
<!-- Dashboard summary Start -->
<style>
    .summary-box{background:#fff;border:1px solid #e5e8ec;border-radius:12px;padding:16px;display:flex;align-items:center;box-shadow:0 1px 2px rgba(0,0,0,0.04);min-height:110px}
    .summary-icon{width:60px;height:60px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:28px;margin-right:14px}
    .summary-icon.date{background:#e9f2ff;color:#2f6fed}
    .summary-icon.present{background:#e9f9ef;color:#1a9d55}
    .summary-icon.absent{background:#ffe9eb;color:#d04949}
    .summary-icon.total{background:#fff3e1;color:#c78922}
    .summary-icon.teacher{background:#e9f2ff;color:#2f6fed}
    .summary-meta{font-size:13px;font-weight:600;color:#69707a;text-transform:uppercase;letter-spacing:.5px}
    .summary-value{font-size:24px;font-weight:700;line-height:1.2;margin-top:4px}
    @media (max-width:767px){.summary-box{margin-bottom:12px}}
    .summary-grid>.col-xl-3{margin-bottom:18px}
    .summary-date-banner{font-size:14px;font-weight:600;color:#445367;margin:0 0 6px}
    .summary-wrapper{width:100%}
    .summary-value small{font-size:12px;color:#8a9199;font-weight:400}
    .summary-box .summary-icon i{transition:transform .3s}
    .summary-box:hover .summary-icon i{transform:scale(1.1)}
    .summary-box[data-type="present"]{border-color:#caf3d9}
    .summary-box[data-type="absent"]{border-color:#ffd1d6}
    .summary-box[data-type="total"]{border-color:#fde3b5}
</style>
<div class="summary-date-banner">Date: <strong>{{ $today ?? date('Y-m-d') }}</strong></div>
<div class="row summary-grid gutters-20 mb-3">
    @isset($summary)
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="summary-box" data-type="present">
            <div class="summary-icon present"><i class="fa-solid fa-user-check"></i></div>
            <div class="summary-wrapper text-end flex-grow-1">
                <div class="summary-meta">Present</div>
                <div class="summary-value">{{ $summary['present'] }} <small>students</small></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="summary-box" data-type="absent">
            <div class="summary-icon absent"><i class="fa-solid fa-user-xmark"></i></div>
            <div class="summary-wrapper text-end flex-grow-1">
                <div class="summary-meta">Absent</div>
                <div class="summary-value">{{ $summary['absent'] }} <small>students</small></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="summary-box" data-type="total">
            <div class="summary-icon total"><i class="fa-solid fa-users"></i></div>
            <div class="summary-wrapper text-end flex-grow-1">
                <div class="summary-meta">Total Student</div>
                <div class="summary-value">{{ number_format($metrics['students'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="summary-box" data-type="total">
            <div class="summary-icon teacher"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div class="summary-wrapper text-end flex-grow-1">
                <div class="summary-meta">Teachers</div>
                <div class="summary-value">{{ number_format($metrics['teachers'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    @endisset
</div>
<!-- Dashboard summary End -->
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
                        <div class="h4 mb-0 {{ $p>0 ? 'text-success' : ($p<0 ? 'text-danger' : 'text-muted') }}">{{ $p<0?'-':'' }}BDT {{ number_format(abs($p),2) }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-between text-muted">
                    <div>Credit<br><strong>BDT {{ number_format($metrics['monthlyProfitIncome'] ?? 0,2) }}</strong></div>
                    <div>Debit<br><strong>BDT {{ number_format($metrics['monthlyProfitExpense'] ?? 0,2) }}</strong></div>
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
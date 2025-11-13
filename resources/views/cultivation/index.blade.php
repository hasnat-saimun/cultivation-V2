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
    <div class="col-12 mt-3">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <strong>Attendance Rate Today:</strong>
                    <span class="ms-1">{{ $attendanceRate ?? 0 }}%</span>
                </div>
                <div class="progress w-50" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $attendanceRate ?? 0 }}%" aria-valuenow="{{ $attendanceRate ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>
    @endisset
</div>
@endsection
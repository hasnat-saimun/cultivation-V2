@extends('cultivation.include')
@section('backTitle')
Dashboard
@endsection
@section('backIndex')
    @endisset
</div>
                    <div class="summary-box" data-type="absent">
                        <div class="summary-icon absent"><i class="fa-solid fa-user-xmark"></i></div>
                        <div class="summary-wrapper text-end flex-grow-1">
                            <div class="summary-meta d-flex justify-content-between align-items-center">
                                <span class="flex-grow-1 text-start">Absent</span>
                                <span class="badge bg-danger" style="font-size:11px;">{{ $absentRate }}%</span>
                            </div>
                            <div class="summary-value">{{ $summary['absent'] }} <small>students</small></div>
                        </div>
                    </div>
                </div>
            @else
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="summary-box" data-type="present">
                        <div class="summary-icon present"><i class="fa-solid fa-user-check"></i></div>
                        <div class="summary-wrapper text-end flex-grow-1">
                            <div class="summary-meta d-flex justify-content-between align-items-center">
                                <span class="flex-grow-1 text-start">Present</span>
                                <span class="badge bg-success" style="font-size:11px;">{{ $attendanceRate ?? 0 }}%</span>
                            </div>
                            <div class="summary-value">{{ $summary['present'] }} <small>students</small></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="summary-box" data-type="absent">
                        <div class="summary-icon absent"><i class="fa-solid fa-user-xmark"></i></div>
                        <div class="summary-wrapper text-end flex-grow-1">
                            <div class="summary-meta d-flex justify-content-between align-items-center">
                                <span class="flex-grow-1 text-start">Absent</span>
                                <span class="badge bg-danger" style="font-size:11px;">{{ $absentRate }}%</span>
                            </div>
                            <div class="summary-value">{{ $summary['absent'] }} <small>students</small></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="summary-box" data-type="teacher">
                        <div class="summary-icon teacher"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <div class="summary-meta">Teachers</div>
                        <div class="summary-value">{{ $metrics['teachers'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="summary-box" data-type="parent">
                        <div class="summary-icon parent"><i class="fa-solid fa-people-roof"></i></div>
                        <div class="summary-meta">Parents</div>
                        <div class="summary-value">{{ $metrics['parents'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="summary-box" data-type="earnings">
                        <div class="summary-icon earnings"><i class="fa-solid fa-coins"></i></div>
                        <div class="summary-meta">Earnings</div>
                        <div class="summary-value">৳{{ number_format($metrics['earnings'] ?? 0) }}</div>
                    </div>
                </div>
            @endif
        </div>
        <div>Credit<br><strong>BDT {{ number_format($metrics['monthlyProfitIncome'] ?? 0,2) }}</strong></div>
        <div>Debit<br><strong>BDT {{ number_format($metrics['monthlyProfitExpense'] ?? 0,2) }}</strong></div>
    </div>
    </div>
    </div>
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
        let currentType = 'bar';
        const makeConfig = (type)=>({
            type,
            data:{
                labels,
                datasets:[
                    {label:'Credit', data: income, backgroundColor:type==='bar'?'#1a9d55':'#1a9d55', borderColor:'#1a9d55', tension:.25, stack:type==='bar'?'cash':undefined},
                    {label:'Debit', data: expense, backgroundColor:type==='bar'?'#d04949':'#d04949', borderColor:'#d04949', tension:.25, stack:type==='bar'?'cash':undefined}
                ]
            },
            options:{
                responsive:true,
                plugins:{legend:{position:'top'}, tooltip:{callbacks:{label:(ctx)=> ctx.dataset.label+': BDT '+Number(ctx.parsed.y).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}}},
                scales:{
                    x:{stacked: type==='bar'},
                    y:{beginAtZero:true, stacked: type==='bar', ticks:{callback:(v)=>'BDT '+v}}
                }
            }
        });
        let chart = new Chart(el, makeConfig('bar'));
        const barBtn = document.getElementById('chartBarBtn');
        const lineBtn = document.getElementById('chartLineBtn');
        function setType(t){
            if(t===currentType) return; currentType=t;
            chart.destroy();
            chart = new Chart(el, makeConfig(t));
            barBtn.classList.toggle('active', t==='bar');
            lineBtn.classList.toggle('active', t==='line');
        }
        barBtn.addEventListener('click',()=>setType('bar'));
        lineBtn.addEventListener('click',()=>setType('line'));
    })();
</script>
@endif
@endsection
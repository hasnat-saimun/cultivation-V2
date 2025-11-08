@extends('account.include')
@section('backTitle')
Report Into Date
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row ">
            <div class="col-8 mx-auto  ">
                <div class="card shadow  p-2 border-0 ">
                @include('components.institute-header')
                @if(session()->has('error'))
                    <div class="alert alert-danger">
                        {{session()->get('error')}}
                    </div>
                @endif
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{session()->get('success')}}
                    </div>
                @endif
                    <form method="POST" class="card-body form form-group" action="{{route('getCashReport')}}">
                        @csrf
                        <style>
                            /* match tuition feesReport radio UI reset */
                            #reportTypeOptions .form-check-label::before,
                            #reportTypeOptions .form-check-label::after,
                            #reportTypeOptions .form-check-input:checked + .form-check-label::before,
                            #reportTypeOptions .form-check-input + .form-check-label::before {
                                border: 0 !important;
                                box-shadow: none !important;
                                background: transparent !important;
                                display: none !important;
                                content: '' !important;
                            }
                            #reportTypeOptions .form-check-input:focus { box-shadow: none !important; }
                            #reportTypeOptions .form-check { padding-left: 0 !important; }
                            #reportTypeOptions .form-check-label { padding-left: 0 !important; margin-left: .25rem !important; }
                            #reportTypeOptions .form-check-input { margin-right: .25rem !important; }
                            #reportTypeOptions .form-check-inline { margin-right: 1rem; }
                        </style>

                        <div class="mb-3">
                            <label class="form-label d-block border-0">Report Type</label>
                            <div id="reportTypeOptions">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reportType" id="rtSingle" value="single">
                                    <label class="form-check-label border-0" for="rtSingle">By Single Date</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reportType" id="rtRange" value="range" checked>
                                    <label class="form-check-label border-0" for="rtRange">By Date Range</label>
                                </div>
                            </div>
                        </div>

                        <div id="singleDateGroup" class="mb-2" style="display:none;">
                            <label for="singleDate" class="form-label">Date</label>
                            <input type="date" class="form-control form-control-sm" id="singleDate" name="singleDate" placeholder="">
                        </div>

                        <div id="rangeDateGroup" class="row g-2 mb-2">
                            <div class="col-6">
                                <label for="fromDate" class="form-label">From Date</label>
                                <input type="date" class="form-control form-control-sm" id="fromDate" name="fromDate" placeholder="">
                            </div>
                            <div class="col-6">
                                <label for="toDate" class="form-label">To Date</label>
                                <input type="date" class="form-control form-control-sm" id="toDate" name="toDate" placeholder="">
                            </div>
                        </div>
                        <div class=" row   mt-5">
                            <div class="col-6">
                                <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Generate Report</button></div>
                            <div class="col-2">
                                <button class="btn-fill-lg bg-blue-dark btn-hover-bluedark" type="reset">Reset</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>  
<script>
(function(){
    const rtSingle = document.getElementById('rtSingle');
    const rtRange = document.getElementById('rtRange');
    const singleGroup = document.getElementById('singleDateGroup');
    const rangeGroup = document.getElementById('rangeDateGroup');
    const singleInput = document.getElementById('singleDate');
    const fromInput = document.getElementById('fromDate');
    const toInput = document.getElementById('toDate');

    function toggleGroups(){
        singleInput && singleInput.removeAttribute('required');
        fromInput && fromInput.removeAttribute('required');
        toInput && toInput.removeAttribute('required');

        if(rtSingle && rtSingle.checked){
            singleGroup.style.display = '';
            rangeGroup.style.display = 'none';
            singleInput && singleInput.setAttribute('required','required');
        } else {
            singleGroup.style.display = 'none';
            rangeGroup.style.display = '';
            fromInput && fromInput.setAttribute('required','required');
            toInput && toInput.setAttribute('required','required');
        }
    }

    [rtSingle, rtRange].forEach(r=> r && r.addEventListener('change', toggleGroups));
    toggleGroups();
})();
</script>
@endsection
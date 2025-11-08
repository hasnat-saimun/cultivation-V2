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
                    <form method="POST" class="card-body form form-group" action="{{route('getFeesReport')}}">
                        @csrf
                        <style>
                            /* Remove template’s custom pseudo-element borders on radio labels in this section */
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
                            /* Reduce left padding/gap between radio and label text */
                            #reportTypeOptions .form-check { padding-left: 0 !important; }
                            #reportTypeOptions .form-check-label { padding-left: 0 !important; margin-left: .25rem !important; }
                            #reportTypeOptions .form-check-input { margin-right: .25rem !important; }
                            #reportTypeOptions .form-check-inline { margin-right: 1rem; }
                        </style>
                        <div class="mb-3">
                            <label class="form-label d-block border-0" style="border:none;">Report Type</label>
                            <div id="reportTypeOptions">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reportType" id="rtSingle" value="single">
                                    <label class="form-check-label border-0" style="border:none;" for="rtSingle">By Single Date</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reportType" id="rtRange" value="range" checked>
                                    <label class="form-check-label border-0" style="border:none;" for="rtRange">By Date Range</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reportType" id="rtMultiple" value="multiple">
                                    <label class="form-check-label border-0" style="border:none;" for="rtMultiple">By Multiple Dates</label>
                                </div>
                            </div>
                            
                        </div>
                        <div class="mb-2">
                            <label for="stdId" class="form-label border-0" style="border:none;">Student ID</label>
                            <input type="number" class="form-control form-control-sm" id="stdId" name="stdId" placeholder="Enter student ID to get report" required>
                            </select>
                        </div>
                        <div id="singleDateGroup" class="mb-2" style="display:none;">
                            <label for="singleDate" class="form-label border-0" style="border:none;">Date</label>
                            <input type="date" class="form-control form-control-sm" id="singleDate" name="singleDate" placeholder="">
                        </div>

                        <div id="rangeDateGroup" class="row g-2 mb-2">
                            <div class="col-6">
                                <label for="fromDate" class="form-label border-0" style="border:none;">From Date</label>
                                <input type="date" class="form-control form-control-sm" id="fromDate" name="fromDate" placeholder="">
                            </div>
                            <div class="col-6">
                                <label for="toDate" class="form-label border-0" style="border:none;">To Date</label>
                                <input type="date" class="form-control form-control-sm" id="toDate" name="toDate" placeholder="">
                            </div>
                        </div>

                        <div id="multipleDateGroup" class="mb-2" style="display:none;">
                            <label class="form-label border-0" style="border:none;">Select Dates</label>
                            <div id="datesContainer">
                                <div class="input-group input-group-sm mb-2 date-row">
                                    <input type="date" name="dates[]" class="form-control" />
                                    <button type="button" class="btn btn-outline-danger remove-date" tabindex="-1">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addDate">+ Add another date</button>
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
        const rtMultiple = document.getElementById('rtMultiple');
        const singleGroup = document.getElementById('singleDateGroup');
        const rangeGroup = document.getElementById('rangeDateGroup');
        const multipleGroup = document.getElementById('multipleDateGroup');
        const singleInput = document.getElementById('singleDate');
        const fromInput = document.getElementById('fromDate');
        const toInput = document.getElementById('toDate');
        const datesContainer = document.getElementById('datesContainer');
        const addDateBtn = document.getElementById('addDate');

        function toggleGroups(){
            [singleInput, fromInput, toInput].forEach(el=> el && el.removeAttribute('required'));
            // remove required from all multiple date inputs
            if(datesContainer){
                Array.from(datesContainer.querySelectorAll('input[type="date"]')).forEach(el=> el.removeAttribute('required'));
            }

            if(rtSingle && rtSingle.checked){
                singleGroup.style.display = '';
                rangeGroup.style.display = 'none';
                multipleGroup.style.display = 'none';
                singleInput && singleInput.setAttribute('required','required');
            } else if(rtMultiple && rtMultiple.checked){
                singleGroup.style.display = 'none';
                rangeGroup.style.display = 'none';
                multipleGroup.style.display = '';
                // make first multiple date required
                const first = datesContainer && datesContainer.querySelector('input[type="date"]');
                first && first.setAttribute('required','required');
            } else { // range default
                singleGroup.style.display = 'none';
                rangeGroup.style.display = '';
                multipleGroup.style.display = 'none';
                fromInput && fromInput.setAttribute('required','required');
                toInput && toInput.setAttribute('required','required');
            }
        }

        [rtSingle, rtRange, rtMultiple].forEach(r=> r && r.addEventListener('change', toggleGroups));
        toggleGroups();

        if(addDateBtn && datesContainer){
            addDateBtn.addEventListener('click', function(){
                const row = document.createElement('div');
                row.className = 'input-group input-group-sm mb-2 date-row';
                row.innerHTML = '<input type="date" name="dates[]" class="form-control" />'+
                                '<button type="button" class="btn btn-outline-danger remove-date" tabindex="-1">Remove</button>';
                datesContainer.appendChild(row);
            });

            datesContainer.addEventListener('click', function(e){
                if(e.target.classList.contains('remove-date')){
                    const row = e.target.closest('.date-row');
                    if(row && datesContainer.children.length > 1){
                        row.remove();
                    }
                }
            });
        }
    })();
</script>
@endsection
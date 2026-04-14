@extends('account.include')
@section('backTitle')
Report Into Date
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-12 mx-auto">
        <div class="row ">
            <div class="col-10 mx-auto  ">
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
                @if(!empty($isTeacher) && $isTeacher)
                    <div class="alert alert-info">
                        Class teacher mode is active. You can search only your assigned class/section students.
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

                        <div class="mb-4">
                            <div class="card-body border rounded">
                                <h6 class="mb-3">Find Student</h6>
                                <div class="row">
                                    <div class="col-md-3 form-group">
                                        <label>Class</label>
                                        <select class="select2" id="filterClass">
                                            <option value="">All Classes</option>
                                            @foreach(($classData ?? []) as $cls)
                                                <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Session</label>
                                        <select class="select2" id="filterSession">
                                            <option value="">All Sessions</option>
                                            @foreach(($sessionData ?? []) as $sess)
                                                <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Section</label>
                                        <select class="select2" id="filterSection">
                                            <option value="">All Sections</option>
                                            @foreach(($sectionData ?? []) as $sec)
                                                <option value="{{ $sec->id }}">{{ $sec->section }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Search</label>
                                        <input type="text" id="filterSearch" class="form-control" placeholder="ID, name, roll">
                                    </div>
                                </div>
                                <div class="d-flex" style="gap:8px;">
                                    <button type="button" class="btn btn-primary" onclick="searchStudentsForReport()">Search</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetStudentSearchForReport()">Reset</button>
                                </div>
                                <div id="studentFilterResults" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="card-body border rounded bg-light">
                                <h6 class="mb-3">Selected Student</h6>
                                <div id="selectedStudentEmpty" class="text-muted">No student selected yet. Use the finder above to choose a student for this report.</div>
                                <div id="selectedStudentDetails" class="row g-3" style="display:none;">
                                    <div class="col-2">
                                        <div class="border rounded p-2 bg-white h-100">
                                            <small class="text-muted d-block">Student ID</small>
                                            <div id="selectedStudentId" class="fw-semibold">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-2 bg-white h-100">
                                            <small class="text-muted d-block">Student Name</small>
                                            <div id="selectedStudentName" class="fw-semibold">-</div>
                                        </div>
                                    </div>
                                    <div class="col-1">
                                        <div class="border rounded p-2 bg-white h-100">
                                            <small class="text-muted d-block">Roll</small>
                                            <div id="selectedStudentRoll" class="fw-semibold">-</div>
                                        </div>
                                    </div>
                                    <div class="col-1">
                                        <div class="border rounded p-2 bg-white h-100">
                                            <small class="text-muted d-block">Class</small>
                                            <div id="selectedStudentClass" class="fw-semibold">-</div>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="border rounded p-2 bg-white h-100">
                                            <small class="text-muted d-block">Session</small>
                                            <div id="selectedStudentSession" class="fw-semibold">-</div>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="border rounded p-2 bg-white h-100">
                                            <small class="text-muted d-block">Section</small>
                                            <div id="selectedStudentSection" class="fw-semibold">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block border-0" style="border:none;">Report Type</label>
                            <div id="reportTypeOptions">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reportType" id="rtDaily" value="daily">
                                    <label class="form-check-label border-0" style="border:none;" for="rtDaily">Daily</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reportType" id="rtMonthly" value="monthly" checked>
                                    <label class="form-check-label border-0" style="border:none;" for="rtMonthly">Monthly</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="reportType" id="rtCustom" value="custom">
                                    <label class="form-check-label border-0" style="border:none;" for="rtCustom">Custom Range</label>
                                </div>
                            </div>
                            <div class="mt-2 d-flex" style="gap:8px; flex-wrap:wrap;">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyReportPreset('today')">Today</button>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="applyReportPreset('thisMonth')">This Month</button>
                                <button type="button" class="btn btn-sm btn-outline-dark" onclick="applyReportPreset('last30')">Last 30 Days</button>
                            </div>
                            
                        </div>
                        <div class="mb-2">
                            <label for="stdId" class="form-label border-0" style="border:none;">Student ID</label>
                            <input type="text" class="form-control form-control-sm" id="stdId" name="stdId" placeholder="Enter or select student ID to get report" required>
                        </div>
                        <div id="dailyDateGroup" class="mb-2" style="display:none;">
                            <label for="dailyDate" class="form-label border-0" style="border:none;">Daily Date</label>
                            <input type="date" class="form-control form-control-sm" id="dailyDate" name="dailyDate" placeholder="">
                        </div>

                        <div id="monthlyDateGroup" class="mb-2">
                            <label for="reportMonth" class="form-label border-0" style="border:none;">Month</label>
                            <input type="month" class="form-control form-control-sm" id="reportMonth" name="reportMonth" value="{{ now()->format('Y-m') }}">
                        </div>

                        <div id="customDateGroup" class="row g-2 mb-2" style="display:none;">
                            <div class="col-6">
                                <label for="fromDate" class="form-label border-0" style="border:none;">From Date</label>
                                <input type="date" class="form-control form-control-sm" id="fromDate" name="fromDate" placeholder="">
                            </div>
                            <div class="col-6">
                                <label for="toDate" class="form-label border-0" style="border:none;">To Date</label>
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
        const rtDaily = document.getElementById('rtDaily');
        const rtMonthly = document.getElementById('rtMonthly');
        const rtCustom = document.getElementById('rtCustom');
        
        const dailyGroup = document.getElementById('dailyDateGroup');
        const monthlyGroup = document.getElementById('monthlyDateGroup');
        const customGroup = document.getElementById('customDateGroup');
        const dailyInput = document.getElementById('dailyDate');
        const monthInput = document.getElementById('reportMonth');
        const fromInput = document.getElementById('fromDate');
        const toInput = document.getElementById('toDate');

        function toDateInputValue(dt){
            const y = dt.getFullYear();
            const m = String(dt.getMonth() + 1).padStart(2, '0');
            const d = String(dt.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + d;
        }

        function toMonthInputValue(dt){
            const y = dt.getFullYear();
            const m = String(dt.getMonth() + 1).padStart(2, '0');
            return y + '-' + m;
        }

        function toggleGroups(){
            [dailyInput, monthInput, fromInput, toInput].forEach(el=> el && el.removeAttribute('required'));

            if(rtDaily && rtDaily.checked){
                dailyGroup.style.display = '';
                monthlyGroup.style.display = 'none';
                customGroup.style.display = 'none';
                dailyInput && dailyInput.setAttribute('required','required');
            } else if(rtCustom && rtCustom.checked) {
                dailyGroup.style.display = 'none';
                monthlyGroup.style.display = 'none';
                customGroup.style.display = '';
                fromInput && fromInput.setAttribute('required','required');
                toInput && toInput.setAttribute('required','required');
            } else {
                dailyGroup.style.display = 'none';
                monthlyGroup.style.display = '';
                customGroup.style.display = 'none';
                monthInput && monthInput.setAttribute('required','required');
            }
        }

        [rtDaily, rtMonthly, rtCustom].forEach(r=> r && r.addEventListener('change', toggleGroups));
        toggleGroups();

        window.applyReportPreset = function(type){
            const now = new Date();

            if(type === 'today'){
                if(rtDaily){
                    rtDaily.checked = true;
                }
                if(dailyInput){
                    dailyInput.value = toDateInputValue(now);
                }
                toggleGroups();
                return;
            }

            if(type === 'thisMonth'){
                if(rtMonthly){
                    rtMonthly.checked = true;
                }
                if(monthInput){
                    monthInput.value = toMonthInputValue(now);
                }
                toggleGroups();
                return;
            }

            if(type === 'last30'){
                if(rtCustom){
                    rtCustom.checked = true;
                }
                const end = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const start = new Date(end);
                start.setDate(end.getDate() - 29);
                if(fromInput){
                    fromInput.value = toDateInputValue(start);
                }
                if(toInput){
                    toInput.value = toDateInputValue(end);
                }
                toggleGroups();
            }
        };

    })();

    function searchStudentsForReport(){
        var classId = document.getElementById('filterClass').value;
        var sessionId = document.getElementById('filterSession').value;
        var sectionId = document.getElementById('filterSection').value;
        var search = document.getElementById('filterSearch').value;

        var params = new URLSearchParams();
        if(classId) params.append('classId', classId);
        if(sessionId) params.append('sessionId', sessionId);
        if(sectionId) params.append('sectionId', sectionId);
        if(search) params.append('search', search);

        var url = "{{ route('getStudentsForTutionFeeFilter') }}" + '?' + params.toString();
        var container = document.getElementById('studentFilterResults');
        container.innerHTML = '<div class="text-muted mt-2">Loading...</div>';

        fetch(url)
            .then(function(res){ return res.text(); })
            .then(function(html){ container.innerHTML = html; })
            .catch(function(){ container.innerHTML = '<div class="alert alert-danger mt-2">Failed to load students.</div>'; });
    }

    function resetStudentSearchForReport(){
        document.getElementById('filterClass').value = '';
        document.getElementById('filterSession').value = '';
        document.getElementById('filterSection').value = '';
        document.getElementById('filterSearch').value = '';
        document.getElementById('studentFilterResults').innerHTML = '';
        clearSelectedStudentForReport();
        try{
            if(window.jQuery){
                $('#filterClass').val('').trigger('change');
                $('#filterSession').val('').trigger('change');
                $('#filterSection').val('').trigger('change');
            }
        }catch(e){ }
    }

    function clearSelectedStudentForReport(){
        var empty = document.getElementById('selectedStudentEmpty');
        var details = document.getElementById('selectedStudentDetails');
        var fields = [
            'selectedStudentId',
            'selectedStudentName',
            'selectedStudentRoll',
            'selectedStudentClass',
            'selectedStudentSession',
            'selectedStudentSection'
        ];

        fields.forEach(function(id){
            var el = document.getElementById(id);
            if(el){
                el.textContent = '-';
            }
        });

        if(details){
            details.style.display = 'none';
        }
        if(empty){
            empty.style.display = '';
        }
    }

    function selectStudent(stdId, fullName, rollNo, className, sessionName, sectionName){
        var input = document.getElementById('stdId');
        if(input){
            input.value = stdId || '';
            input.focus();
            input.scrollIntoView({behavior:'smooth', block:'center'});
        }

        var empty = document.getElementById('selectedStudentEmpty');
        var details = document.getElementById('selectedStudentDetails');
        var values = {
            selectedStudentId: stdId || '-',
            selectedStudentName: fullName || '-',
            selectedStudentRoll: rollNo || '-',
            selectedStudentClass: className || '-',
            selectedStudentSession: sessionName || '-',
            selectedStudentSection: sectionName || '-'
        };

        Object.keys(values).forEach(function(id){
            var el = document.getElementById(id);
            if(el){
                el.textContent = values[id];
            }
        });

        if(empty){
            empty.style.display = 'none';
        }
        if(details){
            details.style.display = '';
        }
    }
</script>
@endsection
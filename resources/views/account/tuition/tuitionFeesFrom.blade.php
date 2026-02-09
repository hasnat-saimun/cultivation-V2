@extends('account.include')
@section('backTitle')
Tuition Fee
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row mx-auto ">
            
            <form method="POST" class="card-body form" action="{{route('saveTuitionfee')}}">
            @if(session()->has('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session()->has('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session()->has('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif
                @csrf
                <div class="row mb-4">
                    <h4 class="text-bold">Student Fees Collection</h4>
                </div>
                <div class="mb-4">
                    <div class="card-body">
                        <h6 class="mb-3">Find Student</h6>
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label>Class</label>
                                <select class="select2" id="filterClass">
                                    <option value="">All Classes</option>
                                    @foreach($classData as $cls)
                                        <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Session</label>
                                <select class="select2" id="filterSession">
                                    <option value="">All Sessions</option>
                                    @foreach($sessionData as $sess)
                                        <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Section</label>
                                <select class="select2" id="filterSection">
                                    <option value="">All Sections</option>
                                    @foreach($sectionData as $sec)
                                        <option value="{{ $sec->id }}">{{ $sec->section }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Search</label>
                                <input type="text" id="filterSearch" class="form-control" placeholder="ID, name, roll">
                            </div>
                        </div>
                        <div class="gap-2 mt-2">
                            <button type="button" class="btn btn-primary" onclick="searchStudents()">Search</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetStudentSearch()">Reset</button>
                        </div>
                        <div id="studentFilterResults"></div>
                    </div>
                </div>
                <div class="row align-items-center">
                    <div class="col-4 form-group">
                        <input type="text" class="form-control" placeholder="Enter student ID to collect tution fee" name="stdId" id="stdId" required>
                    </div>
                    <div class="col-4 text-center form-group">
                        <a href="#" onclick="getStudent()" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Get Data</a>
                    </div>
                </div>
                <div class="row" id="studentData">
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function searchStudents(){
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

    function resetStudentSearch(){
        document.getElementById('filterClass').value = '';
        document.getElementById('filterSession').value = '';
        document.getElementById('filterSection').value = '';
        document.getElementById('filterSearch').value = '';
        document.getElementById('studentFilterResults').innerHTML = '';
        try{
            if(window.jQuery){
                $('#filterClass').val('').trigger('change');
                $('#filterSession').val('').trigger('change');
                $('#filterSection').val('').trigger('change');
            }
        }catch(e){ /* ignore */ }
    }

    function selectStudent(stdId){
        var input = document.getElementById('stdId');
        input.value = stdId;
        getStudent();
        input.scrollIntoView({behavior:'smooth', block:'center'});
    }

    function getStudent() {
        var str   = document.getElementById('stdId').value;
        if(str == "") {
            document.getElementById("studentData").innerHTML = "";
            return;
        }else {
            var xmlhttp     = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("studentData").innerHTML = this.responseText;
                    if(window.initTuitionFeesDynamicRows){ window.initTuitionFeesDynamicRows(); }
                }
            };
            xmlhttp.open("GET","{{ url('/') }}/"+"getStudentForTutionFee/"+str,true);
            xmlhttp.send();
        }
    }
    // Attach dynamic-fee UI logic after content is injected
    (function(){
        let eventsBound = false;
        function rowsWrap(){
            const container = document.getElementById('studentData');
            if(!container) return null;
            return container.querySelector('#feesRows');
        }
        function updateRemoveState(){
            const wrap = rowsWrap(); if(!wrap) return;
            const rows = wrap.querySelectorAll('.fees-row');
            rows.forEach((r)=>{
                const rm = r.querySelector('.remove-row');
                if(rm) rm.disabled = (rows.length===1);
            });
        }
        function computeTotal(){
            const wrap = rowsWrap(); if(!wrap) return;
            let sum = 0;
            wrap.querySelectorAll('.amount-input').forEach(inp=>{
                const v = parseFloat(inp.value);
                if(!isNaN(v)) sum += v;
            });
            const t = document.getElementById('feesTotal');
            if(t) t.textContent = sum.toFixed(2);
        }
        function onClick(e){
            const wrap = rowsWrap(); if(!wrap) return;
            if(e.target.classList.contains('add-row')){
                const first = wrap.querySelector('.fees-row');
                if(!first) return;
                const clone = first.cloneNode(true);
                clone.querySelectorAll('select, input').forEach(el=>{ el.value = ''; });
                wrap.appendChild(clone);
                updateRemoveState();
                computeTotal();
            }
            if(e.target.classList.contains('remove-row')){
                const row = e.target.closest('.fees-row');
                if(row && rowsWrap().querySelectorAll('.fees-row').length>1){
                    row.remove();
                    updateRemoveState();
                    computeTotal();
                }
            }
        }
        function onInput(e){
            if(e.target.classList.contains('amount-input')){
                computeTotal();
            }
        }
        function onChange(e){
            if(e.target.matches('select[name="feesType[]"]')){
                const sel = e.target;
                const opt = sel.options[sel.selectedIndex];
                if(opt){
                    const amt = parseFloat(opt.getAttribute('data-amount'));
                    const row = sel.closest('.fees-row');
                    const input = row ? row.querySelector('.amount-input') : null;
                    if(input && !isNaN(amt)){
                        input.value = amt;
                        computeTotal();
                    }
                }
            }
        }
        window.initTuitionFeesDynamicRows = function(){
            const container = document.getElementById('studentData');
            if(!container || !rowsWrap()) return;
            if(!eventsBound){
                container.addEventListener('click', onClick);
                container.addEventListener('input', onInput);
                container.addEventListener('change', onChange);
                // prevent duplicate fee types on submit
                const form = container.closest('form');
                if(form){
                    form.addEventListener('submit', function(ev){
                        const wrap = rowsWrap(); if(!wrap) return;
                        const selected = [];
                        let dup = false;
                        wrap.querySelectorAll('select[name="feesType[]"]').forEach(sel=>{
                            const v = sel.value;
                            if(v){ if(selected.includes(v)) dup=true; else selected.push(v); }
                        });
                        if(dup){ ev.preventDefault(); alert('Duplicate fee types in the same submission are not allowed.'); }
                    });
                }
                eventsBound = true;
            }
            updateRemoveState();
            computeTotal();
        }
    })();
</script>

@endsection
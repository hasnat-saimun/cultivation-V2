@extends('account.include')
@section('backTitle')
Edit Tuition Fee
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row mx-auto ">
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
                    @if(session()->has('warning'))
                        <div class="alert alert-warning">
                            {{session()->get('warning')}}
                        </div>
                    @endif
        <form method="POST" class="card-body form form-group" action="{{route('updateTuitionFee')}}">
            @csrf
            <input type="hidden" name="tuitionFeeId" value="{{$editData->id}}">
            <input type="hidden" id="originalStdId" value="{{ $editData->stdId }}">
            @php
                $dueAmount = (float)($editData->due_amount ?? $editData->amount ?? 0);
                $paidAmount = (float)($editData->paid_amount ?? $editData->amount ?? 0);
                $currentStudentName = trim((($currentStudent->fullName ?? '').' '.($currentStudent->sureName ?? '')));
                $currentStudentName = $currentStudentName !== '' ? $currentStudentName : '-';

                $currentClass = '-';
                $currentSession = '-';
                $currentSection = '-';
                if (!empty($currentStudent)) {
                    $currentClass = optional(\App\Models\classManage::find($currentStudent->className))->className ?? '-';
                    $currentSession = optional(\App\Models\sessionManage::find($currentStudent->sessName))->session ?? '-';
                    $currentSection = optional(\App\Models\sectionManage::find($currentStudent->sectionName))->section ?? '-';
                }
            @endphp
            <div class="row mb-4">
                <h4 class="text-bold">Edit Tuition Fee Record</h4>
            </div>

            <div class="p-2 bg-secondary text-white rounded mb-4">
                <strong>Currently Linked Student:</strong>
                ID: <span>{{ $editData->stdId }}</span>
                | Name: <span>{{ $currentStudentName }}</span>
                | Class: <span>{{ $currentClass }}</span>
                | Session: <span>{{ $currentSession }}</span>
                | Section: <span>{{ $currentSection }}</span>
            </div>

            <div class="mb-4">
                <div class="card-body border rounded">
                    <h6 class="mb-3">Find Student (Optional, for replacing current student on this record)</h6>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Class</label>
                            <select class="select2" id="editFilterClass">
                                <option value="">All Classes</option>
                                @foreach(($classData ?? []) as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Session</label>
                            <select class="select2" id="editFilterSession">
                                <option value="">All Sessions</option>
                                @foreach(($sessionData ?? []) as $sess)
                                    <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Section</label>
                            <select class="select2" id="editFilterSection">
                                <option value="">All Sections</option>
                                @foreach(($sectionData ?? []) as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->section }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Search</label>
                            <input type="text" id="editFilterSearch" class="form-control" placeholder="ID, name, roll">
                        </div>
                    </div>
                    <div class="d-flex" style="gap:8px;">
                        <button type="button" class="btn btn-primary" onclick="searchEditStudents()">Search</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetEditStudentSearch()">Reset</button>
                    </div>
                    <div id="editStudentFilterResults" class="mt-2"></div>
                    <div class="mt-3 p-2 border rounded bg-light" id="selectedStudentInfo">
                        <strong>Target Student For Update:</strong>
                        <span id="selectedStudentInfoText">
                            ID: {{ old('stdId', $editData->stdId) }} | Name: {{ $currentStudentName }} | Class: {{ $currentClass }} | Session: {{ $currentSession }} | Section: {{ $currentSection }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="stdId" class="form-label">Student ID</label>
                    <input type="text" class="form-control" id="stdIdDisplay" value="{{ old('stdId', $editData->stdId) }}" readonly>
                    <input type="hidden" id="stdId" name="stdId" value="{{ old('stdId', $editData->stdId) }}">
                    <small class="text-muted">Use student search above to replace student if needed.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="feesType" class="form-label">Fees Type</label>
                    <select class="form-select form-control" id="feesType" name="feesType" required>
                        @if(!empty($feesData) && count($feesData)>0)
                            @foreach($feesData as $f)
                                <option value="{{ $f->id }}" data-amount="{{ $f->feesAmount }}" {{ (string)old('feesType', $editData->feesType) === (string)$f->id ? 'selected' : '' }}>{{ $f->feesName }}</option>
                            @endforeach
                        @else
                            <option value="">No fees found</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="feeMonth" class="form-label">Fee Month</label>
                    <input type="month" class="form-control" id="feeMonth" name="feeMonth" value="{{ old('feeMonth', !empty($editData->fee_month) ? \Carbon\Carbon::parse($editData->fee_month)->format('Y-m') : $editData->created_at->format('Y-m')) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="dueAmount" class="form-label">Setup Amount</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="dueAmount" name="dueAmount" value="{{ old('dueAmount', number_format($dueAmount, 2, '.', '')) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="paidAmount" class="form-label">Collected Amount</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="paidAmount" name="paidAmount" value="{{ old('paidAmount', number_format($paidAmount, 2, '.', '')) }}" required>
                </div>
                <div class="gap-2 mt-4">
                    <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Update Record</button>
                    <a href="{{route('tuitionFeeList')}}" class="btn-fill-lg bg-blue-dark btn-hover-bluedark">Back</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function searchEditStudents(){
        var classId = document.getElementById('editFilterClass').value;
        var sessionId = document.getElementById('editFilterSession').value;
        var sectionId = document.getElementById('editFilterSection').value;
        var search = document.getElementById('editFilterSearch').value;

        var params = new URLSearchParams();
        if(classId) params.append('classId', classId);
        if(sessionId) params.append('sessionId', sessionId);
        if(sectionId) params.append('sectionId', sectionId);
        if(search) params.append('search', search);

        var url = "{{ route('getStudentsForTutionFeeFilter') }}" + '?' + params.toString();
        var container = document.getElementById('editStudentFilterResults');
        container.innerHTML = '<div class="text-muted mt-2">Loading...</div>';

        fetch(url)
            .then(function(res){ return res.text(); })
            .then(function(html){ container.innerHTML = html; })
            .catch(function(){ container.innerHTML = '<div class="alert alert-danger mt-2">Failed to load students.</div>'; });
    }

    function resetEditStudentSearch(){
        document.getElementById('editFilterClass').value = '';
        document.getElementById('editFilterSession').value = '';
        document.getElementById('editFilterSection').value = '';
        document.getElementById('editFilterSearch').value = '';
        document.getElementById('editStudentFilterResults').innerHTML = '';
        try{
            if(window.jQuery){
                $('#editFilterClass').val('').trigger('change');
                $('#editFilterSession').val('').trigger('change');
                $('#editFilterSection').val('').trigger('change');
            }
        }catch(e){ }
    }

    function selectStudent(stdId, fullName, rollNo, className, sessionName, sectionName){
        var hiddenInput = document.getElementById('stdId');
        var displayInput = document.getElementById('stdIdDisplay');
        if(hiddenInput){ hiddenInput.value = stdId; }
        if(displayInput){ displayInput.value = stdId; }

        var text = 'ID: ' + (stdId || '-')
            + ' | Name: ' + (fullName || '-')
            + ' | Class: ' + (className || '-')
            + ' | Session: ' + (sessionName || '-')
            + ' | Section: ' + (sectionName || '-');
        var info = document.getElementById('selectedStudentInfoText');
        if(info){ info.textContent = text; }

        if(displayInput){
            displayInput.focus();
            displayInput.scrollIntoView({behavior:'smooth', block:'center'});
        }
    }

    (function(){
        var form = document.querySelector('form[action="{{ route('updateTuitionFee') }}"]');
        if(!form){ return; }

        form.addEventListener('submit', function(e){
            var originalStdId = String((document.getElementById('originalStdId') || {}).value || '').trim();
            var currentStdId = String((document.getElementById('stdId') || {}).value || '').trim();

            if(originalStdId !== '' && currentStdId !== '' && originalStdId !== currentStdId){
                var ok = confirm('You are changing this fee record from student ID ' + originalStdId + ' to ' + currentStdId + '. Continue?');
                if(!ok){
                    e.preventDefault();
                }
            }
        });
    })();
</script>

@endsection
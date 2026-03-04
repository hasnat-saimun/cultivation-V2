@extends('result.singleinclude')
@section('backTitle')
Transcript Generator
@endsection
@section('backIndex')
<style>
    @media print {
        .navbar, .breadcrumbs-area, form, .controls { display:none !important; }
    }
</style>
<div class="main-website">
    <div class="main-content">
        <div class="container-fluid mb-4">
            <form method="GET" action="{{ route('transcripts.bulk') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Exam *</label>
                    <select name="examId" class="form-control" required>
                        <option value="">Select</option>
                        @php $examList = \App\Models\Exam::orderBy('id','DESC')->get(); @endphp
                        @foreach($examList as $ex)
                            <option value="{{ $ex->id }}" {{ $ex->id == request('examId') ? 'selected' : '' }}>{{ $ex->examName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class *</label>
                    <select name="classId" class="form-control" required>
                        <option value="">Select</option>
                        @php $classList = \App\Models\classManage::orderBy('id','ASC')->get(); @endphp
                        @foreach($classList as $cl)
                            <option value="{{ $cl->id }}" {{ $cl->id == request('classId') ? 'selected' : '' }}>{{ $cl->className ?? ('Class-'.$cl->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Session</label>
                    <select name="sessionId" class="form-control">
                        <option value="">All</option>
                        @php $sessionList = \App\Models\sessionManage::orderBy('id','DESC')->get(); @endphp
                        @foreach($sessionList as $s)
                            <option value="{{ $s->id }}" {{ $s->id == request('sessionId') ? 'selected' : '' }}>{{ $s->session ?? ('Session-'.$s->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Section/Group</label>
                    <select name="sectionId" class="form-control">
                        <option value="">All</option>
                        @php $sectionList = \App\Models\sectionManage::orderBy('id','ASC')->get(); @endphp
                        @foreach($sectionList as $sec)
                            <option value="{{ $sec->id }}" {{ $sec->id == request('sectionId') ? 'selected' : '' }}>{{ $sec->section ?? ('Section-'.$sec->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select name="departmentId" class="form-control">
                        <option value="">All</option>
                        @php $departmentList = \App\Models\Department::orderBy('id','ASC')->get(); @endphp
                        @foreach($departmentList as $dept)
                            <option value="{{ $dept->id }}" {{ $dept->id == request('departmentId') ? 'selected' : '' }}>{{ $dept->departmentName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-success w-100">Load Students</button>
                </div>
                @if($studentsLoaded)
                <div class="col-md-3">
                    <a href="{{ route('transcripts.bulk') }}" class="btn btn-warning w-100">Reset</a>
                </div>
                @endif
            </form>
        </div>

        @if(!$examId || !$classId)
            <div class="alert alert-info container">Select Exam and Class to load student list.</div>
        @endif

        @if($examId && $classId)
        <div class="container-fluid">
            @include('components.result-header')
            <div class="d-flex justify-content-between align-items-center mb-2 controls">
                <div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openSelected()">Open Selected Transcripts</button>
                    <button type="button" class="btn btn-success btn-sm" onclick="downloadSelectedPdf()">Download Selected PDF</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAll(this)">Select All</button>
                </div>
                <div class="text-muted small">Tip: Use browser print to save to PDF.</div>
            </div>

            <form id="bulkPdfForm" method="POST" action="{{ route('transcripts.bulk.pdf') }}" class="d-none">
                @csrf
                <input type="hidden" name="examId" value="{{ $examId }}">
                <div id="bulkPdfStdIds"></div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark text-dark">
                        <tr>
                            <th><input type="checkbox" id="ck_all" onclick="toggleAll(this)"></th>
                            <th>Roll</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $stu)
                            <tr>
                                <td><input type="checkbox" class="ck_row" value="{{ $stu->stdId ?: $stu->id }}" data-roll="{{ $stu->rollNumber }}"></td>
                                <td>{{ $stu->rollNumber }}</td>
                                <td>{{ $stu->stdId }}</td>
                                <td>{{ $stu->fullName }} {{ $stu->sureName }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" target="_blank"
                                        href="{{ route('marksheetGenerate', ['stdId' => ($stu->stdId ?: $stu->id), 'studentId' => $stu->id, 'examId' => $examId]) }}">Open Transcript</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No students found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
<script>
function toggleAll(src){
    const ck = src.id === 'ck_all' ? src : document.getElementById('ck_all');
    const rows = document.querySelectorAll('.ck_row');
    const target = src.id === 'ck_all' ? ck.checked : !ck.checked;
    rows.forEach(r => r.checked = target);
    if(src.id !== 'ck_all') ck.checked = target;
}
function openSelected(){
    const examId = '{{ $examId }}';
    if(!examId){ alert('Please select an Exam first.'); return; }
    const rows = Array.from(document.querySelectorAll('.ck_row:checked'));
    if(rows.length === 0){ alert('Select at least one student.'); return; }
    rows.forEach((r,idx) => {
        const stdId = r.value;
        const url = `{{ route('marksheetGenerate') }}?stdId=${encodeURIComponent(stdId)}&examId=${encodeURIComponent(examId)}`;
        setTimeout(() => window.open(url, '_blank'), idx * 150);
    });
}

function downloadSelectedPdf(){
    const examId = '{{ $examId }}';
    if(!examId){ alert('Please select an Exam first.'); return; }
    const rows = Array.from(document.querySelectorAll('.ck_row:checked'));
    if(rows.length === 0){ alert('Select at least one student.'); return; }

    const container = document.getElementById('bulkPdfStdIds');
    container.innerHTML = '';
    rows.forEach((r) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'stdIds[]';
        input.value = r.value;
        container.appendChild(input);
    });
    document.getElementById('bulkPdfForm').submit();
}
</script>
@endsection

@extends('cultivation.include')
@section('backTitle') Mark Attendance @endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-md-10 col-12 mx-auto">
        <div class="card">
            <div class="card-header bg-white border-0 pb-0">
                <h5 class="fw-semibold mb-2">Mark Attendance</h5>
                {{-- Institutional header reuse --}}
                @include('cultivation.noticeHeader')
                {{-- Modern meta chips --}}
                <div class="attendance-meta mt-3">
                    <div class="chips">
                        <div class="chip chip-class" title="Selected Class">
                            <span class="icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor" role="img" focusable="false">
                                    <rect x="3" y="4" width="8.5" height="16" rx="2"></rect>
                                    <rect x="12.5" y="4" width="8.5" height="16" rx="2"></rect>
                                </svg>
                            </span>
                            <span>Class: <strong>{{ $classObj->className ?? $classId }}</strong></span>
                        </div>
                        @if(!empty($sessionId))
                            <div class="chip chip-session" title="Academic Session">
                                <span class="icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="currentColor" role="img" focusable="false">
                                        <polygon points="12,3 22,9 12,15 2,9"></polygon>
                                        <polygon points="12,10 22,16 12,22 2,16"></polygon>
                                    </svg>
                                </span>
                                <span>Session: <strong>{{ $sessionObj->session ?? $sessionId }}</strong></span>
                            </div>
                        @endif
                        @if(!empty($sectionId))
                            <div class="chip chip-section" title="Class Section">
                                <span class="icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="currentColor" role="img" focusable="false">
                                        <polygon points="5,4 17,4 22,9 12,21 2,9"></polygon>
                                    </svg>
                                </span>
                                <span>Section: <strong>{{ $sectionObj->section ?? $sectionId }}</strong></span>
                            </div>
                        @endif
                        <div class="chip chip-date" title="Attendance Date">
                            <span class="icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor" role="img" focusable="false">
                                    <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                                    <rect x="7" y="2" width="2" height="6" rx="1"></rect>
                                    <rect x="15" y="2" width="2" height="6" rx="1"></rect>
                                </svg>
                            </span>
                            <span>Date: <strong>{{ $date }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Note:</strong> Attendance should be taken for the Primary Class/Section assigned to a class-teacher. Other assignments (Admin Assign) are used for Marks Entry only.
                </div>
                <form method="POST" action="{{ route('attendanceStore') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="classId" value="{{ $classId }}">
                    <input type="hidden" name="sessionId" value="{{ $sessionId }}">
                    <input type="hidden" name="sectionId" value="{{ $sectionId }}">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student ID</th>
                                    <th>Roll</th>
                                    <th>Name</th>
                                    <th class="text-center">Present?
                                        <div>
                                            <input type="checkbox" id="toggle-all-present">
                                            <label for="toggle-all-present" class="mb-0 small">All</label>
                                        </div>
                                    </th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $i => $stu)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $stu->stdId }}</td>
                                    <td>{{ $stu->rollNumber }}</td>
                                    <td>{{ trim(($stu->fullName ?? '').' '.($stu->sureName ?? '')) }}</td>
                                    <td>
                                        @php $existingStatus = isset($existing[$stu->id]) ? $existing[$stu->id] : null; @endphp
                                        <input type="hidden" name="studentId[]" value="{{ $stu->id }}">
                                        <input type="checkbox" class="present-toggle" data-row="{{ $i }}" {{ ($existingStatus==='Present') ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <select name="status[]" class="form-select form-control">
                                            <option value="Present" {{ ($existingStatus==='Present') ? 'selected' : '' }}>Present</option>
                                            <option value="Absent" {{ ($existingStatus==='Absent') ? 'selected' : '' }}>Absent</option>
                                            <option value="Late" {{ ($existingStatus==='Late') ? 'selected' : '' }}>Late</option>
                                            <option value="Excused" {{ ($existingStatus==='Excused') ? 'selected' : '' }}>Excused</option>
                                        </select>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center">No students found for the selection.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-success">Save Attendance</button>
                    <a href="{{ route('attendanceIndex') }}" class="btn btn-secondary">Back</a>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const table = document.querySelector('table');
    const rowCheckboxes = Array.from(table.querySelectorAll('input.present-toggle'));
    const statusSelects = Array.from(table.querySelectorAll('select[name="status[]"]'));
    const toggleAll = document.getElementById('toggle-all-present');

    // Initial sync: if a select is Present, ensure its row checkbox is checked
    statusSelects.forEach((sel, idx) => {
        const isPresent = (sel.value === 'Present');
        if (rowCheckboxes[idx]) rowCheckboxes[idx].checked = isPresent;
    });

    // Row checkbox -> select sync
    rowCheckboxes.forEach((cb, idx) => {
        cb.addEventListener('change', () => {
            const sel = statusSelects[idx];
            if (!sel) return;
            if (cb.checked) {
                sel.value = 'Present';
            } else {
                // If it was Present and unchecked now, default to Absent for quick calling
                if (sel.value === 'Present') sel.value = 'Absent';
            }
        });
    });

    // Select -> checkbox sync (uncheck if not Present)
    statusSelects.forEach((sel, idx) => {
        sel.addEventListener('change', () => {
            const cb = rowCheckboxes[idx];
            if (!cb) return;
            cb.checked = (sel.value === 'Present');
        });
    });

    // Toggle all
    if (toggleAll) {
        toggleAll.addEventListener('change', () => {
            const makePresent = toggleAll.checked;
            rowCheckboxes.forEach((cb, idx) => {
                cb.checked = makePresent;
                const sel = statusSelects[idx];
                if (sel) sel.value = makePresent ? 'Present' : 'Absent';
            });
        });
    }
});
</script>
@endpush
@push('styles')
<style>
/* Responsive meta chips styling */
.attendance-meta .chips {display:flex;flex-wrap:wrap;gap:.75rem;}
.attendance-meta .chip {position:relative;display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1rem;border-radius:999px;font-size:clamp(.70rem,.55vw + .55rem,.9rem);line-height:1.1;font-weight:500;letter-spacing:.25px;background:#f1f5f9;color:#334155;box-shadow:0 1px 2px rgba(0,0,0,.08),0 0 0 1px rgba(255,255,255,.6) inset;overflow:hidden;}
.attendance-meta .chip .icon{display:inline-flex;align-items:center;justify-content:center;width:1.2em;height:1.2em;border-radius:50%;background:rgba(255,255,255,.18);box-shadow:inset 0 0 0 1px rgba(255,255,255,.25);}
.attendance-meta .chip .icon svg{width:.95em;height:.95em;}
.attendance-meta .chip strong {font-weight:600;}
.attendance-meta .chip:before {content:"";position:absolute;inset:0;border-radius:inherit;opacity:.12;}
.attendance-meta .chip-class {background:linear-gradient(135deg,#1e3a8a,#2563eb 70%);color:#fff;}
.attendance-meta .chip-session {background:linear-gradient(135deg,#0f766e,#0d9488 70%);color:#fff;}
.attendance-meta .chip-section {background:linear-gradient(135deg,#475569,#64748b 70%);color:#fff;}
.attendance-meta .chip-date {background:linear-gradient(135deg,#047857,#059669 70%);color:#fff;}
.attendance-meta .chip:hover {filter:brightness(1.08);transform:translateY(-1px);transition:.25s cubic-bezier(.4,0,.2,1);}
@media (max-width:576px){
    .attendance-meta .chip {flex:1 1 calc(50% - .75rem);justify-content:center;}
}
@media (prefers-color-scheme:dark){
    .attendance-meta .chip {background:#1e293b;color:#e2e8f0;}
    .attendance-meta .chip-class {background:linear-gradient(135deg,#1e40af,#1d4ed8);} 
    .attendance-meta .chip-session {background:linear-gradient(135deg,#0d9488,#14b8a6);} 
    .attendance-meta .chip-section {background:linear-gradient(135deg,#334155,#475569);} 
    .attendance-meta .chip-date {background:linear-gradient(135deg,#065f46,#047857);} 
}
</style>
@endpush
@endsection
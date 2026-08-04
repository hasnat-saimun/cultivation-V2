@extends('cultivation.include')
@section('backTitle')
Bulk Student Profile Update
@endsection
@section('backIndex')
<div class="row gutters-20 mt-4">
    <div class="col-12">
        <div class="card card-default">
            <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="fa-solid fa-edit"></i> Student Bulk Details Update</h3>
                <a href="{{ route('studentList') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-exclamation-circle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Validation Error!</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="alert alert-info d-flex align-items-center">
                    <i class="fa-solid fa-info-circle me-2 fs-4"></i>
                    <div>
                        <strong>Instructions:</strong> Edit any field directly in the table below. Click "Save All Changes" when done.
                        <span class="badge bg-primary ms-2 text-white">{{ $students->count() }} Students</span>
                    </div>
                </div>

                <form method="get" action="{{ route('studentBulkUpdate') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-auto form-group">
                        <label class="form-label">Session</label>
                        <select name="sessionId" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($sessionDetails as $session)
                                <option value="{{ $session->id }}" {{ ($filters['sessionId'] ?? '') == $session->id ? 'selected' : '' }}>{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-auto">
                        <label class="form-label">Class</label>
                        <select name="classId" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($classDetails as $class)
                                <option value="{{ $class->id }}" {{ ($filters['classId'] ?? '') == $class->id ? 'selected' : '' }}>{{ $class->className }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-auto">
                        <label class="form-label">Section</label>
                        <select name="sectionId" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($sectionDetails as $section)
                                <option value="{{ $section->id }}" {{ ($filters['sectionId'] ?? '') == $section->id ? 'selected' : '' }}>{{ $section->section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-auto">
                        <label class="form-label">Department</label>
                        <select name="departmentId" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($departmentDetails as $dept)
                                <option value="{{ $dept->id }}" {{ ($filters['departmentId'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->departmentName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-auto">
                        <label class="form-label">Search</label>
                        <input name="search" type="text" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="Name / Student ID / Phone">
                    </div>
                    <div class="form-group col-auto">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('studentBulkUpdate') }}" class="btn btn-light">Reset</a>
                    </div>
                </form>

                @if($students->count() > 0)
                    <form method="post" action="{{ route('studentBulkUpdateStore') }}" id="bulkUpdateForm">
                        @csrf
                        <input type="hidden" name="students" id="studentsPayload">
                        <div class="alert alert-danger d-none" id="bulkSerializationError" role="alert"></div>
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-bordered table-hover editable-table table-fit" id="studentTable">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>Student ID</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Roll</th>
                                        <th>Session</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Department</th>
                                        <th>Religious Subject</th>
                                        <th>4th Subject</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Gender</th>
                                        <th>Date of Birth</th>
                                        <th>Father</th>
                                        <th>Mother</th>
                                        <th>Guardian</th>
                                        <th>Guardian Mobile</th>
                                        <th>Relation</th>
                                        <th>Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $index => $student)
                                        <tr data-index="{{ $index }}">
                                            <td>
                                                <input type="hidden" name="students[{{ $index }}][id]" value="{{ $student->id }}">
                                                <span class="text-muted">{{ $student->stdId }}</span>
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="students[{{ $index }}][fullName]"
                                                    value="{{ $student->fullName }}"
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="First Name">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="students[{{ $index }}][sureName]"
                                                    value="{{ $student->sureName }}"
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="Last Name">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="students[{{ $index }}][rollNumber]"
                                                    value="{{ $student->rollNumber }}"
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="Roll">
                                            </td>
                                            <td>
                                                <select name="students[{{ $index }}][sessName]" class="form-select form-select-sm editable-input">
                                                    <option value="">-- Select --</option>
                                                    @foreach($sessionDetails as $sess)
                                                        <option value="{{ $sess->id }}" {{ (int)$student->sessName === (int)$sess->id ? 'selected' : '' }}>{{ $sess->session }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="students[{{ $index }}][className]" class="form-select form-select-sm editable-input">
                                                    <option value="">-- Select --</option>
                                                    @foreach($classDetails as $cls)
                                                        <option value="{{ $cls->id }}" {{ (int)$student->className === (int)$cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="students[{{ $index }}][sectionName]" class="form-select form-select-sm editable-input">
                                                    <option value="">-- Select --</option>
                                                    @foreach($sectionDetails as $sec)
                                                        <option value="{{ $sec->id }}" {{ (int)$student->sectionName === (int)$sec->id ? 'selected' : '' }}>{{ $sec->section }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="students[{{ $index }}][departmentName]" class="form-select form-select-sm editable-input">
                                                    <option value="">-- Select --</option>
                                                    @foreach($departmentDetails as $dept)
                                                        <option value="{{ $dept->id }}" {{ (int)$student->departmentName === (int)$dept->id ? 'selected' : '' }}>{{ $dept->departmentName }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                @php $selectedReligiousId = !empty($student->religiousSubjectId) ? (int)$student->religiousSubjectId : (int)($islamDefaultSubjectId ?? 0); @endphp
                                                <select name="students[{{ $index }}][religiousSubjectId]" class="form-select form-select-sm editable-input">
                                                    <option value="">-- Select --</option>
                                                    @foreach($religiousSubjectList as $relSub)
                                                        <option value="{{ $relSub->id }}" {{ $selectedReligiousId === (int)$relSub->id ? 'selected' : '' }}>{{ $relSub->subjectName }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="students[{{ $index }}][fourthSubjectId]" class="form-select form-select-sm editable-input">
                                                    <option value="">-- Select --</option>
                                                    @foreach($optionalSubjectList as $optSub)
                                                        <option value="{{ $optSub->id }}" {{ (int)$student->fourthSubjectId === (int)$optSub->id ? 'selected' : '' }}>{{ $optSub->subjectName }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="email"
                                                    name="students[{{ $index }}][mail]"
                                                    value="{{ $student->mail }}"
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="email@example.com">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="students[{{ $index }}][phone]"
                                                    value="{{ $student->phone }}"
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="Mobile">
                                            </td>
                                            <td>
                                                <select name="students[{{ $index }}][gender]" class="form-select form-select-sm editable-input">
                                                    <option value="">--</option>
                                                    <option value="1" {{ (string)$student->gender === '1' ? 'selected' : '' }}>Male</option>
                                                    <option value="2" {{ (string)$student->gender === '2' ? 'selected' : '' }}>Female</option>
                                                    <option value="3" {{ (string)$student->gender === '3' ? 'selected' : '' }}>Others</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="date"
                                                    name="students[{{ $index }}][dob]"
                                                    value="{{ $student->dob }}"
                                                    class="form-control form-control-sm editable-input">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="students[{{ $index }}][father]"
                                                    value="{{ $student->father }}"
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="Father">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="students[{{ $index }}][mother]"
                                                    value="{{ $student->mother }}"
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="Mother">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="students[{{ $index }}][gurdianName]"
                                                    value="{{ $student->gurdianName }}"
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="Guardian Name">
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="students[{{ $index }}][gurdianMobile]"
                                                    value="{{ $student->gurdianMobile }}"
                                                    class="form-control form-control-sm editable-input"
                                                    placeholder="Guardian Mobile">
                                            </td>
                                            <td>
                                                <select name="students[{{ $index }}][relationGurdian]" class="form-select form-select-sm editable-input">
                                                    <option value="">--</option>
                                                    <option value="1" {{ (string)$student->relationGurdian === '1' ? 'selected' : '' }}>Father</option>
                                                    <option value="2" {{ (string)$student->relationGurdian === '2' ? 'selected' : '' }}>Mother</option>
                                                    <option value="3" {{ (string)$student->relationGurdian === '3' ? 'selected' : '' }}>Brother</option>
                                                    <option value="4" {{ (string)$student->relationGurdian === '4' ? 'selected' : '' }}>Sister</option>
                                                    <option value="5" {{ (string)$student->relationGurdian === '5' ? 'selected' : '' }}>Uncle</option>
                                                    <option value="6" {{ (string)$student->relationGurdian === '6' ? 'selected' : '' }}>Aunty</option>
                                                    <option value="7" {{ (string)$student->relationGurdian === '7' ? 'selected' : '' }}>Other</option>
                                                </select>
                                            </td>
                                            <td>
                                                <textarea
                                                    name="students[{{ $index }}][address]"
                                                    class="form-control form-control-sm editable-input"
                                                    rows="1"
                                                    placeholder="Address">{{ $student->address }}</textarea>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="action-buttons d-flex gap-3 justify-content-end mt-5 pt-4 border-top">
                            <a href="{{ route('studentList') }}" class="btn btn-outline-secondary btn-md" id="cancelBtn">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-md" id="saveBtn">
                                <i class="fa-solid fa-check"></i> Save All Changes
                            </button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-exclamation-triangle"></i> No students found.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .editable-table {
        font-size: 0.9rem;
    }
    .table-fit {
        table-layout: auto;
        width: 100%;
    }
    .table-fit th,
    .table-fit td {
        white-space: nowrap;
        width: 1%;
    }
    .table-fit td input,
    .table-fit td select,
    .table-fit td textarea {
        min-width: 140px;
    }
    .editable-input {
        border: 2px solid #e0e0e0;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        background-color: #fafbfc;
    }
    .editable-input:hover {
        border-color: #c0c0c0;
        background-color: #ffffff;
    }
    .editable-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.3rem rgba(102, 126, 234, 0.15);
        outline: none;
    }
    .editable-input.border-warning {
        border-color: #ffc107;
        background-color: #fffef0;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.15);
    }
    .editable-table tbody tr:hover {
        background-color: #f0f4ff;
    }
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    textarea.editable-input {
        resize: vertical;
        min-height: 38px;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let hasChanges = false;
        const inputs = document.querySelectorAll('.editable-input');

        inputs.forEach(input => {
            input.addEventListener('change', function() {
                hasChanges = true;
                this.classList.add('border-warning');
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        const form = document.getElementById('bulkUpdateForm');
        if (form) {
            const payloadField = document.getElementById('studentsPayload');
            const errorBox = document.getElementById('bulkSerializationError');
            const originalControls = () => Array.from(form.querySelectorAll('[name^="students["]'));
            const serializeStudents = () => {
                const rows = Array.from(form.querySelectorAll('#studentTable tbody tr'));
                if (rows.length === 0) throw new Error('No student rows are available to update.');
                const students = rows.map(row => {
                    const student = {};
                    row.querySelectorAll('[name^="students["]').forEach(control => {
                        const field = control.name.match(/\[([^\]]+)\]$/)?.[1];
                        if (field) student[field] = control.value;
                    });
                    return student;
                });
                if (students.some(student => !student.id)) throw new Error('A student row is missing its record ID.');
                return JSON.stringify(students);
            };

            const showSerializationError = message => {
                errorBox.textContent = message;
                errorBox.classList.remove('d-none');
            };

            form.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' && event.target.matches('input.editable-input:not([type="hidden"])')) {
                    event.preventDefault();
                    form.requestSubmit(document.getElementById('saveBtn'));
                }
            });

            form.addEventListener('submit', function(event) {
                if (form.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }
                try {
                    const json = serializeStudents();
                    if (!json || json === '[]') throw new Error('No student rows were serialized.');
                    payloadField.value = json;
                    errorBox.classList.add('d-none');
                    originalControls().forEach(control => { control.disabled = true; });
                    form.dataset.submitting = 'true';
                    document.getElementById('saveBtn').disabled = true;
                    hasChanges = false;
                } catch (error) {
                    event.preventDefault();
                    originalControls().forEach(control => { control.disabled = false; });
                    payloadField.value = '';
                    showSerializationError(error.message || 'Student rows could not be prepared. Please try again.');
                }
            });

            form.addEventListener('formdata', function(event) {
                if (!payloadField.value) {
                    try { payloadField.value = serializeStudents(); }
                    catch (error) { showSerializationError(error.message || 'Student rows could not be prepared.'); return; }
                }
                Array.from(event.formData.keys()).filter(key => key.startsWith('students['))
                    .forEach(key => event.formData.delete(key));
                event.formData.set('students', payloadField.value);
            });
        }

        document.querySelectorAll('.alert-success').forEach(alert => {
            setTimeout(() => {
                new bootstrap.Alert(alert).close();
            }, 4000);
        });

        document.querySelectorAll('textarea.editable-input').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    });
</script>
@endpush

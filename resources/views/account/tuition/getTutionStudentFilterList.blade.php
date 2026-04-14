@if(!empty($students) && $students->count() > 0)
    <div class="card mt-3">
        <div class="card-body">
            <h6 class="mb-3">Select Student (showing up to 100)</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Roll</th>
                            <th>Class</th>
                            <th>Session</th>
                            <th>Section</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $stu)
                        @php
                            $classData = \App\Models\classManage::find($stu->className);
                            $sessionData = \App\Models\sessionManage::find($stu->sessName);
                            $sectionData = \App\Models\sectionManage::find($stu->sectionName);
                        @endphp
                        <tr>
                            <td>{{ $stu->stdId }}</td>
                            <td>{{ $stu->fullName }} {{ $stu->sureName }}</td>
                            <td>{{ $stu->rollNumber }}</td>
                            <td>{{ $classData->className ?? '-' }}</td>
                            <td>{{ $sessionData->session ?? '-' }}</td>
                            <td>{{ $sectionData->section ?? '-' }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" onclick='selectStudent(@json($stu->stdId), @json(trim(($stu->fullName ?? "")." ".($stu->sureName ?? ""))), @json((string)($stu->rollNumber ?? "")), @json($classData->className ?? "-"), @json($sessionData->session ?? "-"), @json($sectionData->section ?? "-"))'>Select</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-info mt-3">No students found for the selected filters.</div>
@endif

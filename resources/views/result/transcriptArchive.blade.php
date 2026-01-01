@php
    $studentDetails = $student ?? $archive->student;
    $stdName        = ($studentDetails->fullName ?? '').' '.($studentDetails->sureName ?? '');
    $rollNumber     = $archive->old_roll ?? ($studentDetails->rollNumber ?? '');
    $fName          = $studentDetails->fatherName ?? ($studentDetails->father ?? '');
    $mName          = $studentDetails->motherName ?? ($studentDetails->mother ?? '');
    $sessionName    = $sessionName ?? '-';
    $className      = $className ?? '-';
    $sectionName    = $sectionName ?? '-';
    $examName       = optional($archive->exam)->examName ?? '';
    $createdAt      = $archive->created_at ? $archive->created_at->format('d M Y') : '';
@endphp

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <strong>Academic Transcript</strong>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Student Name:</strong> {{ $stdName }}<br>
                <strong>Roll:</strong> {{ $rollNumber }}<br>
                <strong>Class:</strong> {{ $className }}<br>
                <strong>Section:</strong> {{ $sectionName }}<br>
            </div>
            <div class="col-md-6">
                <strong>Session:</strong> {{ $sessionName }}<br>
                <strong>Exam:</strong> {{ $examName }}<br>
                <strong>Archived At:</strong> {{ $createdAt }}<br>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Subject</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>GPA</th>
                    </tr>
                </thead>
                <tbody>
                @if(isset($transcriptData['subjects']) && is_array($transcriptData['subjects']))
                    @foreach($transcriptData['subjects'] as $subject)
                        <tr>
                            <td>{{ $subject['name'] ?? 'N/A' }}</td>
                            <td>{{ $subject['marks'] ?? 'N/A' }}</td>
                            <td>{{ $subject['grade'] ?? 'N/A' }}</td>
                            <td>{{ $subject['gpa'] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="4" class="text-center">No subject data found.</td></tr>
                @endif
                </tbody>
            </table>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <strong>Total Marks:</strong> {{ $transcriptData['total_marks'] ?? 'N/A' }}<br>
                <strong>GPA:</strong> {{ $transcriptData['gpa'] ?? 'N/A' }}<br>
            </div>
            <div class="col-md-6">
                <strong>Result:</strong> {{ $transcriptData['result'] ?? 'N/A' }}<br>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <h5>Grade Scale</h5>
                <table class="table table-bordered table-sm w-auto">
                    <thead>
                        <tr>
                            <th>Range of Marks</th>
                            <th>Grade</th>
                            <th>Point</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gradeList as $gl)
                            <tr>
                                <td>{{ $gl->minMark }} - {{ $gl->maxMark }}</td>
                                <td>{{ $gl->gradeName }}</td>
                                <td>{{ $gl->gradePoint }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

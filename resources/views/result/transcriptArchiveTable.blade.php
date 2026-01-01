@php
    // Main and optional subject tables, failed subjects, GPA, remarks, etc. for archive transcript
    $mainSubjects = $transcriptData['main_subjects'] ?? [];
    $optionalSubjects = $transcriptData['optional_subjects'] ?? [];
    $failedSubjects = $transcriptData['failed_subjects'] ?? [];
    $totalMarks = $transcriptData['total_marks'] ?? '-';
    $finalLetterGrade = $transcriptData['final_letter_grade'] ?? '-';
    $finalGradePoint = $transcriptData['gpa'] ?? '-';
    $remark = $transcriptData['result'] ?? '-';
@endphp

<h3 class="mt-4 mb-2 fw-bold">Main Subject</h3>
<table class="table table-bordered col-12 text-center">
    <thead>
        <th>Subject Name</th>
        <th>Theory</th>
        <th>MCQ</th>
        <th>Practical</th>
        <th>Total</th>
        <th>Grade</th>
        <th>Point</th>
    </thead>
    <tbody>
        @forelse($mainSubjects as $row)
            <tr>
                <td>{{ $row['name'] ?? '-' }}</td>
                <td>{{ $row['theory'] ?? '-' }}</td>
                <td>{{ $row['mcq'] ?? '-' }}</td>
                <td>{{ $row['practical'] ?? '-' }}</td>
                <td>{{ $row['total'] ?? '-' }}</td>
                <td>{{ $row['grade'] ?? '-' }}</td>
                <td>{{ isset($row['point']) ? number_format($row['point'],2) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No main subjects</td></tr>
        @endforelse
    </tbody>
</table>

<h3 class="mt-4 mb-2 fw-bold">Optional Subject</h3>
<table class="table table-bordered col-12 text-center">
    <thead>
        <th>Subject Name</th>
        <th>Theory</th>
        <th>MCQ</th>
        <th>Practical</th>
        <th>Total</th>
        <th>Grade</th>
        <th>Point</th>
    </thead>
    <tbody>
        @forelse($optionalSubjects as $row)
            <tr>
                <td>{{ $row['name'] ?? '-' }}</td>
                <td>{{ $row['theory'] ?? '-' }}</td>
                <td>{{ $row['mcq'] ?? '-' }}</td>
                <td>{{ $row['practical'] ?? '-' }}</td>
                <td>{{ $row['total'] ?? '-' }}</td>
                <td>{{ $row['grade'] ?? '-' }}</td>
                <td>{{ isset($row['point']) ? number_format($row['point'],2) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No optional subjects</td></tr>
        @endforelse
    </tbody>
</table>

<table class="col-12 mb-4  table table-bordered">
    <thead>
        <th width="20%">Total Marks: {{ $totalMarks }}</th>
        <th width="20%">Letter Grade: {{ $finalLetterGrade }}</th>
        <th width="20%">Grade Point: {{ $finalGradePoint }}</th>
        <th>Remark- {{ $remark }}</th>
    </thead>
</table>

@if(!empty($failedSubjects))
<div class="col-12 mb-3 failed-subjects">
    <h4 class="fw-bold text-danger">Failed Subjects ({{ count($failedSubjects) }})</h4>
    <ul class="mb-0">
        @foreach($failedSubjects as $fs)
            <li>{{ $fs }}</li>
        @endforeach
    </ul>
</div>
@endif

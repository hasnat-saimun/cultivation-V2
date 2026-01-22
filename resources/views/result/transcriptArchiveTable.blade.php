@php
    // Use stored merged subjects from archive (same shape as live transcript)
    $subjectsRaw = $transcriptData['subjects'] ?? [];
    $totalMarks = $transcriptData['total_marks'] ?? '-';
    $finalLetterGrade = $transcriptData['final_letter_grade'] ?? ($transcriptData['result_letter'] ?? ($transcriptData['result'] ?? '-'));
    $finalGradePoint = $transcriptData['gpa'] ?? '-';
    $remark = $transcriptData['result'] ?? '-';

    $mainSubjects = [];
    $optionalSubjects = [];
    $failedSubjects = [];

    $fmtComponent = function($p1, $p2){
        $v1 = is_numeric($p1) ? (float)$p1 : null;
        $v2 = is_numeric($p2) ? (float)$p2 : null;
        if($v1 !== null && $v2 !== null){ return '(' . $v1 . ' + ' . $v2 . ') = ' . ($v1 + $v2); }
        if($v1 !== null){ return $v1; }
        if($v2 !== null){ return $v2; }
        return '-';
    };

    foreach($subjectsRaw as $row){
        $isOptional = ($row['type'] ?? 'Main') === 'Optional';
        if($isOptional){ $target =& $optionalSubjects; } else { $target =& $mainSubjects; }

        $isPaired = !empty($row['paired']) && isset($row['paper1']) && isset($row['paper2']);

        if($isPaired){
            $p1 = $row['paper1'];
            $p2 = $row['paper2'];
            $theory     = $fmtComponent($p1['cq'] ?? null, $p2['cq'] ?? null);
            $mcq        = $fmtComponent($p1['mcq'] ?? null, $p2['mcq'] ?? null);
            $practical  = $fmtComponent($p1['practical'] ?? null, $p2['practical'] ?? null);
            $totalVal1  = is_numeric($p1['total'] ?? null) ? (float)$p1['total'] : null;
            $totalVal2  = is_numeric($p2['total'] ?? null) ? (float)$p2['total'] : null;
            $totalDisp  = ($totalVal1 !== null && $totalVal2 !== null) ? '(' . $totalVal1 . ' + ' . $totalVal2 . ') = ' . ($totalVal1 + $totalVal2) : ($row['total'] ?? '-');
            $grade      = $row['grade'] ?? '-';
            $point      = isset($row['gradePoint']) && is_numeric($row['gradePoint']) ? number_format($row['gradePoint'],2) : ($grade === 'F' ? '0.00' : '-');
        } else {
            $theory     = $row['cq'] ?? ($row['theory'] ?? '-');
            $mcq        = $row['mcq'] ?? '-';
            $practical  = $row['practical'] ?? '-';
            $totalDisp  = $row['total'] ?? ($row['marks'] ?? '-');
            $grade      = $row['grade'] ?? '-';
            $point      = isset($row['gradePoint']) && is_numeric($row['gradePoint']) ? number_format($row['gradePoint'],2) : ($grade === 'F' ? '0.00' : '-');
        }

        $target[] = [
            'name' => $row['name'] ?? '-',
            'theory' => $theory,
            'mcq' => $mcq,
            'practical' => $practical,
            'total' => $totalDisp,
            'grade' => $grade,
            'point' => $point,
        ];

        if(($row['grade'] ?? '-') === 'F' && !empty($row['name'])){
            $failedSubjects[] = $row['name'];
        }
    }
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
                <td>{{ $row['point'] ?? '-' }}</td>
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
                <td>{{ $row['point'] ?? '-' }}</td>
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

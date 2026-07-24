<table class="result-table">
    <thead><tr>
        <th>SL</th><th>Roll</th><th>Student ID</th><th class="name-col">Student Name</th>
        @foreach($subjects as $subject)<th>{{ $subject->subjectName }}{{ $subject->optional ? ' (4th)' : '' }}</th>@endforeach
        <th>Total Marks</th><th>Optional Bonus</th><th>GPA</th><th>Grade</th><th>Failed</th><th>Missing</th><th>Status</th>
    </tr></thead>
    <tbody>
    @foreach($tableRows as $row)
        <tr>
            <td>{{ $loop->iteration }}</td><td>{{ $row['studentIdentity']['roll'] }}</td>
            <td>{{ $row['student']->stdId ?? $row['studentIdentity']['id'] }}</td><td class="name-col">{{ $row['studentIdentity']['name'] }}</td>
            @foreach($subjects as $subject)
                <td>
                    @if(isset($row['cells'][$subject->subjectName]))
                        {{ $row['cells'][$subject->subjectName]['total'] }}
                        <span class="subject-outcome">({{ $row['cells'][$subject->subjectName]['grade'] }}/{{ $row['cells'][$subject->subjectName]['gradePoint'] }})</span>
                    @else
                        -
                    @endif
                </td>
            @endforeach
            <td>{{ $row['totalMarks'] }}</td><td>{{ number_format($row['optionalBonus'], 2) }}</td>
            <td>{{ $row['finalGpa'] ?? 'Incomplete' }}</td><td>{{ $row['finalLetter'] }}</td>
            <td>{{ $row['subjectFails'] }}</td><td>{{ $row['subjectMissing'] }}</td><td>{{ $row['status'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

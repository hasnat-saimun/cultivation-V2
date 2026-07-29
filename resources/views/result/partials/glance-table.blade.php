<table class="glance-table">
    <thead>
        <tr><th rowspan="2">Roll</th><th rowspan="2">Student ID</th><th rowspan="2" class="name-col">Name</th>
            @foreach($subjects as $subject)<th colspan="{{ $subject->componentColumnCount }}">{{ $subject->display_name ?? $subject->subjectName }}{{ ($subject->is_fourth_subject ?? false) ? ' (4th)' : '' }}{{ $subject->paired ? ' (Combined)' : '' }}</th>@endforeach
            <th rowspan="2">Merit Position</th><th rowspan="2">Total</th><th rowspan="2">GPA</th><th rowspan="2">Grade</th><th rowspan="2">Fail</th><th rowspan="2">Status</th>
        </tr>
        <tr>
            @foreach($subjects as $subject)
                @foreach($subject->componentColumns as $component)<th class="mini">{{ $component['label'] }}</th>@endforeach
            @endforeach
        </tr>
    </thead>
    <tbody>
    @foreach($tableRows as $row)
        <tr><td>{{ $row['studentIdentity']['roll'] }}</td><td>{{ $row['student']->stdId ?? $row['studentIdentity']['id'] }}</td><td class="name-col">{{ $row['studentIdentity']['name'] }}</td>
            @foreach($subjects as $subject)
                @foreach($subject->componentColumns as $component)
                    <td class="mini">{{ $row['cells'][$subject->cellKey][$component['key']] ?? '-' }}</td>
                @endforeach
            @endforeach
            <td>{{ $row['meritPosition'] ?? '-' }}</td><td>{{ $row['totalMarks'] }}</td><td>{{ $row['finalGpa'] ?? $row['classification'] }}</td><td>{{ $row['finalLetter'] }}</td><td>{{ $row['subjectFails'] }}</td><td>{{ $row['reportStatus'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

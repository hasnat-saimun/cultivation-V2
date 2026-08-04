<table class="glance-table">
    <thead>
        <tr><th rowspan="2" class="sl-col">SL</th><th rowspan="2" class="roll-col">Roll</th><th rowspan="2" class="id-col">Student ID</th><th rowspan="2" class="name-col">Student Name</th>
            @foreach($subjects as $subject)<th colspan="{{ $subject->componentColumnCount }}">{{ $subject->display_name ?? $subject->subjectName }}{{ ($subject->is_fourth_subject ?? false) ? ' (4th)' : '' }}{{ $subject->paired ? ' (Combined)' : '' }}</th>@endforeach
            <th rowspan="2" class="summary-col">Merit Position</th><th rowspan="2" class="summary-col">Total Marks</th><th rowspan="2" class="summary-col">GPA</th><th rowspan="2" class="summary-col">Grade</th><th rowspan="2" class="summary-col">Failed Subjects</th><th rowspan="2" class="status-col">Result Status</th>
        </tr>
        <tr>
            @foreach($subjects as $subject)
                @foreach($subject->componentColumns as $component)<th class="mini">{{ $component['label'] }}</th>@endforeach
            @endforeach
        </tr>
    </thead>
    <tbody>
    @forelse($tableRows as $row)
        <tr><td>{{ ($slStart ?? 1) + $loop->index }}</td><td>{{ $row['studentIdentity']['roll'] }}</td><td>{{ $row['student']->stdId ?? $row['studentIdentity']['id'] }}</td><td class="name-col">{{ $row['studentIdentity']['name'] }}</td>
            @foreach($subjects as $subject)
                @php
                    $subjectCell = $row['cells'][$subject->cellKey] ?? null;
                    $isFailedCompulsorySubject = is_array($subjectCell)
                        && ($subjectCell['status'] ?? null) === 'Fail'
                        && !($subject->optional ?? false)
                        && !($subject->is_fourth_subject ?? false);
                @endphp
                @foreach($subject->componentColumns as $component)
                    <td class="mini{{ $isFailedCompulsorySubject ? ' failed-subject-cell' : '' }}">{{ $subjectCell[$component['key']] ?? '-' }}</td>
                @endforeach
            @endforeach
            <td>{{ $row['meritPosition'] ?? '-' }}</td><td>{{ $row['totalMarks'] }}</td><td>{{ $row['finalGpa'] ?? $row['classification'] }}</td><td>{{ $row['finalLetter'] }}</td><td>{{ $row['subjectFails'] }}</td><td>{{ $row['reportStatus'] }}</td>
        </tr>
    @empty
        @if($showEmptyState ?? false)
            <tr>
                <td colspan="{{ 10 + collect($subjects)->sum('componentColumnCount') }}" class="glance-table-empty">
                    No result rows are available for the selected criteria.
                </td>
            </tr>
        @endif
    @endforelse
    </tbody>
</table>

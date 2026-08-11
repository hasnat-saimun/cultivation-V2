@forelse($rows as $row)
    @php($isFailedSubject = ($row['status'] ?? null) === 'Fail')
    <tr data-subject-id="{{ $row['id'] }}" data-status="{{ $row['status'] }}">
        <td>{{ $row['name'] }}{{ !empty($optionalLabel) ? ' '.$optionalLabel : '' }}</td>
        <td>{{ $row['cq'] }}</td>
        <td>{{ $row['mcq'] }}</td>
        <td>{{ $row['practical'] }}</td>
        <td>{{ $row['total'] }}</td>
        <td class="{{ $isFailedSubject ? 'failed-grade-cell' : '' }}">{{ $row['grade'] }}</td>
        <td class="{{ $isFailedSubject ? 'failed-grade-cell' : '' }}">{{ $row['gradePoint'] }}</td>
    </tr>
@empty
    <tr><td colspan="7">{{ $emptyMessage }}</td></tr>
@endforelse

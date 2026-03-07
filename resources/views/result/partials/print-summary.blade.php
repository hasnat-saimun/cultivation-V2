@php
    $summaryLegendParts = $legendParts ?? [];
    if(count($summaryLegendParts) === 0 && isset($subjectLegend) && is_array($subjectLegend)){
        foreach($subjectLegend as $pair){
            $summaryLegendParts[] = ($pair['short'] ?? '').'='.($pair['full'] ?? '');
        }
    }
@endphp

<div class="container-fluid mb-2">
    <table class="summary-table">
        <tr>
            <th>Academic Year</th>
            <th>Class</th>
            <th>Group</th>
            <th>Exam</th>
            <th>Total Student</th>
            <th>Present</th>
            <th>Absent</th>
        </tr>
        <tr>
            <td>{{ $sessionName }}</td>
            <td>{{ $className }}</td>
            <td>{{ $groupName }}</td>
            <td>{{ $examName }}</td>
            <td>{{ $totalStudents }}</td>
            <td>{{ $presentCount }}</td>
            <td>{{ $absentCount }}</td>
        </tr>
    </table>
    @if(count($summaryLegendParts) > 0)
        <div class="legend-line">{{ implode(', ', $summaryLegendParts) }}</div>
    @endif
</div>
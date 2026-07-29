<div class="grading-table-wrap">
    <div class="grading-table-title">Grading Scale</div>
    <table class="grading-table">
        <thead>
            <tr>
                <th>Marks</th>
                <th>Grade</th>
                <th>Point</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gradeLegend as $grade)
                <tr>
                    <td>{{ $grade['range'] }}</td>
                    <td class="grading-letter">{{ $grade['grade'] }}</td>
                    <td class="grading-point">{{ $grade['point'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="grading-empty">No grading legend configured</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h5>Overall Result Summary</h5>
<table class="summary-table">
    <thead><tr><th>Total</th><th>Appeared</th><th>Absent / Incomplete</th><th>Pass</th><th>Fail</th><th>Incomplete</th><th>Pass %</th><th>Fail %</th><th>Incomplete %</th></tr></thead>
    <tbody><tr>
        <td>{{ $overallSummary['total'] }}</td><td>{{ $overallSummary['present'] }}</td><td>{{ $overallSummary['absent'] }}</td>
        <td>{{ $overallSummary['pass'] }}</td><td>{{ $overallSummary['fail'] }}</td><td>{{ $overallSummary['incomplete'] }}</td>
        <td>{{ number_format($overallSummary['passPercentage'] ?? 0, 2) }}</td><td>{{ number_format($overallSummary['failPercentage'] ?? 0, 2) }}</td><td>{{ number_format($overallSummary['incompletePercentage'] ?? 0, 2) }}</td>
    </tr></tbody>
</table>

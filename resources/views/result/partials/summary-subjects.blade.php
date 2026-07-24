<h5>Subject-wise Pass/Fail Summary</h5>
<table class="summary-table">
    <thead><tr><th>SL</th><th class="left">Subject</th><th>Appeared</th><th>Pass</th><th>Fail</th><th>Missing</th><th>Pass %</th><th>Fail %</th></tr></thead>
    <tbody>
    @forelse($subjectRows as $row)
        <tr><td>{{ $loop->iteration }}</td><td class="left">{{ $row['subjectName'] }}</td><td>{{ $row['appeared'] }}</td><td>{{ $row['pass'] }}</td><td>{{ $row['fail'] }}</td><td>{{ $row['missing'] }}</td><td>{{ number_format($row['passRate'], 2) }}</td><td>{{ number_format($row['failRate'], 2) }}</td></tr>
    @empty <tr><td colspan="8">No subject-wise data found.</td></tr>
    @endforelse
    </tbody>
</table>

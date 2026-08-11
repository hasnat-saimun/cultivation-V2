<div class="academic-attendance-block">
    <div class="academic-attendance-title">Academic Attendance</div>
    <table class="academic-attendance-table">
        <tr>
            <th>Working Days</th><td>{{ $attendance['workingDays'] ?? '—' }}</td>
            <th>Present</th><td>{{ $attendance['presentDays'] ?? '—' }}</td>
            <th>Absent</th><td>{{ $attendance['absentDays'] ?? '—' }}</td>
        </tr>
    </table>
</div>

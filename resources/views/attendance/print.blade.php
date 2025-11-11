<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>Attendance Printable</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#222;margin:20px;}
        h2{margin:0 0 10px;padding:0;font-size:18px;text-align:center;}
        table{width:100%;border-collapse:collapse;margin-top:10px;}
        th,td{border:1px solid #444;padding:4px 6px;}
        th{background:#f0f0f0;}
        .meta{margin-bottom:8px;font-size:12px;}
        .status-Present{color:#0a7500;font-weight:600;}
        .status-Absent{color:#b30000;font-weight:600;}
        .status-Late{color:#b36b00;font-weight:600;}
        .status-Excused{color:#0057b3;font-weight:600;}
        @media print {.no-print{display:none;}}
        .actions{margin:10px 0;text-align:right;}
        button{padding:6px 12px;margin-left:6px;}
    </style>
</head>
<body>
    <div class="actions no-print">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
    <h2>Attendance Sheet</h2>
    <div class="meta">
        <strong>Date:</strong> {{ $filters['date'] ?? 'All' }} &nbsp;|
        <strong>Class ID:</strong> {{ $filters['classId'] ?? 'All' }} &nbsp;|
        <strong>Session:</strong> {{ $filters['sessionId'] ?? 'All' }} &nbsp;|
        <strong>Section:</strong> {{ $filters['sectionId'] ?? 'All' }}
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Class</th>
                <th>Section</th>
                <th>Session</th>
                <th>Teacher</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $r)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $r->attendance_date }}</td>
                    <td>{{ $r->student_id }}</td>
                    <td>{{ $r->student ? trim(($r->student->fullName ?? '').' '.($r->student->sureName ?? '')) : '' }}</td>
                    <td class="status-{{ $r->status }}">{{ $r->status }}</td>
                    <td>{{ $r->class ? $r->class->className : $r->class_id }}</td>
                    <td>{{ $r->section ? $r->section->section : $r->section_id }}</td>
                    <td>{{ $r->session ? $r->session->session : $r->session_id }}</td>
                    <td>{{ $r->teacher ? $r->teacher->adminName : $r->teacher_id }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">No attendance records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:30px;font-size:11px;color:#777;">Generated at {{ date('Y-m-d H:i:s') }}</div>
</body>
</html>

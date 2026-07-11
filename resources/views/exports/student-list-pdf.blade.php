<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student List</title>
    <style>
        @page { margin: 16mm 10mm 12mm 10mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .header h2 {
            margin: 2px 0 0;
            font-size: 14px;
            font-weight: 600;
        }
        .meta {
            margin-top: 4px;
            font-size: 10.5px;
            color: #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        thead {
            display: table-header-group;
        }
        th, td {
            border: 1px solid #cfcfcf;
            padding: 6px 5px;
            word-wrap: break-word;
        }
        th {
            background: #f3f6fb;
            font-weight: 700;
            text-align: center;
        }
        td.center {
            text-align: center;
        }
        td.name {
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $instituteName ?? 'Institute' }}</h1>
        <h2>Student List</h2>
        <div class="meta">
            Generated: {{ optional($generatedAt ?? now())->format('d M Y, h:i A') }} | Total Students: {{ isset($students) ? $students->count() : 0 }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Serial</th>
                <th style="width: 12%;">Student ID</th>
                <th style="width: 24%;">Student Name</th>
                <th style="width: 12%;">Class</th>
                <th style="width: 10%;">Section</th>
                <th style="width: 14%;">Session</th>
                <th style="width: 8%;">Class Roll</th>
                <th style="width: 15%;">Department</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $st)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td class="center">{{ $st->stdId ?? 'N/A' }}</td>
                    <td class="name">{{ $st->student_name ?? 'N/A' }}</td>
                    <td class="center">{{ optional($st->classInfo)->className ?? 'N/A' }}</td>
                    <td class="center">{{ optional($st->sectionInfo)->section ?? 'N/A' }}</td>
                    <td class="center">{{ optional($st->sessionInfo)->session ?? 'N/A' }}</td>
                    <td class="center">{{ $st->rollNumber ?? 'N/A' }}</td>
                    <td class="center">{{ optional($st->departmentInfo)->departmentName ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

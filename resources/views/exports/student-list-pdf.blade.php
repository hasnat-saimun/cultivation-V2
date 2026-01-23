<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student List</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        thead { background: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Student List</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Class</th>
                <th>Section</th>
                <th>Session</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $st)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $st->stdId ?? $st->id }}</td>
                    <td>{{ $st->stdName }}</td>
                    <td>{{ $st->className }}</td>
                    <td>{{ $st->secName ?? $st->sectionName }}</td>
                    <td>{{ $st->sessName }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

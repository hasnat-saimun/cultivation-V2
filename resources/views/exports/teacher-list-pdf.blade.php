<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher List</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        thead { background: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Teacher List</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Teacher ID</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Join Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teachers as $i => $t)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $t->teacherId }}</td>
                    <td>{{ $t->firstName }} {{ $t->lastName }}</td>
                    <td>{{ $t->designation }}</td>
                    <td>{{ $t->email }}</td>
                    <td>{{ $t->mobile }}</td>
                    <td>{{ $t->joinDate }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

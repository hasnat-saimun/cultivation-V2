<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Staff List</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        thead { background: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Staff List</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Staff ID</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Join Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($staff as $i => $s)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $s->staffId }}</td>
                    <td>{{ $s->firstName }} {{ $s->lastName }}</td>
                    <td>{{ $s->designation }}</td>
                    <td>{{ $s->email }}</td>
                    <td>{{ $s->mobile }}</td>
                    <td>{{ $s->joinDate }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

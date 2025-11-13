<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>Attendance Printable</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#222;margin:20px;}
        h2{margin:0 0 10px;padding:0;font-size:18px;text-align:center;}
        .print-daily-wrapper{max-width:740px;margin:0 auto;background:#fff;padding:14px 18px;border:1px solid #dcdfe2;border-radius:6px;}
    table.print-daily{width:100% !important;border-collapse:collapse;margin:10px auto 0 auto;table-layout:auto;}
    @media (min-width:600px){table.print-daily{table-layout:fixed;}}
        table.print-daily th,table.print-daily td{border:1px solid #444;padding:5px 7px;font-size:12px;vertical-align:middle;}
        table.print-daily th{background:#f5f6f7;font-weight:600;}
        table.print-daily tbody tr:nth-child(even){background:#fafafa;}
        table.print-daily td.small-meta{font-size:11px;color:#666;}
        table.print-daily td.status-cell{font-weight:600;letter-spacing:.5px;}
        .legend{margin-top:8px;font-size:11px;}
        @media print{.no-print{display:none;} .print-daily-wrapper{box-shadow:none;border:1px solid #000;}}
        @media (max-width:600px){table.print-daily{width:100% !important;}}
    </style>
</head>
<body>
    <div class="actions no-print">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
    @include('attendance._printHeader')
    <h2 style="text-align:center;margin-top:10px;">Attendance Sheet</h2>
    @php
        $statusMap = ['Present' => 'P', 'Absent' => 'A', 'Late' => 'T', 'Excused' => 'E'];
        $totals = ['P'=>0,'A'=>0,'T'=>0,'E'=>0];
        foreach($rows as $rr){
            $code = $statusMap[$rr->status] ?? substr($rr->status,0,1);
            if(isset($totals[$code])){ $totals[$code]++; }
        }
    @endphp
    <div class="print-daily-wrapper">
        <table class="print-daily">
            <thead>
                <tr>
                    <th style="width:55px;">Sl</th>
                    <th style="width:95px;">Class Roll</th>
                    <th style="white-space:nowrap;">Student</th>
                    <th style="width:80px;text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $i => $r)
                @php $code = $statusMap[$r->status] ?? substr($r->status,0,1); @endphp
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $r->student && $r->student->rollNumber ? $r->student->rollNumber : '' }}</td>
                    <td>
                        @php $nm = $r->student ? trim(($r->student->fullName ?? '').' '.($r->student->sureName ?? '')) : ''; @endphp
                        {{ $nm }}
                        <div class="small-meta">ID: {{ $r->student_id }}</div>
                    </td>
                    <td class="status-cell" style="text-align:center;">{{ $code }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;">No attendance records found.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="legend"><strong>Legend:</strong> Present = P, Absent = A, Tardy = T, Excused = E</div>
        @if(count($rows))
    <table class="print-daily" style="margin-top:12px;">
            <thead>
                <tr>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Late</th>
                    <th>Excused</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align:center;font-weight:600;">{{ $totals['P'] }}</td>
                    <td style="text-align:center;font-weight:600;">{{ $totals['A'] }}</td>
                    <td style="text-align:center;font-weight:600;">{{ $totals['T'] }}</td>
                    <td style="text-align:center;font-weight:600;">{{ $totals['E'] }}</td>
                </tr>
            </tbody>
        </table>
        @endif
    </div>
    <div style="margin-top:30px;font-size:11px;color:#777;">Generated at {{ date('Y-m-d H:i:s') }}</div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Monthly Attendance Print</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#222;margin:18px;}
        table{width:100%;border-collapse:collapse;}
        th,td{border:1px solid #444;padding:3px 4px;font-size:10px;}
        th{background:#f5f5f5;}
        .weekend{background:#f8f8f8;}
        .legend{margin-top:8px;font-size:10px;}
        .header-block{margin-bottom:12px;}
        .sig-row{margin-top:40px;display:flex;justify-content:space-around;font-size:11px;}
        .sig{border-top:1px solid #222;padding-top:4px;min-width:220px;text-align:center;}
        @media print {.no-print{display:none;} body{margin:4mm;} }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:right;margin-bottom:8px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
    @include('attendance._printHeader')
    <h3 style="text-align:center;margin:6px 0;">Monthly Attendance Sheet ({{ date('F', mktime(0,0,0,$filters['month'],1)) }} {{ $filters['year'] }})</h3>
    <table>
        <thead>
            <tr>
                <th style="white-space:nowrap;">Student</th>
                @for($d=1;$d<=$daysInMonth;$d++)
                    @php $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk,['Sat','Sun']); @endphp
                    <th class="{{ $isWknd ? 'weekend' : '' }}" style="width:20px;text-align:center;">{{ $d }}</th>
                @endfor
                <th>P</th><th>A</th><th>T</th><th>E</th>
            </tr>
            <tr>
                <th></th>
                @for($d=1;$d<=$daysInMonth;$d++)
                    @php $wk = $weekdays[$d] ?? ''; $isWknd = in_array($wk,['Sat','Sun']); @endphp
                    <th class="{{ $isWknd ? 'weekend' : '' }}" style="text-align:center;">{{ substr($wk,0,2) }}</th>
                @endfor
                <th colspan="4"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $st)
                @php $s = $summary[$st->id] ?? ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0]; @endphp
                <tr>
                    <td style="white-space:nowrap;">{{ $st->rollNumber ? $st->rollNumber.'. ' : '' }}{{ trim(($st->fullName ?? '').' '.($st->sureName ?? '')) }}</td>
                    @for($d=1;$d<=$daysInMonth;$d++)
                        @php $code = $matrix[$st->id][$d] ?? ''; $wk = $weekdays[$d]; $isWknd = in_array($wk,['Sat','Sun']); @endphp
                        <td class="{{ $isWknd ? 'weekend' : '' }}" style="text-align:center;">{{ $code }}</td>
                    @endfor
                    <td>{{ $s['present'] }}</td>
                    <td>{{ $s['absent'] }}</td>
                    <td>{{ $s['late'] }}</td>
                    <td>{{ $s['excused'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="legend"><strong>Legend:</strong> P=Present, A=Absent, T=Late/Tardy, E=Excused</div>
    <h4 style="margin:16px 0 6px;">Daily Totals</h4>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                @for($d=1;$d<=$daysInMonth;$d++)
                    @php $wk = $weekdays[$d]; $isWknd = in_array($wk,['Sat','Sun']); @endphp
                    <th class="{{ $isWknd ? 'weekend' : '' }}" style="text-align:center;">{{ $d }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Present</strong></td>
                @for($d=1;$d<=$daysInMonth;$d++)<td style="text-align:center;">{{ $dayTotals['P'][$d] ?? 0 }}</td>@endfor
            </tr>
            <tr>
                <td><strong>Absent</strong></td>
                @for($d=1;$d<=$daysInMonth;$d++)<td style="text-align:center;">{{ $dayTotals['A'][$d] ?? 0 }}</td>@endfor
            </tr>
            <tr>
                <td><strong>Late</strong></td>
                @for($d=1;$d<=$daysInMonth;$d++)<td style="text-align:center;">{{ $dayTotals['T'][$d] ?? 0 }}</td>@endfor
            </tr>
            <tr>
                <td><strong>Excused</strong></td>
                @for($d=1;$d<=$daysInMonth;$d++)<td style="text-align:center;">{{ $dayTotals['E'][$d] ?? 0 }}</td>@endfor
            </tr>
        </tbody>
    </table>
    <div class="sig-row">
        <div class="sig">Class Teacher</div>
        <div class="sig">Principal</div>
    </div>
    <script>window.print();</script>
</body>
</html>

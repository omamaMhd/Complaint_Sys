<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background: #eee; }
    </style>
</head>
<body>

<h2>Complaint Statistics Report</h2>

<p>Total Complaints: {{ $stats['total Complaints'] }}</p>
<p>Today: {{ $stats['today'] }}</p>
<p>This Week: {{ $stats['this_week'] }}</p>

<h4>By Status</h4>
<table>
<tr><th>Status</th><th>Total</th></tr>
@foreach($stats['by_status'] as $row)
<tr>
    <td>{{ $row->status }}</td>
    <td>{{ $row->total }}</td>
</tr>
@endforeach
</table>

<h4>Most Busy Department</h4>
<p>{{ $stats['most_busy_department']->responsible_party ?? '-' }}</p>

</body>
</html>

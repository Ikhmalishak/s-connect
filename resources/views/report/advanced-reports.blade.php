<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Visitors Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2 style="text-align: center;">Visitors Report</h2>
    <h3 style="text-align: center;">Visitor Type Chart</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Visitor Name</th>
                <th>Company</th>
                <th>Type</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($visitors as $i => $visitor)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $visitor->visitor_name }}</td>
                    <td>{{ $visitor->visitor_company }}</td>
                    <td>{{ $visitor->visitor_type }}</td>
                    <td>{{ $visitor->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
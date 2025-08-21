<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
    </style>
</head>
<body>
    <h2>Visitor Report</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Visitor</th>
                <th>Company</th>
                <th>Date</th>
                <th>Purpose</th>
            </tr>
        </thead>
        <tbody>
            @foreach($visitors as $i => $visitor)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $visitor->visitor_name }}</td>
                    <td>{{ $visitor->visitor_company }}</td>
                    <td>{{ $visitor->date }}</td>
                    <td>{{ $visitor->purpose }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

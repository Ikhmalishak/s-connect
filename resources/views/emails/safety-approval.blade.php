<!DOCTYPE html>
<html>
<head>
    <title>EHS Audit - Corrective Action Submitted</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info-box strong {
            display: inline-block;
            min-width: 120px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>EHS Audit - Corrective Action Submitted</h1>
        </div>

        <div class="content">
            <p>Hi <strong>{{ $approver->name }}</strong>,</p>

            <p>A corrective action has been submitted by <strong>{{ $action->submitter->name ?? 'Unknown' }}</strong> for the following audit inspection and is pending your review:</p>

            <div class="info-box">
                <p><strong>Site:</strong> {{ $session->site->name ?? 'N/A' }}</p>
                <p><strong>Department:</strong> {{ $session->department->name ?? 'N/A' }}</p>
                <p><strong>Audit Type:</strong> {{ $session->auditType->name ?? 'N/A' }}</p>
                <p><strong>Inspection Date:</strong> {{ $session->date }}</p>
                <p><strong>Status:</strong> <span class="badge badge-pending">Pending Review</span></p>
            </div>

            <h3>Corrective Action Details</h3>
            <div class="info-box">
                <p><strong>Description:</strong></p>
                <p>{{ $action->description ?? 'No description provided.' }}</p>
                <p><strong>Submitted At:</strong> {{ $action->submitted_at ? $action->submitted_at->format('d M Y H:i') : 'N/A' }}</p>
            </div>

            <p>Please review the corrective action and approve or reject it through the system.</p>

            <p style="text-align: center;">
                <a href="{{ $url }}" class="btn">Review Corrective Action</a>
            </p>

            <p style="margin-top: 20px; font-size: 14px; color: #666;">
                Thank you,<br>
                EHS Management System
            </p>
        </div>

        <div class="footer">
            <p>This is an automated notification from the EHS Management System.</p>
            <p>&copy; {{ date('Y') }} EHS Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
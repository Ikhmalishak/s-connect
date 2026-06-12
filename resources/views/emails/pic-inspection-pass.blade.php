<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: linear-gradient(135deg, #059669, #10b981); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; }
        .content { padding: 30px; }
        .badge { display: inline-block; background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table td { padding: 10px 15px; border-bottom: 1px solid #e5e7eb; }
        .info-table td:first-child { font-weight: 600; color: #6b7280; width: 40%; }
        .footer { background: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { margin: 5px 0; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Safety Inspection PASSED</h1>
            <p>EHS Management System Notification</p>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $pic->name }}</strong>,</p>

            <p>The safety inspection has been completed and <strong class="badge">ALL ITEMS PASSED</strong>. No corrective actions are required.</p>

            <table class="info-table">
                <tr>
                    <td>Inspection Type</td>
                    <td>{{ $session->auditType?->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Site</td>
                    <td>{{ $session->site?->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Department</td>
                    <td>{{ $session->department?->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Inspection Date</td>
                    <td>{{ $session->date }}</td>
                </tr>
                <tr>
                    <td>Submitted By</td>
                    <td>{{ $session->user?->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><span class="badge">PASSED</span></td>
                </tr>
            </table>

            <p>You can view the full inspection details in the EHS Management System dashboard.</p>
        </div>
        <div class="footer">
            <p>This is an automated notification from the EHS Management System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
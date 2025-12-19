<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Container Rejected</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #dc2626;">Container Rejected</h2>

        <p>Dear Team,</p>

        <p>We regret to inform you that the container has been rejected during the approval process.</p>

        <div style="background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #dc2626;">Container Details:</h3>
            <p><strong>Transport Type:</strong> {{ $container->transport_type }}</p>
            <p><strong>Transport Number:</strong> {{ $container->transport_number }}</p>
            <p><strong>SKU Number:</strong> {{ $container->sku_number }}</p>
            <p><strong>Forwarder:</strong> {{ $container->forwarder }}</p>
            <p><strong>Hauler:</strong> {{ $container->hauler }}</p>
            <p><strong>Rejected At:</strong> {{ ucfirst(str_replace('_', ' ', $container->status)) }}</p>
        </div>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Rejection Details:</h3>
            <p><strong>Rejected By:</strong> {{ $approval->approver->name ?? 'System' }}</p>
            <p><strong>Department:</strong> {{ ucfirst($approval->department) }}</p>
            <p><strong>Remarks:</strong> {{ $approval->remarks ?: 'No remarks provided' }}</p>
        </div>

        <p>Please review the rejection reason and take appropriate action. You may need to create a new container request or address the issues identified.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                View Container Details
            </a>
        </div>

        <p style="color: #666; font-size: 14px;">
            This is an automated notification. Please do not reply to this email.
        </p>
    </div>
</body>
</html>

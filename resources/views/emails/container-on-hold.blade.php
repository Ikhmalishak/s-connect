<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Container Put On Hold</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #dc2626;">🚫 Container Put On Hold</h2>

        <p>Dear Team,</p>

        <p>A container has been put on hold by the Quality Department. All operations on this container have been paused until the hold is released.</p>

        <div style="background-color: #fef2f2; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc2626;">
            <h3 style="margin-top: 0; color: #dc2626;">Hold Details:</h3>
            <p><strong>Reason:</strong> {{ $container->hold_reason }}</p>
            <p><strong>Held by:</strong> {{ $user->name }}</p>
            <p><strong>Held on:</strong> {{ $container->hold_at ? $container->hold_at->format('M j, Y g:i A') : 'N/A' }}</p>
        </div>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #059669;">Container Details:</h3>
            <p><strong>Transport Type:</strong> {{ $container->transport_type }}</p>
            <p><strong>Transport Number:</strong> {{ $container->transport_number }}</p>
            <p><strong>SKU Number:</strong> {{ $container->sku_number }}</p>
            <p><strong>Forwarder:</strong> {{ $container->forwarder }}</p>
            <p><strong>Hauler:</strong> {{ $container->hauler }}</p>
            <p><strong>Current Stage:</strong> {{ $container->stage ? str_replace('_', ' ', $container->stage) : 'N/A' }}</p>
        </div>

        <p><strong>Important:</strong> No further actions can be taken on this container until the Quality Department releases the hold.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                View Container Details
            </a>
        </div>

        <p style="color: #666; font-size: 14px;">
            This is an automated notification from the Container Inspection Management System.<br>
            Please do not reply to this email.
        </p>
    </div>
</body>
</html>

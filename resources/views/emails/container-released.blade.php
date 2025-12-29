<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Container Released from Hold</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #059669;">✅ Container Released from Hold</h2>

        <p>Dear Team,</p>

        <p>A container that was previously on hold has been released by the Quality Department. The container is now available for processing and all operations can resume.</p>

        <div style="background-color: #f0fdf4; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #059669;">
            <h3 style="margin-top: 0; color: #059669;">Release Details:</h3>
            <p><strong>Released by:</strong> {{ $user->name }}</p>
            <p><strong>Released on:</strong> {{ now()->format('M j, Y g:i A') }}</p>
            <p><strong>Previous Hold Reason:</strong> {{ $container->hold_reason }}</p>
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

        <p><strong>Next Steps:</strong> You can now continue with the normal container processing workflow.</p>

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

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Container Ready for Onboarding</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #059669;">Container Ready for Onboarding</h2>

        <p>Dear Security Team,</p>

        <p>A container has been approved by all departments and is now ready for onboarding procedures.</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #059669;">Container Details:</h3>
            <p><strong>Transport Type:</strong> {{ $container->transport_type }}</p>
            <p><strong>Transport Number:</strong> {{ $container->transport_number }}</p>
            <p><strong>SKU Number:</strong> {{ $container->sku_number }}</p>
            <p><strong>Forwarder:</strong> {{ $container->forwarder }}</p>
            <p><strong>Hauler:</strong> {{ $container->hauler }}</p>
        </div>

        <p>Please complete the onboarding process and update the container status.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                View Container Dashboard
            </a>
        </div>

        <p style="color: #666; font-size: 14px;">
            This is an automated notification. Please do not reply to this email.
        </p>
    </div>
</body>
</html>

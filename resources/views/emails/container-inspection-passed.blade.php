<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Container Inspection Passed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">Container Inspection Passed - Management Approval Required</h2>

        <p>Dear Management Team,</p>

        <p>A container has successfully passed inspection and requires your approval before proceeding with loading.</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2563eb;">Container Details:</h3>
            <p><strong>Transport Type:</strong> {{ $container->transport_type }}</p>
            <p><strong>Transport Number:</strong> {{ $container->transport_number }}</p>
            <p><strong>SKU Number:</strong> {{ $container->sku_number }}</p>
            <p><strong>Forwarder:</strong> {{ $container->forwarder }}</p>
            <p><strong>Hauler:</strong> {{ $container->hauler }}</p>
        </div>

        <p>Please review and approve this container to allow loading operations to proceed.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $approve_url }}" style="background-color: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
                Approve Container
            </a>
            <a href="{{ $url }}" style="background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Review Container
            </a>
        </div>

        <p style="color: #666; font-size: 14px;">
            This is an automated notification. Please do not reply to this email.
        </p>
    </div>
</body>
</html>

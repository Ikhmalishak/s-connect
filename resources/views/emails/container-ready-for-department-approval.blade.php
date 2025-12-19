<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Container Ready for {{ ucfirst($department) }} Approval</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #7c3aed;">Container Ready for {{ ucfirst($department) }} Department {{ $approvalType === 'inspection' ? 'Inspection' : 'Loading' }} Approval</h2>

        <p>Dear {{ ucfirst($department) }} Team,</p>

        @if($approvalType === 'inspection')
            <p>A container has completed inspection and requires your department's approval before proceeding to loading.</p>
        @else
            <p>A container has completed loading and requires your department's approval before security checking.</p>
        @endif

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #7c3aed;">Container Details:</h3>
            <p><strong>Transport Type:</strong> {{ $container->transport_type }}</p>
            <p><strong>Transport Number:</strong> {{ $container->transport_number }}</p>
            <p><strong>SKU Number:</strong> {{ $container->sku_number }}</p>
            <p><strong>Forwarder:</strong> {{ $container->forwarder }}</p>
            <p><strong>Hauler:</strong> {{ $container->hauler }}</p>
        </div>

        @if($approvalType === 'inspection')
            <p>Please review the inspection results and provide your approval.</p>
        @else
            <p>Please review the loading documentation and provide your approval.</p>
        @endif

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #7c3aed; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Review & Approve
            </a>
        </div>

        <p style="color: #666; font-size: 14px;">
            This is an automated notification. Please do not reply to this email.
        </p>
    </div>
</body>
</html>

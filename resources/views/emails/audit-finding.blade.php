<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>EHS Audit Finding</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">

        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="color: #dc2626;">🚨 EHS Audit Finding</h2>
        </div>

        <p>Dear <strong>{{ $pic->name }}</strong>,</p>

        <p>An EHS audit inspection has been completed with findings that require your attention as the assigned Person In Charge.</p>

        <div style="background-color: #fef2f2; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc2626;">
            <h3 style="margin-top: 0; color: #dc2626;">Audit Session Summary</h3>
            <p><strong>Session ID:</strong> #{{ $session->id }}</p>
            <p><strong>Date:</strong> {{ $session->date }}</p>
            <p><strong>Site:</strong> {{ $session->site?->name ?? 'N/A' }}</p>
            <p><strong>Department:</strong> {{ $session->department?->name ?? 'N/A' }}</p>
            <p><strong>Audit Type:</strong> {{ $session->auditType?->name ?? 'N/A' }}</p>
            <p><strong>Submitted by:</strong> {{ $session->user?->name ?? 'N/A' }}</p>
        </div>

        @if($session->remarks)
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #374151;">General Remarks</h3>
                <p>{{ $session->remarks }}</p>
            </div>
        @endif

        <div style="background-color: #fff7ed; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #f97316;">
            <h3 style="margin-top: 0; color: #f97316;">Failed Items ({{ count($failedItems) }})</h3>

            @foreach($failedItems as $index => $item)
                <div style="padding: 12px; margin-bottom: 10px; background-color: white; border-radius: 4px; border: 1px solid #fee2e2;">
                    <p style="margin: 0 0 5px 0; font-weight: bold; color: #dc2626;">
                        {{ $index + 1 }}. {{ $item['question_text'] ?? 'Unknown Question' }}
                    </p>
                    @if(!empty($item['remarks']))
                        <p style="margin: 0; color: #6b7280; font-size: 14px;">
                            <strong>Remark:</strong> {{ $item['remarks'] }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}"
               style="background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                View Full Report
            </a>
        </div>

        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            This is an automated notification from the EHS Management System.<br>
            Please do not reply to this email.
        </p>
    </div>
</body>
</html>
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Approval;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use GuzzleHttp\Client;

class ApprovalController extends Controller
{
    // 1️⃣ Create approval
    // public function create(Request $request)
    // {
    //     $validated = $request->validate([
    //         'approvalId' => 'required|string|unique:approvals,approval_id',
    //         'title' => 'required|string',
    //         'approver' => 'required|email',
    //         'amount' => 'required|numeric'
    //     ]);

    //     $approval = Approval::create([
    //         'approval_id' => $validated['approvalId'],
    //         'title' => $validated['title'],
    //         'approver' => $validated['approver'],
    //         'amount' => $validated['amount']
    //     ]);

    //     // Send Teams notification
    //     $this->sendToTeams($approval);

    //     return response()->json(['status' => 'PENDING', 'approval' => $approval]);
    // }

    public function create(Request $request)
{
    // Hardcoded test approval
    $approval = Approval::create([
        'approval_id' => 'TEST-006',
        'title' => 'Test Purchase Request',
        'approver' => 'manager@company.com',
        'amount' => 3500
    ]);

    // Send Teams notification
    $this->sendToTeams($approval);

    return response()->json([
        'status' => 'PENDING',
        'approval' => $approval,
        'message' => 'Approval created and Teams notification sent!'
    ]);
}

    // 2️⃣ Approve
    public function approve($id, Request $request)
    {
        $approval = Approval::where('approval_id', $id)->firstOrFail();

        if ($approval->status !== 'PENDING') {
            return response()->json(['error' => 'Already processed'], 400);
        }

        $userEmail = $request->input('user_email'); // replace with proper OAuth validation
        if ($userEmail !== $approval->approver) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $approval->update([
            'status' => 'APPROVED',
            'approved_by' => $userEmail,
            'approved_at' => now()
        ]);

        return response()->json(['status' => 'APPROVED']);
    }

    // 3️⃣ Reject
    public function reject($id, Request $request)
    {
        $approval = Approval::where('approval_id', $id)->firstOrFail();

        if ($approval->status !== 'PENDING') {
            return response()->json(['error' => 'Already processed'], 400);
        }

        $userEmail = $request->input('user_email'); // replace with proper OAuth validation
        if ($userEmail !== $approval->approver) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $approval->update([
            'status' => 'REJECTED',
            'approved_by' => $userEmail,
            'approved_at' => now()
        ]);

        return response()->json(['status' => 'REJECTED']);
    }

    // 4️⃣ Get status
    public function status($id)
    {
        $approval = Approval::where('approval_id', $id)->firstOrFail();
        return response()->json([
            'approvalId' => $approval->approval_id,
            'status' => $approval->status,
            'approved_by' => $approval->approved_by,
            'approved_at' => $approval->approved_at
        ]);
    }

    // Teams notification via Graph SDK
    private function sendToTeams(Approval $approval)
    {
        // 1. Get access token (client credentials)
        $tenantId = env('AZURE_TENANT_ID');
        $clientId = env('AZURE_CLIENT_ID');
        $clientSecret = env('AZURE_CLIENT_SECRET');

        $guzzle = new Client();
        $url = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";
        $response = $guzzle->post($url, [
            'form_params' => [
                'client_id' => $clientId,
                'scope' => 'https://graph.microsoft.com/.default',
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]
        ]);
        $accessToken = json_decode($response->getBody()->getContents(), true)['access_token'];

        $graph = new Graph();
        $graph->setAccessToken($accessToken);

        // 2. Build Adaptive Card JSON (simplified)
        $cardJson = [
            'type' => 'AdaptiveCard',
            'body' => [
                ['type' => 'TextBlock', 'text' => "Approval request: {$approval->title} - RM {$approval->amount}"]
            ],
            'actions' => [
                [
                    'type' => 'Action.Http',
                    'title' => 'Approve',
                    'method' => 'POST',
                    'url' => route('approvals.approve', $approval->approval_id),
                    'headers' => [
                        ['name' => 'Content-Type', 'value' => 'application/json'],
                    ]
                ],
                [
                    'type' => 'Action.Http',
                    'title' => 'Reject',
                    'method' => 'POST',
                    'url' => route('approvals.reject', $approval->approval_id),
                    'headers' => [
                        ['name' => 'Content-Type', 'value' => 'application/json'],
                    ]
                ]
            ],
            '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
            'version' => '1.3'
        ];

        // 3. Send to Teams channel
        $teamId = env('TEAMS_TEAM_ID');
        $channelId = env('TEAMS_CHANNEL_ID');

        $graph->createRequest('POST', "/teams/$teamId/channels/$channelId/messages")
              ->attachBody(['body' => ['contentType' => 'html', 'content' => json_encode($cardJson)]])
              ->execute();
    }
}

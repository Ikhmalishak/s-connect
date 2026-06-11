<?php

namespace App\Http\Controllers\ManageSafety;

use App\Http\Controllers\Controller;
use App\Mail\NotifySafetyTeamApproval;
use App\Models\AuditFindingAction;
use App\Models\AuditSession;
use App\Models\AuditAnswer;
use App\Enums\AuditAnswerEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FindingActionController extends Controller
{
    /**
     * Show the safety approvals dashboard page
     */
    public function getApprovalsPage()
    {
        return Inertia::render('ManageSafety/SafetyApprovals');
    }

    /**
     * Get all failed findings with their corrective actions for a session
     */
    public function getFindings($sessionId)
    {
        $session = AuditSession::with([
            'answers.question.section',
            'answers.findingAction.submitter',
            'answers.findingAction.reviewer',
            'site',
            'department',
            'auditType',
            'user',
        ])->findOrFail($sessionId);

        // Get failed answers (answer = 0) with their finding actions
        $failedAnswers = $session->answers->filter(function ($answer) {
            return $answer->answer === AuditAnswerEnum::NO;
        })->values()->map(function ($answer) {
            // Add finding action data to each answer
            $answer->finding_action = $answer->findingAction;
            return $answer;
        });

        return response()->json([
            'session' => $session,
            'failed_answers' => $failedAnswers,
        ]);
    }

    /**
     * PIC submits corrective actions for ALL failed findings in a session
     * Each failed answer gets its own corrective action entry
     */
    public function submitAction(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer|exists:audit_sessions,id',
            'actions' => 'required|array|min:1',
            'actions.*.answer_id' => 'required|integer|exists:audit_answers,id',
            'actions.*.description' => 'required|string|max:5000',
            'actions.*.corrective_evidence' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $session = AuditSession::findOrFail($request->session_id);

        // Verify the session is in 'failed' status
        if ($session->status !== 'failed') {
            return response()->json([
                'message' => 'Only failed inspections can have corrective actions submitted.',
            ], 422);
        }

        // Check for existing pending actions for these answers
        $answerIds = collect($request->actions)->pluck('answer_id');
        $existingPending = AuditFindingAction::whereIn('audit_answer_id', $answerIds)
            ->where('status', 'pending_review')
            ->exists();

        if ($existingPending) {
            return response()->json([
                'message' => 'One or more findings already have a corrective action pending review.',
            ], 422);
        }

        $savedActions = [];

        foreach ($request->actions as $index => $actionData) {
            $answerId = $actionData['answer_id'];
            $description = $actionData['description'];

            // Handle evidence upload for this specific finding
            $evidencePath = null;
            $photoKey = "actions.{$index}.corrective_evidence";
            if ($request->hasFile($photoKey)) {
                $file = $request->file($photoKey);
                $evidencePath = $file->store('corrective-evidence', 'public');
            }

            // Check if there's a previously rejected action for this answer
            $action = AuditFindingAction::where('audit_answer_id', $answerId)
                ->where('status', 'rejected')
                ->latest()
                ->first();

            if ($action) {
                $action->update([
                    'description' => $description,
                    'corrective_evidence' => $evidencePath ?? $action->corrective_evidence,
                    'status' => 'pending_review',
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                ]);
            } else {
                $action = AuditFindingAction::create([
                    'audit_answer_id' => $answerId,
                    'description' => $description,
                    'corrective_evidence' => $evidencePath,
                    'status' => 'pending_review',
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                ]);
            }

            $action->load(['submitter', 'answer.question']);
            $savedActions[] = $action;
        }

        // Change session status
        $session->update(['status' => 'corrective_action_submitted']);

        // Notify all users with safety.approve permission
        try {
            $approvers = \App\Models\User::permission('safety.approve')->get();
            foreach ($approvers as $approver) {
                if ($approver->email) {
                    Mail::to($approver->email)
                        ->send(new NotifySafetyTeamApproval($session, $savedActions[0] ?? null, $approver));
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send approval notification: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Corrective actions submitted successfully! Pending safety team review.',
            'data' => $savedActions,
        ], 201);
    }

    /**
     * Get all pending approvals for safety team
     */
    public function getPendingApprovals(Request $request)
    {
        $query = AuditSession::with([
            'auditType',
            'site',
            'department',
            'user',
            'answers.findingAction.submitter',
            'answers.findingAction.reviewer',
        ])->whereIn('status', ['corrective_action_submitted', 'finding_closed'])
            ->whereHas('answers.findingAction');

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->whereHas('answers.findingAction', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('date', 'like', "%{$search}%")
                    ->orWhereHas('auditType', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    })->orWhereHas('site', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $sessions = $query->orderBy('updated_at', 'desc')->get();

        // Attach a summary status for the session from its findings
        $sessions->each(function ($session) {
            $actions = $session->answers->pluck('findingAction')->filter();
            if ($actions->isEmpty()) {
                $session->approval_status = 'no_actions';
            } elseif ($actions->every(fn($a) => $a->status === 'approved')) {
                $session->approval_status = 'approved';
            } elseif ($actions->contains('status', 'pending_review') && !$actions->contains('status', 'rejected')) {
                $session->approval_status = 'pending_review';
            } elseif ($actions->every(fn($a) => $a->status === 'rejected')) {
                $session->approval_status = 'rejected';
            } else {
                $session->approval_status = 'mixed';
            }
        });

        return response()->json([
            'data' => $sessions,
        ]);
    }

    /**
     * Get approval stats for the safety team dashboard
     */
    public function getApprovalStats()
    {
        $pendingReview = AuditFindingAction::where('status', 'pending_review')->count();
        $approved = AuditFindingAction::where('status', 'approved')->count();
        $rejected = AuditFindingAction::where('status', 'rejected')->count();

        return response()->json([
            'data' => [
                'pending_review' => $pendingReview,
                'approved' => $approved,
                'rejected' => $rejected,
            ],
        ]);
    }

    /**
     * Safety team approves ALL pending corrective actions for a session
     */
    public function approveAction($sessionId)
    {
        $session = AuditSession::findOrFail($sessionId);

        if ($session->status !== 'corrective_action_submitted') {
            return response()->json([
                'message' => 'This session is not pending review.',
            ], 422);
        }

        // Approve all pending actions for this session
        $pendingActions = AuditFindingAction::whereIn('audit_answer_id', 
            $session->answers()->where('answer', AuditAnswerEnum::NO)->pluck('id')
        )->where('status', 'pending_review')->get();

        foreach ($pendingActions as $action) {
            $action->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        }

        $session->update(['status' => 'finding_closed']);

        return response()->json([
            'message' => 'All corrective actions approved successfully! Findings closed.',
        ]);
    }

    /**
     * Safety team rejects ALL pending corrective actions for a session
     */
    public function rejectAction(Request $request, $sessionId)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $session = AuditSession::findOrFail($sessionId);

        if ($session->status !== 'corrective_action_submitted') {
            return response()->json([
                'message' => 'This session is not pending review.',
            ], 422);
        }

        // Reject all pending actions for this session
        $pendingActions = AuditFindingAction::whereIn('audit_answer_id', 
            $session->answers()->where('answer', AuditAnswerEnum::NO)->pluck('id')
        )->where('status', 'pending_review')->get();

        foreach ($pendingActions as $action) {
            $action->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);
        }

        $session->update(['status' => 'failed']);

        return response()->json([
            'message' => 'Corrective actions rejected. PIC needs to resubmit.',
        ]);
    }
}
<?php

namespace App\Http\Controllers\ManageSafety;

use App\Enums\AuditAnswerEnum;
use App\Http\Controllers\Controller;
use App\Mail\NotifyAuditFinding;
use App\Models\AuditAnswer;
use App\Models\AuditPic;
use App\Models\AuditSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AuditSessionController extends Controller
{
    //return dashboard
    public function getDashboard()
    {
        return Inertia::render('ManageSafety/Dashboard');
    }

    public function getAllSessions(Request $request)
    {
        $query = AuditSession::with('answers.question.section', 'auditType', 'site', 'user', 'department');

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by audit type name or date
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('date', 'like', "%{$search}%")
                  ->orWhereHas('auditType', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Limit results
        $limit = $request->filled('limit') ? (int) $request->limit : 50;
        $sessions = $query->orderBy('created_at', 'desc')->limit($limit)->get();

        return response()->json([
            'session' => $sessions,
        ]);
    }

    public function submitAudit(Request $request)
    {
        $request->validate([
            'audit_type_id' => 'required|integer|exists:audit_types,id',
            'department_id' => 'required|integer|exists:departments,id',
            'site_id' => 'required|integer|exists:sites,id',
            'answers' => 'required|json',
        ]);

        $answers = json_decode($request->input('answers'), true);

        if (!is_array($answers) || empty($answers)) {
            return response()->json([
                'message' => 'At least one answer is required.',
            ], 422);
        }

        // Create the audit session
        $session = AuditSession::create([
            'audit_type_id' => $request->audit_type_id,
            'department_id' => $request->department_id,
            'site_id' => $request->site_id,
            'user_id' => auth()->id(),
            'status' => 'submitted',
            'date' => now(),
        ]);

        foreach ($answers as $answerData) {
            // Skip unanswered/NA questions (null = not set, 2 = N/A)
            if (!isset($answerData['answer']) || $answerData['answer'] === null) {
                continue;
            }

            $answerValue = (int) $answerData['answer'];
            $questionId = $answerData['question_id'];

            // Handle photo upload if present (only for NO = failed)
            $photoPath = null;
            $photoKey = "photo_{$questionId}";

            if ($answerValue === 0 && $request->hasFile($photoKey)) {
                $file = $request->file($photoKey);
                $path = $file->store('audit-photos', 'public');
                $photoPath = $path;
            }

            // Create the answer record
            AuditAnswer::create([
                'audit_session_id' => $session->id,
                'audit_question_id' => $questionId,
                'answer' => $answerValue,
                'remarks' => $answerData['remarks'] ?? null,
                'photo_path' => $photoPath,
                'checked_at' => now(),
            ]);
        }

        // Reload with relationships for the response
        $session->load([
            'answers.question',
            'site',
            'department',
            'auditType',
            'user',
        ]);

        // Check for failed items (answer = 0)
        $failedItems = [];
        foreach ($session->answers as $answer) {
            if ($answer->answer === AuditAnswerEnum::NO) {
                $failedItems[] = [
                    'question_text' => $answer->question?->question_text ?? 'Unknown question',
                    'remarks' => $answer->remarks,
                ];
            }
        }

        // If there are findings, update status and notify PICs
        if (!empty($failedItems)) {
            $session->update(['status' => 'failed']);

            try {
                // Find PICs assigned to this site + department
                $pics = AuditPic::where('site_id', $session->site_id)
                    ->where('department_id', $session->department_id)
                    ->with('user')
                    ->get();

                    foreach ($pics as $pic) {
                    if ($pic->user && $pic->user->email) {
                        Mail::to($pic->user->email)
                            ->send(new NotifyAuditFinding($session, $failedItems, $pic->user));                        
                    }
                }
            } catch (\Exception $e) {
                // Log the error but don't break the submission
                \Illuminate\Support\Facades\Log::error('Failed to send audit finding email: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Inspection submitted successfully!',
            'session' => $session,
        ], 201);
    }
}
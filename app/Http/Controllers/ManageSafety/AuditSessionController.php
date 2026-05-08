<?php

namespace App\Http\Controllers\ManageSafety;

use App\Enums\AuditAnswerEnum;
use App\Http\Controllers\Controller;
use App\Models\AuditAnswer;
use App\Models\AuditSession;
use Illuminate\Http\Request;
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
        $query = AuditSession::with('answers.question', 'auditType');

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
        $session->load('answers.question');

        return response()->json([
            'message' => 'Inspection submitted successfully!',
            'session' => $session,
        ], 201);
    }
}
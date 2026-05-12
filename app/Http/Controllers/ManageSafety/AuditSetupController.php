<?php

namespace App\Http\Controllers\ManageSafety;

use App\Http\Controllers\Controller;
use App\Models\AuditType;
use App\Models\AuditSection;
use App\Models\AuditQuestion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditSetupController extends Controller
{
    // ─── Dashboard page ───────────────────────────────────────────────
    public function getDashboard()
    {
        return Inertia::render('ManageSafety/AuditSetupDashboard');
    }

    // ─── AUDIT TYPES ─────────────────────────────────────────────────
    public function getTypes()
    {
        $types = AuditType::withCount('sections')->orderBy('name')->get();
        return response()->json(['data' => $types]);
    }

    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:audit_types,name',
            'is_active' => 'boolean',
        ]);

        $type = AuditType::create($validated);

        return response()->json([
            'message' => 'Audit type created successfully!',
            'data' => $type,
        ], 201);
    }

    public function updateType(Request $request, AuditType $auditType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:audit_types,name,' . $auditType->id,
            'is_active' => 'boolean',
        ]);

        $auditType->update($validated);

        return response()->json([
            'message' => 'Audit type updated successfully!',
            'data' => $auditType->fresh(),
        ]);
    }

    public function deleteType(AuditType $auditType)
    {
        // Cascade will delete sections and questions
        $auditType->delete();

        return response()->json([
            'message' => 'Audit type deleted successfully!',
        ]);
    }

    // ─── SECTIONS ────────────────────────────────────────────────────
    public function getSections(Request $request)
    {
        $query = AuditSection::with('auditType');

        if ($request->filled('audit_type_id')) {
            $query->where('audit_type_id', $request->audit_type_id);
        }

        $sections = $query->withCount('questions')->orderBy('sort_order')->get();
        return response()->json(['data' => $sections]);
    }

    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'audit_type_id' => 'required|integer|exists:audit_types,id',
            'name' => 'required|string|max:255',
            'sort_order' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $section = AuditSection::create($validated);
        $section->load('auditType');

        return response()->json([
            'message' => 'Section created successfully!',
            'data' => $section,
        ], 201);
    }

    public function updateSection(Request $request, AuditSection $auditSection)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $auditSection->update($validated);
        $auditSection->load('auditType');

        return response()->json([
            'message' => 'Section updated successfully!',
            'data' => $auditSection->fresh()->load('auditType'),
        ]);
    }

    public function deleteSection(AuditSection $auditSection)
    {
        $auditSection->delete();

        return response()->json([
            'message' => 'Section deleted successfully!',
        ]);
    }

    // ─── QUESTIONS ───────────────────────────────────────────────────
    public function getQuestions(Request $request)
    {
        $query = AuditQuestion::with('section.auditType');

        if ($request->filled('section_id')) {
            $query->where('audit_section_id', $request->section_id);
        }

        if ($request->filled('audit_type_id')) {
            $query->whereHas('section', function ($q) use ($request) {
                $q->where('audit_type_id', $request->audit_type_id);
            });
        }

        $questions = $query->orderBy('sort_order')->get();
        return response()->json(['data' => $questions]);
    }

    public function storeQuestion(Request $request)
    {
        $validated = $request->validate([
            'audit_section_id' => 'required|integer|exists:audit_sections,id',
            'question_text' => 'required|string|max:500',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:1',
        ]);

        $question = AuditQuestion::create($validated);
        $question->load('section.auditType');

        return response()->json([
            'message' => 'Question created successfully!',
            'data' => $question,
        ], 201);
    }

    public function updateQuestion(Request $request, AuditQuestion $auditQuestion)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:500',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:1',
        ]);

        $auditQuestion->update($validated);
        $auditQuestion->load('section.auditType');

        return response()->json([
            'message' => 'Question updated successfully!',
            'data' => $auditQuestion->fresh()->load('section.auditType'),
        ]);
    }

    public function deleteQuestion(AuditQuestion $auditQuestion)
    {
        $auditQuestion->delete();

        return response()->json([
            'message' => 'Question deleted successfully!',
        ]);
    }
}
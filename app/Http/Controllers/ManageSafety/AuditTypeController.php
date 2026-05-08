<?php

namespace App\Http\Controllers\ManageSafety;

use App\Http\Controllers\Controller;
use App\Models\AuditSection;
use App\Models\AuditType;
use Illuminate\Http\Request;

class AuditTypeController extends Controller
{

    public function getAuditTypes()
    {
        return response()->json([
            'audit_types' => AuditType::all()
        ]);
    }

    public function getAllQuestions(Request $request)
    {
        $questions = AuditSection::where('audit_type_id', $request->audit_type_id)->with('questions')->get();

        return response()->json([
            'sections' => $questions
        ]);
    }
}

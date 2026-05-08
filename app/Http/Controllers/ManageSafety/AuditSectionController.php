<?php

namespace App\Http\Controllers\ManageSafety;

use App\Http\Controllers\Controller;
use App\Models\AuditSection;

class AuditSectionController extends Controller
{
    public function getAllQuestions()
    {
        $questions = AuditSection::with('questions')->get();
        
        return response()->json([
            'session' => $questions
        ]);
    }
}

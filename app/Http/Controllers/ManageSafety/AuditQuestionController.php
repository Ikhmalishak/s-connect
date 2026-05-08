<?php

namespace App\Http\Controllers\ManageSafety;

use App\Http\Controllers\Controller;
use App\Models\AuditQuestion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditQuestionController extends Controller
{
    public function getAllQuestions()
    {
        $questions = AuditQuestion::all();
        return response()->json([
            'session' => $questions
        ]);
    }
}

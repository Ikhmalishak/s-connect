<?php

namespace App\Http\Controllers;

use App\Models\VisitorStaffAcknowledgement;
use DB;
use Illuminate\Http\Request;

class VisitorStaffAcknowledgementController extends Controller
{
    public function getVisitorStaffAcknowledgementDetails(Request $request)
    {
        $id = $request->id;

        // Fetch the visitor staff acknowledgement along with its visitors
        $visitor_staff_acknowledgement = VisitorStaffAcknowledgement::with('visitors')->find($id);

        if (!$visitor_staff_acknowledgement) {
            return response()->json([
                'message' => 'Visitor Staff Acknowledgement not found'
            ], 404);
        }

        return response()->json([
            'visitor_staff_acknowledgement' => $visitor_staff_acknowledgement,
        ]);
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\VisitorStaffAcknowledgement;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class VisitorStaffAcknowledgementController extends Controller
{

    //function to call list of visitor that have been verified
    public function getAllVerifiedVisitor()
    {
        $date = Carbon::now();

        $verified = VisitorStaffAcknowledgement::with(['visitors.gatePass'])
            ->whereDate('created_at', $date)
            ->get();

        return response()->json([
            'message' => "success",
            'visitors' => $verified,
        ]);
    }
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

    //function for staff verify the visitor acknowledgement
    public function verifyVisitorAcknowledgement(Request $request)
    {

        $id = $request->visitor_ack_id;
        $staff_name = $request->staff_name;
        $staff_id = $request->staff_id;

        $ack = VisitorStaffAcknowledgement::find($id);

        if (!$ack) {
            return response()->json([
                'success' => false,
                'message' => 'Visitor acknowledgement not found',
            ], 404);
        }

        // Update acknowledgement record
        $ack->acknowledged_by = $staff_name;
        $ack->staff_id = $staff_id;
        $ack->acknowledged_at = now();
        $ack->save();


        return response()->json([
            'success' => true,
            'message' => 'Visitor verified successfully!',
            'data' => $ack,
        ]);
    }

}

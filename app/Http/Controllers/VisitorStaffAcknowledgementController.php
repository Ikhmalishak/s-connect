<?php

namespace App\Http\Controllers;

use App\Models\VisitorStaffAcknowledgement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Mpdf\Mpdf;

class VisitorStaffAcknowledgementController extends Controller
{

    //function to call list of visitor that have been verified
    public function getAllVerifiedVisitor()
    {
        $date = Carbon::now();

        $verified = VisitorStaffAcknowledgement::with(['visitors.gatePass'])
            ->whereNotNull('acknowledged_at')
            ->whereDate('created_at', $date)
            ->get();

        return response()->json([
            'message' => "success",
            'visitors' => $verified,
        ]);
    }
    public function getVisitorStaffAcknowledgementDetails(Request $request)
    {
        $ack_number = $request->ack_number;

        // Fetch the visitor staff acknowledgement along with its visitors
        $visitor_staff_acknowledgement = VisitorStaffAcknowledgement::with('visitors')->where('ack_number', $ack_number)->first();

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
        $ack_number = $request->ack_number;
        $staff_name = $request->staff_name;
        $staff_id = $request->staff_id;

        $ack = VisitorStaffAcknowledgement::where('ack_number', $ack_number)->first();

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

    //function to reprint the sticker if missing
    public function reprintVisitorSticker($ack_number)
    {
        $ack = VisitorStaffAcknowledgement::with('visitors')->where('ack_number', $ack_number)->first();

        if (!$ack) {
            return response()->json([
                'success' => false,
                'message' => 'Visitor acknowledgement not found',
            ], 404);
        }

        // count how many visitors linked to this acknowledgement
        $totalPax = $ack->visitors->count();

        // call your existing printSticker method
        $printResult = $this->printSticker($ack->id, $totalPax, $ack->ack_number);

        return response()->json([
            'success' => true,
            'message' => 'Sticker reprinted successfully',
            'data' => $ack,
            'print_result' => $printResult, // to debug the lp command output
        ]);
    }


    public function printSticker($ackId, $totalPax, $ackNumber)
    {
        $qrPath = storage_path("app/public/qr_ack_{$ackId}.png");
        QrCode::format('png')->size(360)->margin(0)->generate($ackNumber, $qrPath);

        $html = view('sticker', [
            'qr' => base64_encode(file_get_contents($qrPath)),
            'logo' => base64_encode(file_get_contents(public_path('assets/ss3.png'))),
            'ack_id' => $ackId,
            'total_pax' => $totalPax,
            'ack_number' => $ackNumber,
        ])->render();

        $pdfPath = storage_path("app/public/sticker_ack_{$ackId}.pdf");
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [62, 30], // mm
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($pdfPath, 'F');

        $printerName = "Brother_QL_820NWB";
        $cmd = "lp -d {$printerName} -o PageSize=Custom.62x30mm -o print-scaling=none -o CutMedia=Auto " . escapeshellarg($pdfPath);
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json(['status' => 'error', 'output' => $output], 500);
        }

        return response()->json(['status' => 'success', 'output' => $output]);
    }
}

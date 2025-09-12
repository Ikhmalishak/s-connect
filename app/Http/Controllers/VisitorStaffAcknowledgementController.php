<?php

namespace App\Http\Controllers;

use App\Models\VisitorStaffAcknowledgement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Mpdf\Mpdf;
use Illuminate\Support\Str;

class VisitorStaffAcknowledgementController extends Controller
{

public function getAllVerifiedVisitor(Request $request)
{
    $limit = $request->input('limit', 25);
    $keyword = $request->input('keyword');
    $date = Carbon::now()->toDateString(); // date only

    $query = VisitorStaffAcknowledgement::with(['visitors.gatePass'])
        ->whereNotNull('acknowledged_at')
        ->whereDate('created_at', $date);

    if ($keyword) {
        $query->where(function ($q) use ($keyword) {
            $q->where('acknowledged_by', 'LIKE', "%{$keyword}%")
              ->orWhere('staff_id', 'LIKE', "%{$keyword}%")
              ->orWhere('acknowledged_by_security', 'LIKE', "%{$keyword}%")
              ->orWhere('ack_number', 'LIKE', "%{$keyword}%")
              ->orWhereHas('visitors', function ($sub) use ($keyword) {
                  $sub->where('visitor_name', 'LIKE', "%{$keyword}%")
                      ->orWhere('visitor_company', 'LIKE', "%{$keyword}%")
                      ->orWhere('purpose', 'LIKE', "%{$keyword}%")
                      ->orWhereHas('gatePass', function ($gp) use ($keyword) {
                          $gp->where('pass_number', 'LIKE', "%{$keyword}%");
                      });
              });
        });
    }

    $verified = $query->orderByDesc('acknowledged_at')
        ->limit($limit)
        ->get();

    // Post-process: when keyword present, filter children visitors
    if ($keyword) {
        $lower = Str::lower($keyword);

        foreach ($verified as $ack) {
            // Check if ack-level fields match the keyword
            $ackLevelMatched = false;
            foreach (['acknowledged_by', 'staff_id', 'acknowledged_by_security', 'ack_number'] as $field) {
                if (Str::contains(Str::lower($ack->$field ?? ''), $lower)) {
                    $ackLevelMatched = true;
                    break;
                }
            }

            if (! $ackLevelMatched) {
                // Keep only visitors that match the keyword (by name/company/purpose/pass)
                $filtered = $ack->visitors->filter(function ($v) use ($lower) {
                    if (Str::contains(Str::lower($v->visitor_name ?? ''), $lower)) return true;
                    if (Str::contains(Str::lower($v->visitor_company ?? ''), $lower)) return true;
                    if (Str::contains(Str::lower($v->purpose ?? ''), $lower)) return true;
                    if ($v->gatePass && Str::contains(Str::lower($v->gatePass->pass_number ?? ''), $lower)) return true;
                    return false;
                })->values();

                // Replace the relation so the response only contains matching visitor rows
                $ack->setRelation('visitors', $filtered);
            }
            // if ack-level matched, keep all visitors as-is
        }
    }

    // recompute total after filtering
    $totalVisitors = $verified->pluck('visitors')->flatten(1)->count();

    return response()->json([
        'message' => "success",
        'visitors' => $verified,
        'total_visitors' => $totalVisitors,
    ]);
}

    public function getVisitorStaffAcknowledgementDetails(Request $request)
    {
        $ack_number = $request->ack_number;

        $visitor_staff_acknowledgement = VisitorStaffAcknowledgement::with('visitors')
            ->where('ack_number', $ack_number)
            ->first();

        if (!is_null($visitor_staff_acknowledgement->acknowledged_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Visitor Staff Acknowledgement has already been acknowledged.'
            ], 200);
        }

        if (!$visitor_staff_acknowledgement) {
            return response()->json([
                'success' => false,
                'message' => 'Visitor Staff Acknowledgement not found'
            ], 200); // <-- still 200
        }

        $notCheckedIn = $visitor_staff_acknowledgement->visitors->whereNull('time_in');

        if ($notCheckedIn->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'All visitors must check in first before acknowledgement.',
                'not_checked_in' => $notCheckedIn->pluck('visitor_name')
            ], 200); // <-- still 200
        }

        return response()->json([
            'success' => true,
            'visitor_staff_acknowledgement' => $visitor_staff_acknowledgement,
        ], 200);
    }

    //function for staff verify the visitor acknowledgement
    public function verifyVisitorAcknowledgement(Request $request)
    {
        $ack_number = $request->ack_number;
        $staff_name = $request->staff_name;
        $staff_id = $request->staff_id;

        $ack = VisitorStaffAcknowledgement::where('ack_number', $ack_number)->with('visitors')->first();

        if (!$ack) {
            return response()->json([
                'success' => false,
                'message' => 'Visitor acknowledgement not found',
            ], 200); // Always 200
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
        ], 200);
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

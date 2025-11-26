<?php

namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\InspectionAnswer;
use App\Models\ShipmentTransportInspection;
use Illuminate\Http\Request;

class ShipmentTransportInspectionController extends Controller
{
    public function createInspection(Request $request)
    {
        $transport_shipment_id = $request->input('shipment_transport_id');

        $transport_shipment_inspection_exist = ShipmentTransportInspection::where('shipment_transport_id', $transport_shipment_id)->first();

        if ($transport_shipment_inspection_exist) {
            return response()->json([
                'message' => 'Inspection already exists for this shipment transport',
                'inspection' => $transport_shipment_inspection_exist
            ]);
        }

        $inspection = ShipmentTransportInspection::create([
            'shipment_transport_id' => $transport_shipment_id,
            'status' => 'pending',
            'inspected_by' => auth()->id(),
            'remarks' => 'test',
        ]);

        return response()->json([
            'message' => 'Inspection created successfully',
            'inspection' => $inspection
        ]);
    }

    public function updateInspection(Request $request, $id)
    {
        $inspection = ShipmentTransportInspection::findOrFail(4);

        // Decode JSON answers
        $answers = json_decode($request->answers, true);
        // Loop through answers and attach photos if available
        foreach ($answers as &$answer) {

            $qid = $answer['question_id'];              // example: 1
            $photoKey = "photo_" . $qid;                // example: photo_1

            if ($request->hasFile($photoKey)) {
                $file = $request->file($photoKey);

                // Store file
                $path = $file->store('inspection_photos', 'public');

                // Attach stored path
                $answer['photo_urls'] = [$path];

            } else {
                $answer['photo_urls'] = [];
            }
        }

        // Save answers
        $failed = false;

        foreach ($answers as $answer) {
            InspectionAnswer::updateOrCreate(
                [
                    'shipment_transport_inspection_id' => $inspection->id,
                    'inspection_question_id' => $answer['question_id'],
                ],
                [
                    'passed' => $answer['passed'],
                    'remarks' => $answer['remarks'] ?? null,
                    'photo_path' => $path, // ✔ string only
                ]
            );

            if (!$answer['passed']) {
                $failed = true;
            }
        }

        // Update inspection status
        $inspection->update([
            'status' => $failed ? 'failed' : 'passed'
        ]);

        return response()->json([
            'message' => 'Inspection updated successfully',
            'inspection' => $inspection
        ]);
    }
}

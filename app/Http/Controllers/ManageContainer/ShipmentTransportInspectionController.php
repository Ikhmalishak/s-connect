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
        $inspection = ShipmentTransportInspection::where('shipment_transport_id', $id)->firstOrFail();

        // Decode JSON answers
        $answers = json_decode($request->answers, true);
        // Loop through answers and attach photos if available
        foreach ($answers as $i => $answer) {

            $qid = $answer['question_id'];
            $photoKey = "photo_" . $qid;

            if ($request->hasFile($photoKey)) {

                $file = $request->file($photoKey);
                $path = $file->store('inspection_photos', 'public');

                $answer['photo_urls'] = $path;

            } else {
                $answer['photo_urls'] = [];
            }

            // simpan balik ke array — tiada reference nonsense
            $answers[$i] = $answer;
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
                    'photo_path' => is_array($answer['photo_urls'])
                        ? ($answer['photo_urls'][0] ?? null)
                        : $answer['photo_urls'],
                ]
            );

            if (!$answer['passed']) {
                $failed = true;
            }
        }

        // Update inspection status
        $inspection->update([
            'received_at' => $request->input('received_at'),
            'inspected_at' => $request->input('inspected_at'),
            'status' => $failed ? 'failed' : 'passed',
            'inspected_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Inspection updated successfully',
            'inspection' => $inspection
        ]);
    }

    public function getInspectionDetails($id)
    {
        $inspection = ShipmentTransportInspection::with(['answers.question', 'transport.photo'])->where('shipment_transport_id', $id)->firstOrFail();

        return response()->json([
            'data' => $inspection
        ]);
    }

    public function test(Request $request, $id){
dd($request->all());
    }
}

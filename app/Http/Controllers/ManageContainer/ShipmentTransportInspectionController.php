<?php

namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\InspectionAnswer;
use App\Models\ShipmentTransport;
use App\Models\ShipmentTransportInspection;
use Illuminate\Http\Request;

class ShipmentTransportInspectionController extends Controller
{
    public function createInspection(Request $request)
    {
        $transport_shipment_id = $request->input('shipment_transport_id');
        $user = auth()->user();

        // Check if shipment transport belongs to user's site (unless superadmin)
        $container = ShipmentTransport::where('id', $transport_shipment_id);
        if (!$user->hasPermissionTo('superadmin')) {
            $container->where('site_id', $user->site_id);
        }
        $container = $container->first();

        if (!$container) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

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

        // Update container status to in_progress when inspection starts
        $container->update(['status' => 'in_progress']);

        return response()->json([
            'message' => 'Inspection created successfully',
            'inspection' => $inspection
        ]);
    }

    public function updateInspection(Request $request, $id)
    {
        $user = auth()->user();

        // Check if shipment transport belongs to user's site (unless superadmin)
        $container = ShipmentTransport::where('id', $id);
        if (!$user->hasPermissionTo('superadmin')) {
            $container->where('site_id', $user->site_id);
        }
        $container = $container->first();

        if (!$container) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

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

        // Update container status and send notifications
        if ($failed) {
            $container->update([
                'status' => 'failed',
                'failed_at' => 'container_checking'
            ]);
        } else {
            $container->update(['stage' => 'container_checking_approval']);

            // Create inspection approval request (department will be determined by approver)
            $approval = \App\Models\ShipmentTransportApproval::create([
                'shipment_transport_id' => $container->id,
                'department' => 'quality',
                'approval_type' => 'inspection',
                'approval_status' => 'pending',
            ]);

            // Broadcast real-time update for inspection submission
            broadcast(new \App\Events\ContainerStageUpdated($container))->toOthers();

            // Send Power Automate notification for inspection approval
            $this->sendInspectionApprovalNotification($container, $approval);

            // Send email to quality department users who can approve inspections (same site as container)
            // $qualityUsers = \App\Models\User::permission('container.quality.approve_inspection')
            //     ->where('site_id', $container->site_id)
            //     ->get();
            // \Illuminate\Support\Facades\Mail::to($qualityUsers)->send(new \App\Mail\ContainerInspectionPassed($container, $approval));
        }

        return response()->json([
            'message' => 'Inspection updated successfully',
            'inspection' => $inspection
        ]);
    }

    public function showByShipmentTransportId(Request $request)
    {
        $user = auth()->user();
        $shipmentTransportId = $request->query('shipment_transport_id');

        if (!$shipmentTransportId) {
            return response()->json(['message' => 'Shipment transport ID is required'], 400);
        }

        // Check if shipment transport belongs to user's site (unless superadmin)
        $container = ShipmentTransport::where('id', $shipmentTransportId);
        if (!$user->hasPermissionTo('superadmin')) {
            $container->where('site_id', $user->site_id);
        }
        $container = $container->first();

        if (!$container) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        $inspections = ShipmentTransportInspection::with(['answers.question', 'transport.photo'])->where('shipment_transport_id', $shipmentTransportId)->get();

        return response()->json([
            'data' => $inspections
        ]);
    }

    public function getInspectionDetails($id)
    {
        $user = auth()->user();

        // Check if shipment transport belongs to user's site (unless superadmin)
        $container = ShipmentTransport::where('id', $id);
        if (!$user->hasAnyPermission(['superadmin','container.shipping.access'])) {
            $container->where('site_id', $user->site_id);
        }
        $container = $container->first();

        if (!$container) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        $inspection = ShipmentTransportInspection::with(['answers.question', 'transport.photo', 'transport.approvals'])->where('shipment_transport_id', $id)->firstOrFail();

        return response()->json([
            'data' => $inspection
        ]);
    }

    private function sendInspectionApprovalNotification(ShipmentTransport $container, \App\Models\ShipmentTransportApproval $approval)
    {
        try {
            // Get quality department users who can approve inspections (same site as container)
            $qualityUsers = \App\Models\User::permission('container.quality.approve_inspection')
                ->where('site_id', $container->site_id)
                ->get();

            if ($qualityUsers->isEmpty()) {
                \Illuminate\Support\Facades\Log::warning("No quality users found for inspection approval notifications");
                return;
            }

            $triggerUrl = config('services.power_automate.inspection_trigger_url');
            if (!$triggerUrl) {
                \Illuminate\Support\Facades\Log::error("Power Automate inspection trigger URL not configured");
                return;
            }

            // Collect all quality approver emails into an array for Power Automate to handle "first wins" logic
            $approverEmails = $qualityUsers->pluck('email')->toArray();

            $payload = [
                'approval_id' => $approval->id,
                'title' => ($container->container_number ?: $container->transport_number) . ' - Inspection Approval',
                'description' => 'Container inspection requires quality department approval',
                'approver_email' => $approverEmails,
            ];

            $response = \Illuminate\Support\Facades\Http::post($triggerUrl, $payload);

            if ($response->successful()) {
                \Illuminate\Support\Facades\Log::info("Sent Power Automate notification to " . count($approverEmails) . " quality users for inspection approval {$approval->id}");
            } else {
                \Illuminate\Support\Facades\Log::error("Failed to send Power Automate notification for inspection approval {$approval->id}: " . $response->body());
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Exception sending Power Automate notifications for inspection approval {$approval->id}: " . $e->getMessage());
        }
    }
}

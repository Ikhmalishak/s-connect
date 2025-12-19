<?php

namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\ShipmentTransportPhoto;
use Illuminate\Http\Request;

class ShipmentTransportPhotoController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        $photoTypes = [
            'pallet_condition_photo',
            'pallet_label_photo',
            'gps_photo_before_installation',
            'container_truck_photo',
            'empty_container_photo',
            'inside_gps_photo',
            'half_loaded_photo',
            'one_side_door_closed_with_container_number_photo',
            'complete_loaded_photo',
            'outside_gps_photo',
            'security_seal_photo',
            'container_full_seal_photo'
        ];

        $rules = [
            'shipment_transport_id' => 'required|exists:shipment_transports,id',
        ];

        // Add validation rules for each photo type
        foreach ($photoTypes as $type) {
            $rules[$type] = 'nullable|image|max:5120'; // each file must be an image, max 5MB, optional
        }

        $validated = $request->validate($rules);

        // Check if shipment transport belongs to user's site (unless superadmin)
        $container = \App\Models\ShipmentTransport::where('id', $validated['shipment_transport_id']);
        if (!$user->hasPermissionTo('superadmin')) {
            $container->where('site_id', $user->site_id);
        }
        $container = $container->first();

        if (!$container) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        $createdPhotos = [];

        foreach ($photoTypes as $type) {
            if ($request->hasFile($type)) {
                $photo = $request->file($type);
                $path = $photo->store('container_photo', 'public');

                $createdPhotos[$type] = ShipmentTransportPhoto::create([
                    'shipment_transport_id' => $validated['shipment_transport_id'],
                    'label' => $type,
                    'photo_path' => $path,
                    'taken_by' => auth()->user()->id
                ]);
            }
        }

        // Update container stage and trigger department approvals
        $container->update(['stage' => 'container_loading_report_approval']);

        // Broadcast real-time update for photo upload
        broadcast(new \App\Events\ContainerStageUpdated($container))->toOthers();

        // Create department approval requests
        $approvalController = new \App\Http\Controllers\ManageContainer\ShipmentTransportApprovalController();
        $approvalController->createDepartmentApprovals($container);

        return response()->json([
            'message' => 'Photos uploaded successfully.',
            'data' => $createdPhotos,
        ]);
    }

    public function submitSecurityChecking(Request $request)
    {
        $user = auth()->user();

        $security_checking = ShipmentTransportPhoto::where('label','security_checking_photo')
        ->where('shipment_transport_id',$request->shipment_transport_id)
        ->exists();

        if($security_checking){
            return response()->json([
                'messages' => 'Security checking photo for this container already exist',
            ]);
        };

        $validated = $request->validate([
            'shipment_transport_id' => 'required|exists:shipment_transports,id',
            'security_checking_photo' => 'required|file'
        ]);

        // Check if shipment transport belongs to user's site (unless superadmin)
        $container = \App\Models\ShipmentTransport::where('id', $validated['shipment_transport_id']);
        if (!$user->hasPermissionTo('superadmin')) {
            $container->where('site_id', $user->site_id);
        }
        $container = $container->first();

        if (!$container) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        foreach ($validated as $key => $file) {
            if ($key === 'shipment_transport_id')
                continue; // skip ID
            ShipmentTransportPhoto::create([
                'shipment_transport_id' => $validated['shipment_transport_id'],
                'label' => $key, // use the field name dynamically
                'photo_path' => $file->store('container_photo', 'public'),
                'taken_by' => auth()->user()->id
            ]);
        }

        // Update container status to completed and stage to onboarded
        $container->update([
            'status' => 'completed',
            'stage' => 'onboarded'
        ]);

        return response()->json([
            'message' => 'Security checking completed successfully',
        ]);
    }
}

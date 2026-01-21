<?php

namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\ShipmentTransport;
use App\Models\ShipmentTransportPhoto;
use Illuminate\Http\Request;

class ShipmentTransportPhotoController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'shipment_transport_id' => 'required|exists:shipment_transports,id',
        ];

        $validated = $request->validate($rules);

        // Get the container to determine required photos
        $container = \App\Models\ShipmentTransport::where('id', $validated['shipment_transport_id']);
        if (!$user->hasPermissionTo('superadmin')) {
            $container->where('site_id', $user->site_id);
        }
        $container = $container->first();

        if (!$container) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        // Get required photo types for this container
        $requiredPhotoTypes = $this->getRequiredPhotoKeys($container);

        // Add validation rules only for photo types that are actually sent and required
        foreach ($requiredPhotoTypes as $type) {
            if ($request->hasFile($type)) {
                $rules[$type] = 'required|image|max:5120'; // required if sent, must be image, max 5MB
            }
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

        // Validate container stage - must be in loading report stage (after quality inspection approval)
        if ($container->stage !== 'container_loading_report') {
            return response()->json([
                'message' => 'Cannot upload photos. Container must pass quality inspection approval first.'
            ], 403);
        }

        $createdPhotos = [];
        $uploadedPhotoCount = 0;

        foreach ($requiredPhotoTypes as $type) {
            if ($request->hasFile($type)) {
                $photo = $request->file($type);
                $path = $photo->store('container_photo', 'public');

                // Check if photo of this type already exists for this shipment
                $existingPhoto = ShipmentTransportPhoto::where('shipment_transport_id', $validated['shipment_transport_id'])
                    ->where('label', $type)
                    ->first();

                if ($existingPhoto) {
                    // Update existing photo
                    $existingPhoto->update([
                        'photo_path' => $path,
                        'taken_by' => auth()->user()->id
                    ]);
                    $createdPhotos[$type] = $existingPhoto;
                } else {
                    // Create new photo
                    $createdPhotos[$type] = ShipmentTransportPhoto::create([
                        'shipment_transport_id' => $validated['shipment_transport_id'],
                        'label' => $type,
                        'photo_path' => $path,
                        'taken_by' => auth()->user()->id
                    ]);
                }

                $uploadedPhotoCount++;
            }
        }

        // Only trigger approvals if this is a bulk upload (multiple photos) or if explicitly creating record
        // For individual photo uploads, just save them without triggering workflow
        if ($uploadedPhotoCount > 1 || $request->input('create_record', false)) {
            // Update container stage and trigger department approvals
            $container->update(['stage' => 'container_loading_report_approval']);

            // Broadcast real-time update for photo upload
            broadcast(new \App\Events\ContainerStageUpdated($container))->toOthers();

            // Create department approval requests
            $approvalController = new \App\Http\Controllers\ManageContainer\ShipmentTransportApprovalController();
            $approvalController->createDepartmentApprovals($container);

            $message = $uploadedPhotoCount > 1 ? 'Photos uploaded successfully.' : 'Record created successfully.';
        } else {
            // Individual photo upload - just save without triggering workflow
            $message = 'Photo uploaded successfully.';
        }

        return response()->json([
            'message' => $message,
            'data' => $createdPhotos,
        ]);
    }

    public function getPhotos(ShipmentTransport $shipmentTransport)
    {
        $user = auth()->user();

        // Check if shipment transport belongs to user's site (unless superadmin)
        if (!$user->hasPermissionTo('superadmin') && $shipmentTransport->site_id !== $user->site_id) {
            return response()->json(['message' => 'Unauthorized access to shipment transport'], 403);
        }

        $photos = $shipmentTransport->photo()->get();

        return response()->json([
            'data' => $photos
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

    /**
     * Get the keys of required photos for a shipment transport.
     */
    private function getRequiredPhotoKeys(ShipmentTransport $shipmentTransport)
    {
        $requiredPhotos = [];

        // Always required photos
        $requiredPhotos = [
            'pallet_condition_photo',
            'pallet_label_photo',
            'container_truck_photo',
            'empty_container_photo',
            'half_loaded_photo',
            'one_side_door_closed_with_container_number_photo',
            'complete_loaded_photo',
        ];

        // GPS photos - required if GPS serial numbers are present
        if (!empty($shipmentTransport->inside_gps_sn) || !empty($shipmentTransport->outside_gps_sn)) {
            $requiredPhotos = array_merge($requiredPhotos, [
                'gps_photo_before_installation',
                'inside_gps_photo',
                'outside_gps_photo',
            ]);
        }

        // Seal photos - required if seal serial numbers are present
        if (!empty($shipmentTransport->high_security_seal_sn) ||
            !empty($shipmentTransport->fork_seal_sn) ||
            !empty($shipmentTransport->temporary_seal_sn)) {
            $requiredPhotos = array_merge($requiredPhotos, [
                'security_seal_photo',
                'container_full_seal_photo',
            ]);
        }

        return $requiredPhotos;
    }
}

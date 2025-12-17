<?php

namespace App\Http\Controllers\ManageContainer;

use App\Http\Controllers\Controller;
use App\Models\ShipmentTransportPhoto;
use Illuminate\Http\Request;

class ShipmentTransportPhotoController extends Controller
{
    public function store(Request $request)
    {
        $rules = [
            'shipment_transport_id' => 'required|exists:shipment_transports,id',
            'photos' => 'required|array',
            'photos.*' => 'required|image|max:5120', // each file must be an image, max 5MB
        ];
        $validated = $request->validate($rules);

        $createdPhotos = [];

        foreach ($validated['photos'] as $key => $photo) {
            $path = $photo->store('container_photo', 'public');

            $createdPhotos[$key] = ShipmentTransportPhoto::create([
                'shipment_transport_id' => $validated['shipment_transport_id'],
                'label' => $key,
                'photo_path' => $path,
                'taken_by' => auth()->user()->id
            ]);
        }

        return response()->json([
            'message' => 'Photos uploaded successfully.',
            'data' => $createdPhotos,
        ]);
    }

    public function submitSecurityChecking(Request $request)
    {
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
    }
}

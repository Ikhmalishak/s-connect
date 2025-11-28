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

        foreach($validated['photos'] as $key => $photo){
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
}

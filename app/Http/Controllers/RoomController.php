<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    // List rooms (optional filter by site)
    public function getRoomBySite(Request $request)
    {
        $query = Room::query();

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        return response()->json([
            'message' => 'Successfully fetched all rooms',
            'data' => $query->get()
        ]);
    }

    //get room by room id
    public function getRoomById($id)
    {
        $room = Room::where('id', $id)->first();

        return response()->json([
            'messages' => 'Successfully fetched room by Id',
            'data' => $room,
        ]);
    }

    // Store new room
    public function store(Request $request)
    {
        $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string',
            'capacity' => 'nullable|integer',
            'location' => 'nullable|string',
            'status' => 'required|in:available,maintenance',
        ]);

        $room = Room::create($request->all());

        return response()->json([
            'message' => 'Room created successfully.',
            'data' => $room
        ], 201);
    }

    // Show specific room
    public function show($id)
    {
        return Room::with('site', 'reservations')->findOrFail($id);
    }

    // Update room
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string',
            'site_id' => 'sometimes|required|exists:sites,id',
            'capacity' => 'nullable|integer',
            'location' => 'nullable|string',
            'status' => 'in:available,maintenance',
        ]);

        $room->update($request->all());

        return response()->json([
            'message' => 'Room updated successfully.',
            'data' => $room
        ]);
    }

    // Soft delete room
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return response()->json(['message' => 'Room removed (soft deleted).']);
    }
}

<?php

namespace App\Http\Controllers\ManageRoomReservation;

use App\Http\Controllers\Controller;
use App\Events\RoomReservationCreated;
use App\Models\RoomReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class RoomReservationController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('ManageRoomReservation/RoomReservationDashboard');
    }
    
    public function getRoomReservationTabletInterface($id)
    {
        return Inertia::render('ManageRoomReservation/RoomReservationTablet', [
            'roomId' => $id,
        ]);
    }

    // List reservations
    public function index(Request $request)
    {
        $query = RoomReservation::whereDate('date', $request->date)
            ->where('status', "active")
            ->with('room');

        // Filter by site
        if ($request->site_id) {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('site_id', $request->site_id);
            });
        }

        return response()->json([
            'messages' => 'Successfully fetched booking lists',
            'data' => $query->orderBy('start_time')->get()
        ]);
    }

    //get room reservation by room id
    public function getRoomStatus($roomId)
    {
        $now = now();

        $reservation = RoomReservation::where('room_id', $roomId)
            ->whereDate('date', $now->toDateString())
            ->whereTime('start_time', '<=', $now->toTimeString())
            ->whereTime('end_time', '>', $now->toTimeString())
            ->where('status', "active")
            ->first();

        $status = $reservation ? 'in_use' : 'available';

        $minutesLeft = null;

        if ($reservation) {
            $end = Carbon::parse($reservation->end_time);
            $minutesLeft = $now->diffInMinutes($end, false); // can go negative
            $minutesLeft = max($minutesLeft, 0); // no negative values
        }

        $room_reservation_today = RoomReservation::whereDate('date', Carbon::today())
            ->where('room_id', $roomId)
            ->whereIn('status', ['active', 'completed'])
            ->orderByRaw("FIELD(status, 'active', 'completed')")
            ->orderBy('start_time', 'asc') // or whatever your time column is
            ->get();

        return response()->json([
            'status' => $status,
            'current_reservation' => $reservation,
            'minutes_left' => $minutesLeft,
            'room_schedule' => $room_reservation_today
        ]);
    }

    // Create reservation (Pending by default)
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'user_name' => 'required|string|min:2|max:50',
            'user_id' => 'required|string|min:3|max:20',
            'user_email' => 'required|string',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i|before:end_time',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'purpose' => 'nullable|string|max:255',
        ]);

        // Combine date + time → full datetime
        $startDateTime = $request->date . ' ' . $request->start_time;
        $endDateTime = $request->date . ' ' . $request->end_time;

        // Prevent overlapping reservations
        $overlapExists = RoomReservation::where('room_id', $request->room_id)
            ->whereDate('date', $request->date)
            ->where('status', "active")
            ->where(function ($q) use ($startDateTime, $endDateTime) {
                $q->where('start_time', '<', $endDateTime)
                    ->where('end_time', '>', $startDateTime);
            })
            ->exists();

        if ($overlapExists) {
            return response()->json([
                'message' => 'This room is already booked during the selected time.'
            ], 422);
        }

        // Create booking
        $reservation = RoomReservation::create([
            'room_id' => $request->room_id,
            'user_name' => $request->user_name,
            'user_id' => $request->user_id,
            'date' => $request->date,
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'purpose' => $request->purpose,
            'email' => $request->user_email,
            'reminder_sent' => false
        ]);

        //call the event
        event(new RoomReservationCreated());

        return response()->json([
            'message' => 'Reservation created successfully.',
            'data' => $reservation
        ]);
    }

    // Cancel reservation by requester
    public function cancel($id)
    {
        $reservation = RoomReservation::findOrFail($id);

        event(new RoomReservationCreated());

        $reservation->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Reservation successfully cancelled']);
    }
}

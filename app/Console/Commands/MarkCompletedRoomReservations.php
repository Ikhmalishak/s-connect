<?php

namespace App\Console\Commands;

use App\Models\RoomReservation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkCompletedRoomReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-completed-room-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark reservations as completed when their end time has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $reservations = RoomReservation::whereDate('date', $now)
        ->where('status', "active")
        ->where('end_time', '<', $now)
        ->get();

        foreach( $reservations as $reservation){
            $reservation-> update(['status' => "completed"]);
            Log::info("Marked reservation {$reservation->id} as completed");
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RoomReservation;
use App\Mail\RoomReservationReminderMail;
use Carbon\Carbon;
use Mail;
use Illuminate\Support\Facades\Log;

class SendRoomReservationReminderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-room-reservation-reminder-emails';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails 15 minutes before a room reservation starts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $reservations = RoomReservation::where('status', 'active')
            ->where('reminder_sent', false)
            ->whereDate('start_time', Carbon::today())
            ->whereBetween('start_time', [$now, $now->clone()->addMinutes(15)])
            ->get();

        foreach ($reservations as $reservation) {
            Mail::to($reservation->email)->send(new RoomReservationReminderMail($reservation));

            $reservation->update(['reminder_sent' => true]);

            $this->info("Reminder sent to {$reservation->email}");
            Log::info("Successfully sent reminder email", [
                'email' => $reservation->email,
                'reservation_id' => $reservation->id,
            ]);
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Events\NotifyGuard;
use App\Models\Visitor;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;

class NotifyGuardAboutVisitors extends Command
{
    protected $signature = 'app:notify-guard-about-visitors';
    protected $description = 'Notify guard about visitors who have not checked out after shift cutoff.';

    public function handle()
    {
        $date = now()->format('Y-m-d');

        $visitors = Visitor::whereDate('date', $date)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->with('gatePass')
            ->get();

        if ($visitors->isNotEmpty()) {

            // Fire the event only if visitors exist
            event(new NotifyGuard($visitors));

            Log::info('Visitors still inside at shift cutoff', [
                'count' => $visitors->count(),
                'visitors' => $visitors->pluck('visitor_name')
            ]);
        } else {
            Log::info('No visitors inside at shift cutoff.');
        }
    }
}

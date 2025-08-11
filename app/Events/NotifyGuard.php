<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotifyGuard implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Collection $visitors;

    /**
     * Create a new event instance.
     */
    public function __construct(Collection $visitors)
    {
        Log::info('notify guard event');

        // Assign the visitors collection to the correct property
        $this->visitors = $visitors;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('guard'),
        ];
    }

    /**
     * The data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'visitors' => $this->visitors->map(function ($visitor) {
                return [
                    'id' => $visitor->id,
                    'visitor_name' => $visitor->visitor_name,
                    'gate_pass' => $visitor->gatePass ? $visitor->gatePass->toArray() : null,
                    // add other visitor fields you want to send
                ];
            })->toArray(),
        ];
    }

    /**
     * The event name for broadcasting.
     */
    public function broadcastAs(): string
    {
        return 'notify.guard';
    }
}

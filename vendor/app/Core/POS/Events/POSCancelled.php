<?php

namespace App\Core\POS\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Core\POS\Entities\PointOfSale;

class POSCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pos;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(PointOfSale $pos)
    {
        $this->pos = $pos;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

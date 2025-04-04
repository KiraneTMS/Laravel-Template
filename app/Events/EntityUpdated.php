<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class EntityUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $entityName;
    public $item;
    public $action;

    public function __construct($entityName, $item, $action = 'update')
    {
        $this->entityName = $entityName;
        $this->item = $item;
        $this->action = $action;
    }

    public function broadcastOn()
    {
        return new Channel('entity.' . $this->entityName);
    }

    public function broadcastWith()
    {
        return [
            'item' => $this->item,
            'action' => $this->action,
        ];
    }
}

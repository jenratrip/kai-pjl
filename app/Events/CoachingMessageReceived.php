<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CoachingMessageReceived implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $messageId, private string $recipientUserId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("users.{$this->recipientUserId}.coaching-messages")];
    }

    public function broadcastAs(): string
    {
        return 'CoachingMessageReceived';
    }

    public function broadcastWith(): array
    {
        return ['message_id' => $this->messageId];
    }
}

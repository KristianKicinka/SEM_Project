<?php
/**
 * @file ProcessUpdate.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessUpdate implements ShouldBroadcast {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    private string $channel_id;
    public string $process_id;
    public string $status;
    public int $progress;
    public string $message;
    public string $name;

    /**
     * Create a new event instance.
     */
    public function __construct($channel_id, $process_id, $status, $progress, $message, $name) {
        $this->channel_id = $channel_id;
        $this->process_id = $process_id;
        $this->status = $status;
        $this->progress = $progress;
        $this->message = $message;
        $this->name = $name;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('process-channel-' . $this->channel_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'process-update';
    }
}

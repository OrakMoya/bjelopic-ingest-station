<?php

namespace App\Events;

use App\Models\File;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileIngestedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $queue = 'messages';

    /**
     * Create a new event instance.
     */
    public function __construct(public File $file, public int $totalFileCount, public int|null $overrideId = null, public bool $alreadyExists = false, public bool $error = false)
    {

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('ingest'),
        ];
    }
    /**
     * @return array<string,array>
     */
    public function broadcastWith(): array
    {
        return [
            'file' => [
                'id' => $this->overrideId ?? $this->file->id,
                'filename' => $this->file->filename,
                'path' => $this->file->path,
            ],
            'totalFileCount' => $this->totalFileCount,
            'alreadyExists' => $this->alreadyExists,
            'error' => $this->error
        ];
    }
}

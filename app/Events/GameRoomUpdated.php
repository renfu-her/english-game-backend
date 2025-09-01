<?php

namespace App\Events;

use App\Models\GameRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameRoomUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GameRoom $gameRoom;
    public string $eventType;
    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(GameRoom $gameRoom, string $eventType, array $data = [])
    {
        $this->gameRoom = $gameRoom;
        $this->eventType = $eventType;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game-room.' . $this->gameRoom->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'event_type' => $this->eventType,
            'game_room' => [
                'id' => $this->gameRoom->id,
                'name' => $this->gameRoom->name,
                'status' => $this->gameRoom->status,
                'current_players' => $this->gameRoom->current_players,
                'max_players' => $this->gameRoom->max_players,
                'current_round' => $this->gameRoom->current_round,
                'total_rounds' => $this->gameRoom->total_rounds,
            ],
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'game.room.updated';
    }
}

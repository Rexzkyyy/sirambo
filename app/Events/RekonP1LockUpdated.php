<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RekonP1LockUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type,            // kategori | subkategori
        public int $entityId,           // id_kategori | id_sub_kategori
        public string $pendekatan,      // lapangan_usaha | pengeluaran
        public bool $locked,            // true (dikunci) | false (dibuka)
        public ?int $userId = null
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('rekon-p1');
    }

    public function broadcastAs(): string
    {
        return 'rekon-p1.lock-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'entity_id' => $this->entityId,
            'pendekatan' => $this->pendekatan,
            'locked' => $this->locked,
            'user_id' => $this->userId,
        ];
    }
}

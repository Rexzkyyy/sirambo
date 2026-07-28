<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RekonP1Updated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type,            // kategori | subkategori
        public int $entityId,           // id_kategori | id_sub_kategori
        public int $idWilayah,
        public int $idTahun,
        public int $idPeriode,
        public string $tipe,            // berlaku | konstan
        public float $adj,
        public ?int $historyId,
        public string $pendekatan,
        public ?int $userId = null
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('rekon-p1');
    }

    public function broadcastAs(): string
    {
        return 'rekon-p1.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'entity_id' => $this->entityId,
            'id_wilayah' => $this->idWilayah,
            'id_tahun' => $this->idTahun,
            'id_periode' => $this->idPeriode,
            'tipe' => $this->tipe,
            'adj' => $this->adj,
            'history_id' => $this->historyId,
            'pendekatan' => $this->pendekatan,
            'user_id' => $this->userId,
        ];
    }
}

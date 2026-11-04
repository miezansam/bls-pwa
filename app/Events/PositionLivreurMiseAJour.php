<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

final class PositionLivreurMiseAJour implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $commandeId,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $mesureS,
    ) {
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('commande.' . $this->commandeId)];
    }

    public function broadcastAs(): string
    {
        return 'position.change';
    }

    /** @return array{latitude: float, longitude: float, mesure_s: int} */
    public function broadcastWith(): array
    {
        return ['latitude' => $this->latitude, 'longitude' => $this->longitude, 'mesure_s' => $this->mesureS];
    }
}

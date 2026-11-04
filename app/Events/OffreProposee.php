<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Attribution;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OffreProposee implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Attribution $attribution)
    {
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('livreur.' . $this->attribution->livreur_id)];
    }

    public function broadcastAs(): string
    {
        return 'offre.proposee';
    }

    /** @return array{reference: string} */
    public function broadcastWith(): array
    {
        return ['reference' => $this->attribution->commande->reference];
    }
}

<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\StatutCommande;
use App\Models\Commande;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StatutCommandeChange implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Commande $commande,
        public readonly ?StatutCommande $ancienStatut,
        public readonly StatutCommande $nouveauStatut,
    ) {
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        $canaux = [new PrivateChannel('commande.' . $this->commande->id)];

        if ($this->commande->livreur_id !== null) {
            $canaux[] = new PrivateChannel('livreur.' . $this->commande->livreur_id);
        }

        return $canaux;
    }

    public function broadcastAs(): string
    {
        return 'statut.change';
    }

    /** @return array{reference: string, statut: string, libelle: string} */
    public function broadcastWith(): array
    {
        return [
            'reference' => $this->commande->reference,
            'statut' => $this->nouveauStatut->value,
            'libelle' => $this->nouveauStatut->libelle(),
        ];
    }
}

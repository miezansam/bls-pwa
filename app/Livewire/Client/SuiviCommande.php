<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Models\Commande;
use App\Services\PositionLivreurService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.espace')]
#[Title('Ma commande')]
class SuiviCommande extends Component
{
    public Commande $commande;

    public function mount(Commande $commande): void
    {
        abort_unless($commande->client_id === auth()->id(), 404);

        $this->commande = $commande;
    }

    /** @return array<string, string> */
    public function getListeners(): array
    {
        return [
            "echo-private:commande.{$this->commande->id},.statut.change" => '$refresh',
        ];
    }

    /** Appelée par le navigateur quand la connexion en direct est coupée. */
    public function positionActuelle(PositionLivreurService $positions): ?array
    {
        $this->commande->refresh();

        return $this->suiviActif() ? $positions->derniereAvecAge($this->commande->livreur_id) : null;
    }

    public function render()
    {
        $this->commande->refresh();
        $suiviActif = $this->suiviActif();

        return view('livewire.client.suivi-commande', [
            'suiviActif' => $suiviActif,
            'position' => $suiviActif ? app(PositionLivreurService::class)->derniereAvecAge($this->commande->livreur_id) : null,
        ]);
    }

    /** La position n'est visible que pendant une course active avec un livreur. */
    private function suiviActif(): bool
    {
        return $this->commande->livreur_id !== null && $this->commande->statut->declencheSuiviGps();
    }
}

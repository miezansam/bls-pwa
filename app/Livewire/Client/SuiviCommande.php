<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Models\Commande;
use App\Services\PositionLivreurService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Enums\StatutCommande;
use App\Services\ItineraireService;

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
        $position = $suiviActif ? app(PositionLivreurService::class)->derniereAvecAge($this->commande->livreur_id) : null;

        return view('livewire.client.suivi-commande', [
            'suiviActif' => $suiviActif,
            'position' => $position,
            'eta' => $this->eta($position),
        ]);
    }

    /**
     * Estimation d'arrivée, seulement vers une adresse épinglée et avec une position récente.
     *
     * @param array{latitude: float, longitude: float, age_s: int}|null $position
     * @return array{libelle: string, minutes: int}|null
     */
    private function eta(?array $position): ?array
    {
        if ($position === null || $position['age_s'] > 120) {
            return null;
        }

        $commande = $this->commande;

        [$epingle, $latitude, $longitude, $libelle] = match ($commande->statut) {
            StatutCommande::Acceptee, StatutCommande::EnRouteRetrait => [
                $commande->retrait_epingle,
                $commande->retrait_latitude,
                $commande->retrait_longitude,
                'Arrivée du livreur au retrait',
            ],
            default => [
                $commande->livraison_epingle,
                $commande->livraison_latitude,
                $commande->livraison_longitude,
                'Livraison estimée',
            ],
        };

        if (! $epingle) {
            return null;
        }

        $trajet = app(ItineraireService::class)->estimer($position['latitude'], $position['longitude'], $latitude, $longitude);

        $minutes = max(1, (int) ceil($trajet['duree_s'] / 60));

        // Au-delà, l'estimation n'aide pas le client (GPS erroné, livreur très loin) : on ne l'affiche pas
        if ($minutes > (int) config('bls.itineraire.eta_max_minutes')) {
            return null;
        }

        return ['libelle' => $libelle, 'minutes' => $minutes];

    }

    /** La position n'est visible que pendant une course active avec un livreur. */
    private function suiviActif(): bool
    {
        return $this->commande->livreur_id !== null && $this->commande->statut->declencheSuiviGps();
    }
}

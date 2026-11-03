<?php

declare(strict_types=1);

namespace App\Livewire\Livreur;

use App\Actions\AccepterAttributionAction;
use App\Actions\ChangerDisponibiliteLivreurAction;
use App\Actions\RefuserAttributionAction;
use App\Enums\StatutAttribution;
use App\Enums\StatutLivreur;
use App\Models\Attribution;
use App\Models\Commande;
use App\Models\ProfilLivreur;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.espace')]
#[Title('Espace livreur')]
class Accueil extends Component
{
    public string $erreur = '';

    #[Computed]
    public function profil(): ProfilLivreur
    {
        return ProfilLivreur::query()->where('utilisateur_id', auth()->id())->firstOrFail();
    }

    #[Computed]
    public function estDisponible(): bool
    {
        return $this->profil->statut === StatutLivreur::Disponible;
    }

    /** @return Collection<int, Attribution> */
    #[Computed]
    public function offres(): Collection
    {
        return Attribution::query()
            ->where('livreur_id', auth()->id())
            ->where('statut', StatutAttribution::Proposee)
            ->where(fn ($q) => $q->whereNull('expire_le')->orWhere('expire_le', '>', now()))
            ->with(['commande.zoneRetrait', 'commande.zoneLivraison'])
            ->oldest('proposee_le')
            ->get();
    }

    #[Computed]
    public function courseEnCours(): ?Commande
    {
        return Commande::query()
            ->duLivreur(auth()->id())
            ->enCours()
            ->with(['zoneRetrait', 'zoneLivraison'])
            ->latest()
            ->first();
    }

    public function accepter(int $attributionId, AccepterAttributionAction $accepter): void
    {
        $this->erreur = '';
        $attribution = Attribution::query()->where('livreur_id', auth()->id())->findOrFail($attributionId);

        try {
            $accepter->executer($attribution, auth()->user());
        } catch (DomainException $e) {
            $this->erreur = $e->getMessage();
        }
    }

    public function refuser(int $attributionId, RefuserAttributionAction $refuser): void
    {
        $this->erreur = '';
        $attribution = Attribution::query()->where('livreur_id', auth()->id())->findOrFail($attributionId);

        try {
            $refuser->executer($attribution, auth()->user());
        } catch (DomainException $e) {
            $this->erreur = $e->getMessage();
        }
    }

    public function basculer(ChangerDisponibiliteLivreurAction $changer): void
    {
        $this->erreur = '';

        try {
            $changer->executer(auth()->user(), ! $this->estDisponible);
        } catch (DomainException $e) {
            $this->erreur = $e->getMessage();
        }

        unset($this->profil, $this->estDisponible);
    }

    public function render(): View
    {
        return view('livewire.livreur.accueil');
    }
}

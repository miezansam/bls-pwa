<?php

declare(strict_types=1);

namespace App\Livewire\Livreur;

use App\Actions\AccepterAttributionAction;
use App\Actions\ChangerDisponibiliteLivreurAction;
use App\Actions\ChangerStatutCommandeAction;
use App\Actions\ConfirmerLivraisonAction;
use App\Actions\DeclarerMontantArticlesAction;
use App\Actions\RefuserAttributionAction;
use App\Enums\StatutAttribution;
use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Enums\TypeCommande;
use App\Exceptions\CodeLivraisonInvalideException;
use App\Exceptions\TransitionInvalideException;
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

    public string $montantArticles = '';

    public string $codeLivraison = '';

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

    /** Texte du bouton d'avancement. L'étape finale (livraison) passe par la saisie du code. */
    #[Computed]
    public function libelleAction(): ?string
    {
        return match ($this->courseEnCours?->statut) {
            StatutCommande::Acceptee => 'Je pars au retrait',
            StatutCommande::EnRouteRetrait => 'J\'ai récupéré la commande',
            StatutCommande::ColisRecupere => 'Je pars livrer',
            default => null,
        };
    }

    /** @return array{livrees: int, caisse: int} */
    #[Computed]
    public function bilanDuJour(): array
    {
        $livrees = Commande::query()
            ->duLivreur(auth()->id())
            ->where('statut', StatutCommande::Livree)
            ->where('livree_le', '>=', now()->startOfDay())
            ->count();

        return ['livrees' => $livrees, 'caisse' => $this->profil->solde_caisse];
    }

    public function accepter(int $attributionId, AccepterAttributionAction $accepter): void
    {
        $attribution = Attribution::query()->where('livreur_id', auth()->id())->findOrFail($attributionId);

        $this->tenter(fn () => $accepter->executer($attribution, auth()->user()));
    }

    public function refuser(int $attributionId, RefuserAttributionAction $refuser): void
    {
        $attribution = Attribution::query()->where('livreur_id', auth()->id())->findOrFail($attributionId);

        $this->tenter(fn () => $refuser->executer($attribution, auth()->user()));
    }

    public function basculer(ChangerDisponibiliteLivreurAction $changer): void
    {
        $this->tenter(fn () => $changer->executer(auth()->user(), ! $this->estDisponible));
    }

    public function avancer(ChangerStatutCommandeAction $changer, DeclarerMontantArticlesAction $declarer): void
    {
        $course = $this->courseEnCours;
        $suivant = $course === null ? null : $this->prochainStatut($course->statut);

        if ($suivant === null) {
            return;
        }

        $declareMontant = $suivant === StatutCommande::ColisRecupere && $course->type === TypeCommande::Repas;

        if ($declareMontant) {
            $this->montantArticles = preg_replace('/\s+/', '', $this->montantArticles) ?? '';

            $this->validate(
                ['montantArticles' => ['required', 'integer', 'min:0', 'max:200000']],
                [
                    'required' => 'Entrez le montant payé pour les articles.',
                    'integer' => 'Entrez un nombre entier, sans virgule.',
                    'min' => 'Le montant ne peut pas être négatif.',
                    'max' => 'Ce montant semble trop élevé.',
                ],
            );
        }

        $this->tenter(function () use ($changer, $declarer, $course, $suivant, $declareMontant): void {
            if ($declareMontant) {
                $declarer->executer($course, (int) $this->montantArticles, auth()->user());
            }

            $changer->executer($course, $suivant, auteur: auth()->user());
        });

        $this->reset('montantArticles');
    }

    public function confirmerLivraison(ConfirmerLivraisonAction $confirmer): void
    {
        $course = $this->courseEnCours;

        if ($course === null || $course->statut !== StatutCommande::EnRouteLivraison) {
            return;
        }

        $this->validate(
            ['codeLivraison' => ['required', 'digits:4']],
            ['required' => 'Entrez le code à 4 chiffres donné par le client.', 'digits' => 'Le code a exactement 4 chiffres.'],
        );

        $this->tenter(fn () => $confirmer->executer($course, $this->codeLivraison, auth()->user()));

        $this->reset('codeLivraison');
    }

    public function render(): View
    {
        return view('livewire.livreur.accueil');
    }

    private function prochainStatut(StatutCommande $statut): ?StatutCommande
    {
        return match ($statut) {
            StatutCommande::Acceptee => StatutCommande::EnRouteRetrait,
            StatutCommande::EnRouteRetrait => StatutCommande::ColisRecupere,
            StatutCommande::ColisRecupere => StatutCommande::EnRouteLivraison,
            default => null,
        };
    }

    /** Exécute une opération métier et affiche son erreur éventuelle en français. */
    private function tenter(callable $operation): void
    {
        $this->erreur = '';

        try {
            $operation();
        } catch (TransitionInvalideException | CodeLivraisonInvalideException | DomainException $e) {
            $this->erreur = $e->getMessage();
        }

        // Sans cela, l'affichage reprendrait les valeurs mises en cache avant le changement
        unset($this->profil, $this->estDisponible, $this->offres, $this->courseEnCours, $this->libelleAction, $this->bilanDuJour);
    }

    /** @return array<string, string> */
    public function getListeners(): array
    {
        $canal = 'echo-private:livreur.' . auth()->id();

        return [
            $canal . ',.offre.proposee' => '$refresh',
            $canal . ',.statut.change' => '$refresh',
        ];
    }
}

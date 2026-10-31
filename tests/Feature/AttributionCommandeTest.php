<?php

declare(strict_types=1);

use App\Actions\AccepterAttributionAction;
use App\Actions\AttribuerCommandeAction;
use App\Actions\ChangerStatutCommandeAction;
use App\Actions\RefuserAttributionAction;
use App\Enums\StatutAttribution;
use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Models\Attribution;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;

function statutDuLivreur(Utilisateur $livreur): StatutLivreur
{
    return ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->firstOrFail()->statut;
}

test('l\'admin propose une commande à un livreur sans la faire avancer', function () {
    $commande = commandeDeTest();
    $livreur = Utilisateur::factory()->livreur()->create();
    $admin = Utilisateur::factory()->admin()->create();

    $attribution = app(AttribuerCommandeAction::class)->executer($commande, $livreur, $admin);

    expect($attribution->statut)->toBe(StatutAttribution::Proposee);
    expect($attribution->attribuee_par)->toBe($admin->id);
    expect($attribution->expire_le->isFuture())->toBeTrue();

    $commande = $commande->fresh();
    expect($commande->statut)->toBe(StatutCommande::EnAttente);
    expect($commande->livreur_id)->toBeNull();
    expect($commande->attribuee_le)->not->toBeNull();
});

test('une nouvelle proposition annule la précédente', function () {
    $commande = commandeDeTest();
    $premier = Utilisateur::factory()->livreur()->create();
    $second = Utilisateur::factory()->livreur()->create();
    $attribuer = app(AttribuerCommandeAction::class);

    $ancienne = $attribuer->executer($commande, $premier);
    $attribuer->executer($commande, $second);

    expect($ancienne->fresh()->statut)->toBe(StatutAttribution::Annulee);
    expect(Attribution::query()->where('statut', StatutAttribution::Proposee->value)->count())->toBe(1);
});

test('on ne propose pas une course à un livreur hors ligne ou non validé', function () {
    $commande = commandeDeTest();
    $attribuer = app(AttribuerCommandeAction::class);

    $horsLigne = Utilisateur::factory()->livreur()->create();
    ProfilLivreur::query()->where('utilisateur_id', $horsLigne->id)->update(['statut' => StatutLivreur::HorsLigne->value]);

    $nonValide = Utilisateur::factory()->livreur()->create();
    ProfilLivreur::query()->where('utilisateur_id', $nonValide->id)->update(['est_valide' => false]);

    expect(fn () => $attribuer->executer($commande, $horsLigne))->toThrow(DomainException::class);
    expect(fn () => $attribuer->executer($commande, $nonValide))->toThrow(DomainException::class);
    expect(Attribution::count())->toBe(0);
});

test('on ne propose pas une commande déjà acceptée', function () {
    [$commande] = courseEnRouteLivraison();
    $autre = Utilisateur::factory()->livreur()->create();

    expect(fn () => app(AttribuerCommandeAction::class)->executer($commande, $autre))
        ->toThrow(DomainException::class);
});

test('le livreur accepte : la commande est acceptée et il devient occupé', function () {
    $commande = commandeDeTest();
    $livreur = Utilisateur::factory()->livreur()->create();
    $attribution = app(AttribuerCommandeAction::class)->executer($commande, $livreur);

    $commande = app(AccepterAttributionAction::class)->executer($attribution, $livreur);

    expect($commande->statut)->toBe(StatutCommande::Acceptee);
    expect($commande->livreur_id)->toBe($livreur->id);
    expect($attribution->fresh()->statut)->toBe(StatutAttribution::Acceptee);
    expect($attribution->fresh()->repondue_le)->not->toBeNull();
    expect(statutDuLivreur($livreur))->toBe(StatutLivreur::Occupe);
});

test('le livreur refuse : la commande reste en attente et peut être réattribuée', function () {
    $commande = commandeDeTest();
    $premier = Utilisateur::factory()->livreur()->create();
    $second = Utilisateur::factory()->livreur()->create();
    $attribution = app(AttribuerCommandeAction::class)->executer($commande, $premier);

    $refusee = app(RefuserAttributionAction::class)->executer($attribution, $premier);

    expect($refusee->statut)->toBe(StatutAttribution::Refusee);
    expect($commande->fresh()->statut)->toBe(StatutCommande::EnAttente);
    expect($commande->fresh()->attribuee_le)->toBeNull();
    expect(statutDuLivreur($premier))->toBe(StatutLivreur::Disponible);

    $nouvelle = app(AttribuerCommandeAction::class)->executer($commande, $second);
    expect($nouvelle->statut)->toBe(StatutAttribution::Proposee);
});

test('un autre livreur ne peut pas répondre à l\'offre', function () {
    $commande = commandeDeTest();
    $livreur = Utilisateur::factory()->livreur()->create();
    $intrus = Utilisateur::factory()->livreur()->create();
    $attribution = app(AttribuerCommandeAction::class)->executer($commande, $livreur);

    expect(fn () => app(AccepterAttributionAction::class)->executer($attribution, $intrus))
        ->toThrow(DomainException::class);
    expect(fn () => app(RefuserAttributionAction::class)->executer($attribution, $intrus))
        ->toThrow(DomainException::class);
    expect($attribution->fresh()->statut)->toBe(StatutAttribution::Proposee);
});

test('une offre expirée ne peut plus être acceptée et est marquée expirée', function () {
    $commande = commandeDeTest();
    $livreur = Utilisateur::factory()->livreur()->create();
    $attribution = app(AttribuerCommandeAction::class)->executer($commande, $livreur);

    Attribution::query()->update(['expire_le' => now()->subMinute()]);

    expect(fn () => app(AccepterAttributionAction::class)->executer($attribution, $livreur))
        ->toThrow(DomainException::class, 'expiré');

    expect($attribution->fresh()->statut)->toBe(StatutAttribution::Expiree);
    expect($commande->fresh()->statut)->toBe(StatutCommande::EnAttente);
});

test('une offre ne peut pas être acceptée deux fois', function () {
    $commande = commandeDeTest();
    $livreur = Utilisateur::factory()->livreur()->create();
    $attribution = app(AttribuerCommandeAction::class)->executer($commande, $livreur);
    $accepter = app(AccepterAttributionAction::class);

    $accepter->executer($attribution, $livreur);

    expect(fn () => $accepter->executer($attribution, $livreur))->toThrow(DomainException::class);
});

test('annuler la commande ferme ses offres en cours', function () {
    $commande = commandeDeTest();
    $livreur = Utilisateur::factory()->livreur()->create();
    $attribution = app(AttribuerCommandeAction::class)->executer($commande, $livreur);

    app(ChangerStatutCommandeAction::class)
        ->executer($commande, StatutCommande::Annulee, auteur: $commande->client, note: 'Changement de plan');

    expect($attribution->fresh()->statut)->toBe(StatutAttribution::Annulee);
});

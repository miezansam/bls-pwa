<?php

declare(strict_types=1);

use App\Actions\AttribuerCommandeAction;
use App\Actions\ChangerEtatCompteAction;
use App\Enums\StatutAttribution;
use App\Enums\StatutLivreur;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;

test('un compte client peut être désactivé', function () {
    $client = Utilisateur::factory()->client()->create();
    $admin = Utilisateur::factory()->admin()->create();

    $client = app(ChangerEtatCompteAction::class)->executer($client, false, $admin);

    expect($client->est_actif)->toBeFalse();
});

test('désactiver un livreur le met hors ligne', function () {
    $livreur = Utilisateur::factory()->livreur()->create();
    $admin = Utilisateur::factory()->admin()->create();

    app(ChangerEtatCompteAction::class)->executer($livreur, false, $admin);

    $profil = ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->firstOrFail();
    expect($livreur->fresh()->est_actif)->toBeFalse();
    expect($profil->statut)->toBe(StatutLivreur::HorsLigne);
});

test('on ne peut pas désactiver un livreur qui a une course en cours', function () {
    [, $livreur] = courseEnRouteLivraison();
    $admin = Utilisateur::factory()->admin()->create();

    expect(fn () => app(ChangerEtatCompteAction::class)->executer($livreur, false, $admin))
        ->toThrow(DomainException::class, 'course en cours');

    expect($livreur->fresh()->est_actif)->toBeTrue();
});

test('un admin ne peut pas désactiver son propre compte', function () {
    $admin = Utilisateur::factory()->admin()->create();

    expect(fn () => app(ChangerEtatCompteAction::class)->executer($admin, false, $admin))
        ->toThrow(DomainException::class);

    expect($admin->fresh()->est_actif)->toBeTrue();
});

test('un compte désactivé peut être réactivé', function () {
    $client = Utilisateur::factory()->client()->create(['est_actif' => false]);
    $admin = Utilisateur::factory()->admin()->create();

    $client = app(ChangerEtatCompteAction::class)->executer($client, true, $admin);

    expect($client->est_actif)->toBeTrue();
});

test('désactiver un livreur annule ses offres en attente', function () {
    $commande = commandeDeTest();
    $livreur = Utilisateur::factory()->livreur()->create();
    $admin = Utilisateur::factory()->admin()->create();
    $attribution = app(AttribuerCommandeAction::class)->executer($commande, $livreur, $admin);

    app(ChangerEtatCompteAction::class)->executer($livreur, false, $admin);

    expect($attribution->fresh()->statut)->toBe(StatutAttribution::Annulee);
    expect($commande->fresh()->attribuee_le)->toBeNull();
});

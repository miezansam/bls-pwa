<?php

declare(strict_types=1);

use App\Actions\CreerLivreurAction;
use App\Enums\StatutLivreur;
use App\Enums\TypeVehicule;
use App\Models\Utilisateur;

function donneesLivreur(array $surcharges = []): array
{
    return array_merge([
        'nom' => 'Koffi Test',
        'telephone' => '+2250700000123',
        'type_vehicule' => 'moto',
        'plaque' => '1234 AB 01',
        'est_valide' => true,
    ], $surcharges);
}

test('un livreur est créé avec son compte, son rôle et un profil hors ligne', function () {
    $profil = app(CreerLivreurAction::class)->executer(donneesLivreur());
    $utilisateur = $profil->utilisateur;

    expect($utilisateur->nom)->toBe('Koffi Test');
    expect($utilisateur->telephone)->toBe('+2250700000123');
    expect($utilisateur->est_actif)->toBeTrue();
    expect($utilisateur->telephone_verifie_le)->toBeNull();
    expect($utilisateur->hasRole('livreur'))->toBeTrue();

    expect($profil->statut)->toBe(StatutLivreur::HorsLigne);
    expect($profil->type_vehicule)->toBe(TypeVehicule::Moto);
    expect($profil->plaque)->toBe('1234 AB 01');
    expect($profil->est_valide)->toBeTrue();
});

test('un livreur peut être créé non validé', function () {
    $profil = app(CreerLivreurAction::class)->executer(donneesLivreur(['est_valide' => false]));

    expect($profil->est_valide)->toBeFalse();
});

test('un numéro déjà utilisé est refusé', function () {
    $creer = app(CreerLivreurAction::class);
    $creer->executer(donneesLivreur());

    expect(fn () => $creer->executer(donneesLivreur(['nom' => 'Autre personne'])))
        ->toThrow(DomainException::class, 'déjà utilisé');

    expect(Utilisateur::count())->toBe(1);
});

test('le numéro d\'un compte supprimé reste réservé', function () {
    Utilisateur::factory()->create(['telephone' => '+2250700000123'])->delete();

    expect(fn () => app(CreerLivreurAction::class)->executer(donneesLivreur()))
        ->toThrow(DomainException::class);
});

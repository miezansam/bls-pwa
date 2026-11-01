<?php

declare(strict_types=1);

use App\Actions\ReverserCaisseAction;
use App\Models\ProfilLivreur;
use App\Models\ReversementCaisse;
use App\Models\Utilisateur;

function livreurAvecCaisse(int $solde): array
{
    $livreur = Utilisateur::factory()->livreur()->create();
    ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->update(['solde_caisse' => $solde]);

    return [$livreur, ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->firstOrFail()];
}

test('un reversement partiel diminue la caisse et laisse une trace', function () {
    [$livreur, $profil] = livreurAvecCaisse(1500);
    $admin = Utilisateur::factory()->admin()->create();

    $reversement = app(ReverserCaisseAction::class)->executer($profil, 1000, $admin, 'Remis en main propre');

    expect($profil->fresh()->solde_caisse)->toBe(500);
    expect($reversement->montant)->toBe(1000);
    expect($reversement->livreur_id)->toBe($livreur->id);
    expect($reversement->recu_par)->toBe($admin->id);
    expect($reversement->note)->toBe('Remis en main propre');
});

test('un reversement total ramène la caisse à zéro', function () {
    [, $profil] = livreurAvecCaisse(800);
    $admin = Utilisateur::factory()->admin()->create();

    app(ReverserCaisseAction::class)->executer($profil, 800, $admin);

    expect($profil->fresh()->solde_caisse)->toBe(0);
});

test('on ne peut pas reverser plus que la caisse', function () {
    [, $profil] = livreurAvecCaisse(500);
    $admin = Utilisateur::factory()->admin()->create();

    expect(fn () => app(ReverserCaisseAction::class)->executer($profil, 501, $admin))
        ->toThrow(DomainException::class, 'dépasse');

    expect($profil->fresh()->solde_caisse)->toBe(500);
    expect(ReversementCaisse::count())->toBe(0);
});

test('un montant nul ou négatif est refusé', function () {
    [, $profil] = livreurAvecCaisse(500);
    $admin = Utilisateur::factory()->admin()->create();

    foreach ([0, -100] as $montant) {
        expect(fn () => app(ReverserCaisseAction::class)->executer($profil, $montant, $admin))
            ->toThrow(DomainException::class);
    }

    expect($profil->fresh()->solde_caisse)->toBe(500);
});

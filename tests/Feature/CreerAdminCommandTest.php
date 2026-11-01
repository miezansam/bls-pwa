<?php

declare(strict_types=1);

use App\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;

test('la commande crée un administrateur avec le rôle admin et un mot de passe haché', function () {
    $this->artisan('bls:creer-admin', [
        'email' => 'admin@example.test',
        '--nom' => 'Admin Test',
        '--telephone' => '+2250700000099',
    ])
        ->expectsQuestion('Mot de passe (12 caractères minimum)', 'un-mot-de-passe-solide')
        ->assertSuccessful();

    $admin = Utilisateur::query()->where('email', 'admin@example.test')->firstOrFail();

    expect($admin->hasRole('admin'))->toBeTrue();
    expect($admin->est_actif)->toBeTrue();
    expect($admin->password)->not->toBe('un-mot-de-passe-solide');
    expect(Hash::check('un-mot-de-passe-solide', $admin->password))->toBeTrue();
});

test('la commande refuse un mot de passe trop court', function () {
    $this->artisan('bls:creer-admin', [
        'email' => 'admin@example.test',
        '--nom' => 'Admin Test',
        '--telephone' => '+2250700000099',
    ])
        ->expectsQuestion('Mot de passe (12 caractères minimum)', 'court')
        ->assertFailed();

    expect(Utilisateur::query()->where('email', 'admin@example.test')->exists())->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Models\Utilisateur;
use Filament\Facades\Filament;

test('un visiteur non connecté est redirigé vers la connexion admin', function () {
    $this->get('/admin')->assertRedirectContains('/admin/login');
});

test('la page de connexion admin s\'affiche', function () {
    $this->get('/admin/login')->assertOk();
});

test('seul un administrateur actif peut accéder au panneau', function () {
    $panneau = Filament::getPanel('admin');

    $admin = Utilisateur::factory()->admin()->create();
    $adminDesactive = Utilisateur::factory()->admin()->create(['est_actif' => false]);
    $client = Utilisateur::factory()->client()->create();
    $livreur = Utilisateur::factory()->livreur()->create();

    expect($admin->canAccessPanel($panneau))->toBeTrue();
    expect($adminDesactive->canAccessPanel($panneau))->toBeFalse();
    expect($client->canAccessPanel($panneau))->toBeFalse();
    expect($livreur->canAccessPanel($panneau))->toBeFalse();
});

test('le tableau de bord est ouvert à l\'admin et interdit au client', function () {
    $this->actingAs(Utilisateur::factory()->admin()->create())->get('/admin')->assertOk();
    $this->actingAs(Utilisateur::factory()->client()->create())->get('/admin')->assertForbidden();
});

test('la liste des commandes affiche les commandes à l\'admin', function () {
    $commande = commandeDeTest();

    $this->actingAs(Utilisateur::factory()->admin()->create())
        ->get('/admin/commandes')
        ->assertOk()
        ->assertSee($commande->reference);
});

test('la liste des commandes est interdite à un livreur', function () {
    $this->actingAs(Utilisateur::factory()->livreur()->create())
        ->get('/admin/commandes')
        ->assertForbidden();
});

<?php

declare(strict_types=1);

use App\Models\Utilisateur;
use App\Models\Zone;

dataset('ecrans_gestion', [
    'zones' => '/admin/zones',
    'tarifs' => '/admin/tarifs',
    'livreurs' => '/admin/livreurs',
    'clients' => '/admin/clients',
    'reversements' => '/admin/reversements-caisse',
    'options' => '/admin/options-tarifaires',
]);

test('un administrateur accède aux écrans de gestion', function (string $url) {
    $this->actingAs(Utilisateur::factory()->admin()->create())->get($url)->assertOk();
})->with('ecrans_gestion');

test('un livreur n\'accède pas aux écrans de gestion', function (string $url) {
    $this->actingAs(Utilisateur::factory()->livreur()->create())->get($url)->assertForbidden();
})->with('ecrans_gestion');

test('la liste des zones affiche les zones', function () {
    creerZone('Quartier Test Zone');

    $this->actingAs(Utilisateur::factory()->admin()->create())
        ->get('/admin/zones')
        ->assertOk()
        ->assertSee('Quartier Test Zone');
});

test('la liste des livreurs affiche les livreurs', function () {
    Utilisateur::factory()->livreur()->create(['nom' => 'Koffi Livreur Test']);

    $this->actingAs(Utilisateur::factory()->admin()->create())
        ->get('/admin/livreurs')
        ->assertOk()
        ->assertSee('Koffi Livreur Test');
});

test('la liste des clients n\'affiche que des clients', function () {
    Utilisateur::factory()->client()->create(['nom' => 'Aya Cliente Test']);
    Utilisateur::factory()->livreur()->create(['nom' => 'Koffi Livreur Test']);

    $this->actingAs(Utilisateur::factory()->admin()->create())
        ->get('/admin/clients')
        ->assertOk()
        ->assertSee('Aya Cliente Test')
        ->assertDontSee('Koffi Livreur Test');
});

test('le slug d\'une zone est généré à partir de son nom', function () {
    $zone = Zone::create(['nom' => 'Quartier Résidentiel']);

    expect($zone->slug)->toBe('quartier-residentiel');
});

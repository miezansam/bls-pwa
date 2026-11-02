<?php

declare(strict_types=1);

use App\Livewire\Auth\Connexion;
use App\Livewire\Auth\Inscription;
use App\Models\Utilisateur;
use Livewire\Livewire;

// Les mises en page chargent Vite : sans les fichiers compilés (CI, première installation), on le neutralise
beforeEach(fn () => $this->withoutVite());

test('la page de connexion s\'affiche pour un visiteur', function () {
    $this->get('/connexion')->assertOk()->assertSee('Recevoir mon code');
});

test('la page d\'inscription s\'affiche pour un visiteur', function () {
    $this->get('/inscription')->assertOk()->assertSee('Créer mon compte');
});

test('un utilisateur déjà connecté est renvoyé vers son espace', function () {
    $this->actingAs(Utilisateur::factory()->client()->create())
        ->get('/connexion')
        ->assertRedirect(route('accueil'));
});

test('l\'accueil oriente chacun vers son espace', function () {
    $this->get('/')->assertRedirect(route('login'));

    $this->actingAs(Utilisateur::factory()->client()->create())->get('/')->assertRedirect(route('client.accueil'));
    $this->actingAs(Utilisateur::factory()->livreur()->create())->get('/')->assertRedirect(route('livreur.accueil'));
    $this->actingAs(Utilisateur::factory()->admin()->create())->get('/')->assertRedirect('/admin');
});

test('chaque espace est réservé à son rôle', function () {
    $client = Utilisateur::factory()->client()->create();
    $livreur = Utilisateur::factory()->livreur()->create();

    $this->get('/client')->assertRedirect(route('login'));

    $this->actingAs($client)->get('/client')->assertOk()->assertSee($client->nom);
    $this->actingAs($client)->get('/livreur')->assertForbidden();

    $this->actingAs($livreur)->get('/livreur')->assertOk();
    $this->actingAs($livreur)->get('/client')->assertForbidden();
});

test('connexion complète : numéro, code, session ouverte et redirection', function () {
    $sms = smsFictif();
    $client = Utilisateur::factory()->client()->create(['telephone' => '+2250700000001']);

    Livewire::test(Connexion::class)
        ->set('telephone', '07 00 00 00 01')
        ->call('demanderCode')
        ->assertHasNoErrors()
        ->assertSet('etape', 'code')
        ->assertSet('telephone', '+2250700000001')
        ->set('code', $sms->dernierCode())
        ->call('verifierCode')
        ->assertHasNoErrors()
        ->assertRedirect(route('accueil'));

    $this->assertAuthenticatedAs($client);
});

test('un numéro sans compte est refusé à la connexion, sans SMS', function () {
    $sms = smsFictif();

    Livewire::test(Connexion::class)
        ->set('telephone', '0700000099')
        ->call('demanderCode')
        ->assertHasErrors('telephone')
        ->assertSet('etape', 'telephone');

    expect($sms->messages)->toBeEmpty();
});

test('un mauvais code ne connecte pas', function () {
    $sms = smsFictif();
    Utilisateur::factory()->client()->create(['telephone' => '+2250700000001']);

    $ecran = Livewire::test(Connexion::class)->set('telephone', '0700000001')->call('demanderCode');
    $mauvais = $sms->dernierCode() === '000000' ? '111111' : '000000';

    $ecran->set('code', $mauvais)
        ->call('verifierCode')
        ->assertHasErrors('code')
        ->assertSet('etape', 'code');

    $this->assertGuest();
});

test('un code vide ou mal formé est refusé avant toute vérification', function () {
    smsFictif();
    Utilisateur::factory()->client()->create(['telephone' => '+2250700000001']);

    $ecran = Livewire::test(Connexion::class)->set('telephone', '0700000001')->call('demanderCode');

    $ecran->set('code', '')->call('verifierCode')->assertHasErrors(['code' => 'required']);
    $ecran->set('code', '12ab')->call('verifierCode')->assertHasErrors(['code' => 'digits']);
});

test('inscription complète : compte client créé et session ouverte', function () {
    $sms = smsFictif();

    Livewire::test(Inscription::class)
        ->set('nom', 'Aya Kouassi')
        ->set('telephone', '07 00 00 00 02')
        ->call('demanderCode')
        ->assertHasNoErrors()
        ->assertSet('etape', 'code')
        ->set('code', $sms->dernierCode())
        ->call('verifierCode')
        ->assertHasNoErrors()
        ->assertRedirect(route('accueil'));

    $client = Utilisateur::query()->where('telephone', '+2250700000002')->firstOrFail();

    expect($client->nom)->toBe('Aya Kouassi');
    expect($client->hasRole('client'))->toBeTrue();
    $this->assertAuthenticatedAs($client);
});

test('l\'inscription exige un nom et n\'envoie pas de SMS sans lui', function () {
    $sms = smsFictif();

    Livewire::test(Inscription::class)
        ->set('nom', '')
        ->set('telephone', '0700000002')
        ->call('demanderCode')
        ->assertHasErrors(['nom' => 'required'])
        ->assertSet('etape', 'telephone');

    expect($sms->messages)->toBeEmpty();
});

test('l\'inscription refuse un numéro déjà inscrit', function () {
    $sms = smsFictif();
    Utilisateur::factory()->client()->create(['telephone' => '+2250700000002']);

    Livewire::test(Inscription::class)
        ->set('nom', 'Aya Kouassi')
        ->set('telephone', '0700000002')
        ->call('demanderCode')
        ->assertHasErrors('telephone');

    expect($sms->messages)->toBeEmpty();
});

test('renvoyer un code immédiatement est refusé', function () {
    smsFictif();

    Livewire::test(Inscription::class)
        ->set('nom', 'Aya Kouassi')
        ->set('telephone', '0700000021')
        ->call('demanderCode')
        ->call('renvoyerCode')
        ->assertHasErrors('telephone');
});

test('trop de demandes de code depuis le même appareil sont bloquées', function () {
    smsFictif();
    config(['bls.otp.ip_demandes_max_par_heure' => 2]);

    $ecran = Livewire::test(Inscription::class)->set('nom', 'Aya Kouassi');

    foreach (['0700000011', '0700000012'] as $numero) {
        $ecran->set('telephone', $numero)->call('demanderCode')->assertHasNoErrors();
    }

    $ecran->set('telephone', '0700000013')
        ->call('demanderCode')
        ->assertHasErrors('telephone')
        ->assertSee('Trop de tentatives');
});

test('trop de saisies de code depuis le même appareil sont bloquées', function () {
    $sms = smsFictif();
    config(['bls.otp.ip_verifications_max_par_heure' => 2]);
    Utilisateur::factory()->client()->create(['telephone' => '+2250700000001']);

    $ecran = Livewire::test(Connexion::class)->set('telephone', '0700000001')->call('demanderCode');
    $mauvais = $sms->dernierCode() === '000000' ? '111111' : '000000';

    $ecran->set('code', $mauvais)->call('verifierCode');
    $ecran->set('code', $mauvais)->call('verifierCode');

    $ecran->set('code', $mauvais)
        ->call('verifierCode')
        ->assertSee('Trop de tentatives');

    $this->assertGuest();
});

test('la déconnexion ferme la session', function () {
    $this->actingAs(Utilisateur::factory()->client()->create())
        ->post('/deconnexion')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('la page d\'inscription annonce le prix le plus bas de livraison', function () {
    creerZoneAvecFrais('Ville', 500);
    creerZoneAvecFrais('Agou', 2000);

    $this->get('/inscription')->assertOk()->assertSee('500')->assertDontSee('2 000');
});

test('une zone désactivée n\'entre pas dans le prix annoncé', function () {
    creerZoneAvecFrais('Ville', 500);
    creerZoneAvecFrais('Zone fermée', 350, active: false);

    $this->get('/inscription')->assertOk()->assertSee('500')->assertDontSee('350');
});

<?php

declare(strict_types=1);

it('affiche la page hors ligne sans connexion et sans jeton CSRF', function () {
    $this->get('/hors-ligne')
        ->assertOk()
        ->assertSee('Pas de connexion')
        ->assertSee('images/logo-inverse.png', false)
        ->assertDontSee('csrf-token', false);
});

it('sert le service worker depuis la racine', function () {
    $reponse = $this->get('/sw.js')->assertOk();

    expect($reponse->headers->get('Content-Type'))->toContain('javascript')
        ->and($reponse->headers->get('Cache-Control'))->toContain('no-cache');
});

it('ne met jamais de page en cache dans le service worker', function () {
    $source = file_get_contents(resource_path('js/sw.js'));

    expect($source)->toContain('NetworkOnly')
        ->and($source)->not->toContain('CacheFirst')
        ->and($source)->not->toContain('StaleWhileRevalidate')
        ->and($source)->not->toContain('NetworkFirst');
});

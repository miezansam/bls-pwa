<?php

declare(strict_types=1);

it('déclare le thème marine, le manifeste et le logo inversé', function () {
    $this->get('/connexion')
        ->assertOk()
        ->assertSee('<meta name="theme-color" content="#011F48">', false)
        ->assertSee('manifest.webmanifest', false)
        ->assertSee('images/logo-inverse.png', false);
});

it('utilise le nom officiel', function () {
    expect(config('app.name'))->toBe('Béni Livraison Service');
});

it('fournit un manifeste marine et les fichiers d\'identité', function () {
    $manifeste = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

    expect(strtolower($manifeste['theme_color']))->toBe('#011f48')
        ->and(strtolower($manifeste['background_color']))->toBe('#011f48');

    foreach ([
        'images/logo-inverse.png',
        'images/logo-compact.png',
        'images/logo-compact-inverse.png',
        'images/icons/icon-192.png',
        'images/icons/icon-512.png',
        'images/icons/icon-maskable-512.png',
        'favicon.ico',
        'apple-touch-icon.png',
    ] as $chemin) {
        expect(public_path($chemin))->toBeFile();
    }
});

it('n\'a aucune couleur en dur ni reste de vert dans les vues', function () {
    $fichiers = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

    foreach ($fichiers as $fichier) {
        if (! $fichier->isFile() || ! str_ends_with($fichier->getFilename(), '.blade.php')) {
            continue;
        }
        $contenu = file_get_contents($fichier->getPathname());
        expect($contenu)->not->toMatch('/#[0-9a-fA-F]{6}\b/', $fichier->getFilename())
            ->and($contenu)->not->toMatch('/(bg|text|border)-(green|vert)/', $fichier->getFilename());
    }
});

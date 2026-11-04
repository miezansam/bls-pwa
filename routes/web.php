<?php

declare(strict_types=1);

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\DeconnexionController;
use App\Livewire\Auth\Connexion;
use App\Livewire\Auth\Inscription;
use App\Livewire\Client\Accueil as AccueilClient;
use App\Livewire\Livreur\Accueil as AccueilLivreur;
use Illuminate\Support\Facades\Route;
use App\Livewire\Client\NouvelleCommande;
use App\Livewire\Client\SuiviCommande;
use App\Http\Controllers\PositionLivreurController;

Route::get('/', AccueilController::class)->name('accueil');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', Connexion::class)->name('login');
    Route::get('/inscription', Inscription::class)->name('inscription');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', DeconnexionController::class)->name('deconnexion');

    Route::get('/client', AccueilClient::class)->middleware('role:client')->name('client.accueil');
    Route::get('/livreur', AccueilLivreur::class)->middleware('role:livreur')->name('livreur.accueil');
});


Route::get('/client/commander', NouvelleCommande::class)->middleware('role:client')->name('client.commander');
Route::get('/client/commandes/{commande:reference}', SuiviCommande::class)->middleware('role:client')->name('client.commande');


Route::post('/livreur/position', PositionLivreurController::class)
        ->middleware(['role:livreur', 'throttle:60,1'])
        ->name('livreur.position');

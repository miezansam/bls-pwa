<?php

declare(strict_types=1);

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\DeconnexionController;
use App\Livewire\Auth\Connexion;
use App\Livewire\Auth\Inscription;
use App\Livewire\Client\Accueil as AccueilClient;
use App\Livewire\Livreur\Accueil as AccueilLivreur;
use Illuminate\Support\Facades\Route;

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

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleUtilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AccueilController
{
    /** Envoie chacun vers son espace. */
    public function __invoke(Request $request): RedirectResponse
    {
        $utilisateur = $request->user();

        if ($utilisateur === null) {
            return redirect()->route('login');
        }

        return match (true) {
            $utilisateur->hasRole(RoleUtilisateur::Livreur->value) => redirect()->route('livreur.accueil'),
            $utilisateur->hasRole(RoleUtilisateur::Client->value) => redirect()->route('client.accueil'),
            default => redirect('/admin'),
        };
    }
}

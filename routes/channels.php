<?php

declare(strict_types=1);

use App\Models\Commande;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('commande.{commandeId}', function (Utilisateur $utilisateur, int $commandeId): bool {
    return Commande::query()
        ->whereKey($commandeId)
        ->where(fn ($q) => $q->where('client_id', $utilisateur->id)->orWhere('livreur_id', $utilisateur->id))
        ->exists();
});

Broadcast::channel('livreur.{livreurId}', function (Utilisateur $utilisateur, int $livreurId): bool {
    return $utilisateur->id === $livreurId;
});

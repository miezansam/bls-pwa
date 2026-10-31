<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatutCommande;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueStatut extends Model
{
    public const UPDATED_AT = null;   // la table n'a pas de updated_at

    protected $table = 'historique_statuts';

    protected $fillable = [
        'commande_id', 'ancien_statut', 'nouveau_statut', 'auteur_id', 'note', 'latitude', 'longitude',
    ];

    protected function casts(): array
    {
        return [
            'ancien_statut' => StatutCommande::class,
            'nouveau_statut' => StatutCommande::class,
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'auteur_id');
    }
}

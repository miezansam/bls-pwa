<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatutLivreur;
use App\Enums\TypeVehicule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilLivreur extends Model
{
    protected $table = 'profils_livreurs';

    protected $fillable = [
        'utilisateur_id', 'statut', 'type_vehicule', 'plaque', 'est_valide',
        'derniere_latitude', 'derniere_longitude', 'derniere_precision_m',
        'derniere_position_le', 'solde_caisse',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutLivreur::class,
            'type_vehicule' => TypeVehicule::class,
            'est_valide' => 'boolean',
            'derniere_latitude' => 'float',
            'derniere_longitude' => 'float',
            'derniere_position_le' => 'datetime',
            'solde_caisse' => 'integer',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function scopeDisponibles(Builder $requete): Builder
    {
        return $requete->where('statut', StatutLivreur::Disponible)->where('est_valide', true);
    }
}

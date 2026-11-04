<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PositionLivreur extends Model
{
    protected $table = 'positions_livreurs';

    /** created_at est rempli par la base, il n'y a pas de updated_at. */
    public $timestamps = false;

    protected $fillable = [
        'livreur_id', 'commande_id', 'latitude', 'longitude',
        'precision_m', 'vitesse_kmh', 'cap', 'enregistree_le',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'precision_m' => 'integer',
            'vitesse_kmh' => 'integer',
            'cap' => 'integer',
            'enregistree_le' => 'datetime',
        ];
    }
}

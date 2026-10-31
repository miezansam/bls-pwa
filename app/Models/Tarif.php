<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModeTarif;
use App\Enums\TypeCommande;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarif extends Model
{
    protected $table = 'tarifs';

    protected $fillable = [
        'nom', 'mode', 'type_commande', 'zone_retrait_id', 'zone_livraison_id',
        'frais_forfait', 'frais_base', 'frais_par_km', 'frais_minimum', 'est_actif',
    ];

    protected function casts(): array
    {
        return [
            'mode' => ModeTarif::class,
            'type_commande' => TypeCommande::class,
            'frais_forfait' => 'integer',
            'frais_base' => 'integer',
            'frais_par_km' => 'integer',
            'frais_minimum' => 'integer',
            'est_actif' => 'boolean',
        ];
    }

    public function zoneRetrait(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_retrait_id');
    }

    public function zoneLivraison(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_livraison_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Zone extends Model
{
    protected $table = 'zones';

    protected $fillable = [
        'nom', 'slug', 'description', 'frais_livraison', 'accepte_livraison_nuit',
        'centre_latitude', 'centre_longitude', 'polygone', 'est_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Zone $zone): void {
            if (blank($zone->slug)) {
                $zone->slug = Str::slug($zone->nom);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'frais_livraison' => 'integer',
            'accepte_livraison_nuit' => 'boolean',
            'centre_latitude' => 'float',
            'centre_longitude' => 'float',
            'polygone' => 'array',
            'est_active' => 'boolean',
        ];
    }

    public function tarifsDepart(): HasMany
    {
        return $this->hasMany(Tarif::class, 'zone_retrait_id');
    }

    public function tarifsArrivee(): HasMany
    {
        return $this->hasMany(Tarif::class, 'zone_livraison_id');
    }
}

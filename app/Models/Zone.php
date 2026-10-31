<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $table = 'zones';

    protected $fillable = [
        'nom', 'slug', 'description', 'centre_latitude', 'centre_longitude', 'polygone', 'est_active',
    ];

    protected function casts(): array
    {
        return [
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

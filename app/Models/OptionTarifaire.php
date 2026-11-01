<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CodeOption;
use App\Enums\RegleOption;
use Illuminate\Database\Eloquent\Model;

class OptionTarifaire extends Model
{
    protected $table = 'options_tarifaires';

    protected $fillable = [
        'code', 'regle', 'montant', 'heure_debut', 'heure_fin', 'nombre_minimum', 'est_active',
    ];

    protected function casts(): array
    {
        return [
            'code' => CodeOption::class,
            'regle' => RegleOption::class,
            'montant' => 'integer',
            'heure_debut' => 'integer',
            'heure_fin' => 'integer',
            'nombre_minimum' => 'integer',
            'est_active' => 'boolean',
        ];
    }
}

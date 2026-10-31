<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatutAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attribution extends Model
{
    protected $table = 'attributions';

    protected $fillable = [
        'commande_id', 'livreur_id', 'attribuee_par', 'statut',
        'proposee_le', 'repondue_le', 'expire_le',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutAttribution::class,
            'proposee_le' => 'datetime',
            'repondue_le' => 'datetime',
            'expire_le' => 'datetime',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function livreur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'livreur_id');
    }

    public function attribueePar(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'attribuee_par');
    }
}

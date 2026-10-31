<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReversementCaisse extends Model
{
    protected $table = 'reversements_caisse';

    protected $fillable = ['livreur_id', 'recu_par', 'montant', 'note', 'reverse_le'];

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
            'reverse_le' => 'datetime',
        ];
    }

    public function livreur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'livreur_id');
    }

    public function recuPar(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'recu_par');
    }
}

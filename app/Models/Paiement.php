<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModePaiement;
use App\Enums\StatutPaiement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $table = 'paiements';

    protected $fillable = [
        'commande_id', 'utilisateur_id', 'mode', 'statut', 'montant', 'devise',
        'telephone_payeur', 'fournisseur', 'reference_fournisseur',
        'donnees_fournisseur', 'motif_echec', 'paye_le',
    ];

    protected $hidden = ['donnees_fournisseur'];

    protected function casts(): array
    {
        return [
            'mode' => ModePaiement::class,
            'statut' => StatutPaiement::class,
            'montant' => 'integer',
            'donnees_fournisseur' => 'array',
            'paye_le' => 'datetime',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function payeur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }
}

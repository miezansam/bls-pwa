<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModePaiement;
use App\Enums\StatutCommande;
use App\Enums\StatutPaiement;
use App\Enums\TypeCommande;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Commande extends Model
{
    protected $table = 'commandes';

    protected $fillable = [
        'reference', 'client_id', 'livreur_id', 'type', 'statut',
        'retrait_adresse_id', 'retrait_zone_id', 'retrait_latitude', 'retrait_longitude',
        'retrait_repere', 'retrait_contact_nom', 'retrait_contact_telephone',
        'livraison_adresse_id', 'livraison_zone_id', 'livraison_latitude', 'livraison_longitude',
        'livraison_repere', 'livraison_contact_nom', 'livraison_contact_telephone',
        'description', 'photo_chemin', 'note_livreur',
        'distance_m', 'duree_estimee_s', 'frais_livraison', 'montant_articles', 'montant_total',
        'mode_paiement', 'statut_paiement', 'code_livraison', 'code_livraison_tentatives',
        'attribuee_le', 'acceptee_le', 'recuperee_le', 'livree_le',
        'annulee_le', 'annulee_par', 'motif_annulation',
    ];

    /** Le code de livraison ne doit jamais fuiter vers le livreur via l'API. */
    protected $hidden = ['code_livraison'];

    protected function casts(): array
    {
        return [
            'type' => TypeCommande::class,
            'statut' => StatutCommande::class,
            'mode_paiement' => ModePaiement::class,
            'statut_paiement' => StatutPaiement::class,
            'retrait_latitude' => 'float',
            'retrait_longitude' => 'float',
            'livraison_latitude' => 'float',
            'livraison_longitude' => 'float',
            'distance_m' => 'integer',
            'duree_estimee_s' => 'integer',
            'frais_livraison' => 'integer',
            'montant_articles' => 'integer',
            'montant_total' => 'integer',
            'code_livraison_tentatives' => 'integer',
            'attribuee_le' => 'datetime',
            'acceptee_le' => 'datetime',
            'recuperee_le' => 'datetime',
            'livree_le' => 'datetime',
            'annulee_le' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'client_id');
    }

    public function livreur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'livreur_id');
    }

    public function annuleePar(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'annulee_par');
    }

    public function adresseRetrait(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'retrait_adresse_id');
    }

    public function adresseLivraison(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'livraison_adresse_id');
    }

    public function zoneRetrait(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'retrait_zone_id');
    }

    public function zoneLivraison(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'livraison_zone_id');
    }

    public function historiqueStatuts(): HasMany
    {
        return $this->hasMany(HistoriqueStatut::class, 'commande_id')->orderBy('created_at');
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(Attribution::class, 'commande_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'commande_id');
    }

    public function dernierPaiement(): HasOne
    {
        return $this->hasOne(Paiement::class, 'commande_id')->latestOfMany();
    }

    public function positions(): HasMany
    {
        return $this->hasMany(PositionLivreur::class, 'commande_id');
    }

    public function scopeEnCours(Builder $requete): Builder
    {
        return $requete->whereNotIn('statut', [StatutCommande::Livree, StatutCommande::Annulee]);
    }

    public function scopeDuLivreur(Builder $requete, int $livreurId): Builder
    {
        return $requete->where('livreur_id', $livreurId);
    }
}

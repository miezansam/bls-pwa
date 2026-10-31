<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Utilisateur extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'nom', 'telephone', 'telephone_verifie_le', 'email', 'password',
        'avatar_chemin', 'langue', 'est_actif', 'derniere_connexion_le',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'telephone_verifie_le' => 'datetime',
            'derniere_connexion_le' => 'datetime',
            'password' => 'hashed',
            'est_actif' => 'boolean',
        ];
    }

    public function profilLivreur(): HasOne
    {
        return $this->hasOne(ProfilLivreur::class, 'utilisateur_id');
    }

    public function adresses(): HasMany
    {
        return $this->hasMany(Adresse::class, 'utilisateur_id');
    }

    /** Commandes passées en tant que client. */
    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class, 'client_id');
    }

    /** Courses effectuées en tant que livreur. */
    public function livraisons(): HasMany
    {
        return $this->hasMany(Commande::class, 'livreur_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(PositionLivreur::class, 'livreur_id');
    }

    public function reversementsCaisse(): HasMany
    {
        return $this->hasMany(ReversementCaisse::class, 'livreur_id');
    }
}

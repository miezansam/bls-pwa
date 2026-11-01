<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutLivreur;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use DomainException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class CreerLivreurAction
{
    /**
     * @param array{nom: string, telephone: string, type_vehicule: mixed, plaque?: ?string, est_valide?: bool} $donnees
     */
    public function executer(array $donnees): ProfilLivreur
    {
        $telephone = (string) $donnees['telephone'];

        // Les comptes supprimés comptent aussi : l'index unique de la base les inclut
        if (Utilisateur::withTrashed()->where('telephone', $telephone)->exists()) {
            throw new DomainException('Ce numéro de téléphone est déjà utilisé.');
        }

        return DB::transaction(function () use ($donnees, $telephone) {
            $utilisateur = Utilisateur::create([
                'nom' => $donnees['nom'],
                'telephone' => $telephone,
                'est_actif' => true,
            ]);

            Role::findOrCreate(RoleUtilisateur::Livreur->value, 'web');
            $utilisateur->assignRole(RoleUtilisateur::Livreur->value);

            return ProfilLivreur::create([
                'utilisateur_id' => $utilisateur->id,
                'statut' => StatutLivreur::HorsLigne,
                'type_vehicule' => $donnees['type_vehicule'],
                'plaque' => $donnees['plaque'] ?? null,
                'est_valide' => $donnees['est_valide'] ?? false,
            ]);
        });
    }
}

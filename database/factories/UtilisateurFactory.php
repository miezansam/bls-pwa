<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutLivreur;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/** @extends Factory<Utilisateur> */
class UtilisateurFactory extends Factory
{
    protected $model = Utilisateur::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'telephone' => '+22507' . fake()->unique()->numerify('########'),
            'telephone_verifie_le' => now(),
            'langue' => 'fr',
            'est_actif' => true,
        ];
    }

    public function client(): static
    {
        return $this->afterCreating(function (Utilisateur $utilisateur) {
            Role::findOrCreate(RoleUtilisateur::Client->value, 'web');
            $utilisateur->assignRole(RoleUtilisateur::Client->value);
        });
    }

    public function livreur(): static
    {
        return $this->afterCreating(function (Utilisateur $utilisateur) {
            Role::findOrCreate(RoleUtilisateur::Livreur->value, 'web');
            $utilisateur->assignRole(RoleUtilisateur::Livreur->value);

            ProfilLivreur::create([
                'utilisateur_id' => $utilisateur->id,
                'statut' => StatutLivreur::Disponible,
                'est_valide' => true,
            ]);
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function (Utilisateur $utilisateur) {
            Role::findOrCreate(RoleUtilisateur::Admin->value, 'web');
            $utilisateur->assignRole(RoleUtilisateur::Admin->value);
        });
    }
}

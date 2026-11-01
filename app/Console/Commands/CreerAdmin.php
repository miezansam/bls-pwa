<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RoleUtilisateur;
use App\Models\Utilisateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreerAdmin extends Command
{
    protected $signature = 'bls:creer-admin
        {email : Adresse email de connexion}
        {--nom= : Nom affiché}
        {--telephone= : Numéro au format international, ex : +2250700000000}';

    protected $description = 'Crée un compte administrateur pour le panneau d\'administration';

    public function handle(): int
    {
        $donnees = [
            'email' => (string) $this->argument('email'),
            'nom' => (string) ($this->option('nom') ?: $this->ask('Nom complet')),
            'telephone' => (string) ($this->option('telephone') ?: $this->ask('Téléphone (ex : +2250700000000)')),
            'mot_de_passe' => (string) $this->secret('Mot de passe (12 caractères minimum)'),
        ];

        $validateur = Validator::make($donnees, [
            'email' => ['required', 'email', 'unique:utilisateurs,email'],
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'regex:/^\+\d{8,15}$/', 'unique:utilisateurs,telephone'],
            'mot_de_passe' => ['required', 'string', 'min:12'],
        ], [], [
            'nom' => 'nom',
            'telephone' => 'téléphone',
            'mot_de_passe' => 'mot de passe',
        ]);

        if ($validateur->fails()) {
            foreach ($validateur->errors()->all() as $erreur) {
                $this->error($erreur);
            }

            return self::FAILURE;
        }

        Role::findOrCreate(RoleUtilisateur::Admin->value, 'web');

        $admin = Utilisateur::create([
            'nom' => $donnees['nom'],
            'telephone' => $donnees['telephone'],
            'telephone_verifie_le' => now(),
            'email' => $donnees['email'],
            'password' => $donnees['mot_de_passe'],   // haché automatiquement par le cast du modèle
            'est_actif' => true,
        ]);

        $admin->assignRole(RoleUtilisateur::Admin->value);

        $this->info("Administrateur créé : {$admin->email}");

        return self::SUCCESS;
    }
}

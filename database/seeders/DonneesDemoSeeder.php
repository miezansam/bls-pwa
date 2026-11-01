<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AccepterAttributionAction;
use App\Actions\AttribuerCommandeAction;
use App\Actions\ChangerStatutCommandeAction;
use App\Actions\ConfirmerLivraisonAction;
use App\Actions\CreerCommandeAction;
use App\Enums\ModePaiement;
use App\Enums\RoleUtilisateur;
use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Enums\TypeCommande;
use App\Models\Commande;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/** Données fictives pour le développement local : ne jamais exécuter en production. */
class DonneesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->creerAdminLocal();

        if (Commande::query()->exists()) {
            return;
        }

        $ville = Zone::query()->where('slug', 'ville-adzope')->first();
        $aquaParc = Zone::query()->where('slug', 'aqua-parc')->first();

        if ($ville === null || $aquaParc === null) {
            $this->command?->warn('Zones introuvables : lance d\'abord TarifsOfficielsSeeder.');

            return;
        }

        [$koffi, $awa, $yao] = [
            Utilisateur::factory()->livreur()->create(['nom' => 'Koffi (livreur démo)']),
            Utilisateur::factory()->livreur()->create(['nom' => 'Awa (livreuse démo)']),
            Utilisateur::factory()->livreur()->create(['nom' => 'Yao (livreur démo)']),
        ];

        ProfilLivreur::query()->where('utilisateur_id', $yao->id)->update(['statut' => StatutLivreur::HorsLigne->value]);

        [$aya, $kone, $brou] = [
            Utilisateur::factory()->client()->create(['nom' => 'Aya (cliente démo)']),
            Utilisateur::factory()->client()->create(['nom' => 'Koné (client démo)']),
            Utilisateur::factory()->client()->create(['nom' => 'Brou (client démo)']),
        ];

        $creer = fn (Utilisateur $client, array $surcharges = []): Commande => app(CreerCommandeAction::class)
            ->executer($client, $this->donnees($ville, $aquaParc, $surcharges));

        $changer = app(ChangerStatutCommandeAction::class);
        $attribuer = app(AttribuerCommandeAction::class);
        $accepter = app(AccepterAttributionAction::class);

        // 1. Une course livrée par Koffi : sa caisse contient 800 FCFA à reverser
        $livree = $creer($aya);
        $accepter->executer($attribuer->executer($livree, $koffi, $admin), $koffi);

        foreach ([StatutCommande::EnRouteRetrait, StatutCommande::ColisRecupere, StatutCommande::EnRouteLivraison] as $statut) {
            $livree = $changer->executer($livree, $statut, auteur: $koffi);
        }

        app(ConfirmerLivraisonAction::class)->executer($livree, $livree->code_livraison, $koffi);

        // 2. Une course acceptée par Awa
        $acceptee = $creer($kone);
        $accepter->executer($attribuer->executer($acceptee, $awa, $admin), $awa);

        // 3. Un repas en attente, avec une offre envoyée à Koffi
        $repas = $creer($brou, ['type' => TypeCommande::Repas, 'montant_articles' => 2500, 'description' => 'Poulet braisé et alloco']);
        $attribuer->executer($repas, $koffi, $admin);

        // 4. Un colis en attente, sans offre
        $creer($aya, ['description' => 'Paquet de vêtements']);

        // 5. Une commande annulée
        $annulee = $creer($kone, ['description' => 'Commande annulée par le client']);
        $changer->executer($annulee, StatutCommande::Annulee, auteur: $kone, note: 'Changement de plan');
    }

    private function creerAdminLocal(): ?Utilisateur
    {
        $email = config('bls.dev_admin.email');
        $motDePasse = config('bls.dev_admin.mot_de_passe');

        if (blank($email) || blank($motDePasse)) {
            return null;
        }

        $existant = Utilisateur::query()->where('email', $email)->first();

        if ($existant !== null) {
            return $existant;
        }

        Role::findOrCreate(RoleUtilisateur::Admin->value, 'web');

        $admin = Utilisateur::create([
            'nom' => 'Admin local',
            'telephone' => '+2250000000001',
            'telephone_verifie_le' => now(),
            'email' => $email,
            'password' => $motDePasse,
            'est_actif' => true,
        ]);

        $admin->assignRole(RoleUtilisateur::Admin->value);

        return $admin;
    }

    /** Coordonnées d'exemple, approximatives. */
    private function donnees(Zone $depart, Zone $arrivee, array $surcharges): array
    {
        return array_merge([
            'type' => TypeCommande::Colis,
            'retrait' => [
                'zone_id' => $depart->id,
                'adresse_id' => null,
                'latitude' => 6.1067,
                'longitude' => -3.8603,
                'repere' => 'Devant le marché',
                'contact_nom' => null,
                'contact_telephone' => '+2250700000001',
            ],
            'livraison' => [
                'zone_id' => $arrivee->id,
                'adresse_id' => null,
                'latitude' => 6.1100,
                'longitude' => -3.8650,
                'repere' => 'Près de la pharmacie',
                'contact_nom' => null,
                'contact_telephone' => '+2250700000002',
            ],
            'description' => 'Un sac de documents',
            'photo_chemin' => null,
            'mode_paiement' => ModePaiement::Especes,
            'montant_articles' => 0,
        ], $surcharges);
    }
}

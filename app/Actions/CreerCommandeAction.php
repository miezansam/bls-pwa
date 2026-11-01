<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModePaiement;
use App\Enums\StatutCommande;
use App\Enums\StatutPaiement;
use App\Enums\TypeCommande;
use App\Events\CommandeCreee;
use App\Models\Commande;
use App\Models\HistoriqueStatut;
use App\Models\Paiement;
use App\Models\Utilisateur;
use App\Services\CalculerPrixService;
use DomainException;
use Illuminate\Support\Facades\DB;
use App\Data\OptionsCommande;

final class CreerCommandeAction
{
    public function __construct(private readonly CalculerPrixService $prix)
    {
    }

    /**
     * @param array{
     *     type: TypeCommande,
     *     retrait: array{zone_id: ?int, adresse_id: ?int, latitude: float, longitude: float, repere: string, contact_nom: ?string, contact_telephone: string},
     *     livraison: array{zone_id: ?int, adresse_id: ?int, latitude: float, longitude: float, repere: string, contact_nom: ?string, contact_telephone: string},
     *     description: string,
     *     photo_chemin: ?string,
     *     mode_paiement: ModePaiement,
     *     express?: bool,
     *     double_course?: bool,
     *     speciale?: bool,
     *     nombre_repas?: int,
     *     montant_articles: int,
     * } $donnees  Données déjà validées (Form Request)
     */
    public function executer(Utilisateur $client, array $donnees): Commande
    {
        if (! $client->est_actif) {
            throw new DomainException('Ce compte est désactivé.');
        }

        $mode = $donnees['mode_paiement'];

        if ($mode->estMobileMoney() && ! config('bls.mobile_money_actif')) {
            throw new DomainException('Le paiement Mobile Money n\'est pas encore disponible.');
        }

        $type = $donnees['type'];
        $retrait = $donnees['retrait'];
        $livraison = $donnees['livraison'];
        $montantArticles = $type === TypeCommande::Repas ? $donnees['montant_articles'] : 0;
        $nombreRepas = $type === TypeCommande::Repas ? max(1, (int) ($donnees['nombre_repas'] ?? 1)) : 1;

        $estimation = $this->prix->estimer(
            type: $type,
            zoneRetraitId: $retrait['zone_id'],
            zoneLivraisonId: $livraison['zone_id'],
            retraitLatitude: $retrait['latitude'],
            retraitLongitude: $retrait['longitude'],
            livraisonLatitude: $livraison['latitude'],
            livraisonLongitude: $livraison['longitude'],
            options: new OptionsCommande(
                express: (bool) ($donnees['express'] ?? false),
                doubleCourse: (bool) ($donnees['double_course'] ?? false),
                speciale: (bool) ($donnees['speciale'] ?? false),
                nombreRepas: $nombreRepas,
            ),
        );

        $montantTotal = $estimation->fraisLivraison + $montantArticles;

        return DB::transaction(function () use ($client, $donnees, $type, $mode, $retrait, $livraison, $montantArticles, $nombreRepas, $estimation, $montantTotal) {
            $commande = Commande::create([
                'reference' => $this->genererReference(),
                'client_id' => $client->id,
                'type' => $type,
                'statut' => StatutCommande::EnAttente,

                'retrait_adresse_id' => $retrait['adresse_id'],
                'retrait_zone_id' => $retrait['zone_id'],
                'retrait_latitude' => $retrait['latitude'],
                'retrait_longitude' => $retrait['longitude'],
                'retrait_repere' => $retrait['repere'],
                'retrait_contact_nom' => $retrait['contact_nom'],
                'retrait_contact_telephone' => $retrait['contact_telephone'],

                'livraison_adresse_id' => $livraison['adresse_id'],
                'livraison_zone_id' => $livraison['zone_id'],
                'livraison_latitude' => $livraison['latitude'],
                'livraison_longitude' => $livraison['longitude'],
                'livraison_repere' => $livraison['repere'],
                'livraison_contact_nom' => $livraison['contact_nom'],
                'livraison_contact_telephone' => $livraison['contact_telephone'],

                'description' => $donnees['description'],
                'photo_chemin' => $donnees['photo_chemin'],

                'distance_m' => $estimation->distanceM,
                'duree_estimee_s' => $estimation->dureeEstimeeS,
                'frais_livraison' => $estimation->fraisLivraison,
                'montant_articles' => $montantArticles,
                'nombre_repas' => $nombreRepas,
                'detail_prix' => $estimation->detail,
                'montant_total' => $montantTotal,

                'mode_paiement' => $mode,
                'statut_paiement' => StatutPaiement::EnAttente,
                'code_livraison' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            ]);

            HistoriqueStatut::create([
                'commande_id' => $commande->id,
                'ancien_statut' => null,
                'nouveau_statut' => StatutCommande::EnAttente,
                'auteur_id' => $client->id,
            ]);

            Paiement::create([
                'commande_id' => $commande->id,
                'utilisateur_id' => $client->id,
                'mode' => $mode,
                'statut' => StatutPaiement::EnAttente,
                'montant' => $montantTotal,
                'devise' => 'XOF',
            ]);

            CommandeCreee::dispatch($commande);

            return $commande->refresh();
        });
    }

    /** Référence lisible, non séquentielle (n'indique pas le volume d'activité). Ex : BLS-261031-K7M2Q */
    private function genererReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // sans 0, O, 1, I pour éviter les confusions

        do {
            $suffixe = '';

            for ($i = 0; $i < 5; $i++) {
                $suffixe .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $reference = 'BLS-' . now()->format('ymd') . '-' . $suffixe;
        } while (Commande::query()->where('reference', $reference)->exists());

        return $reference;
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum StatutCommande: string
{
    case EnAttente = 'en_attente';
    case Acceptee = 'acceptee';
    case EnRouteRetrait = 'en_route_retrait';
    case ColisRecupere = 'colis_recupere';
    case EnRouteLivraison = 'en_route_livraison';
    case Livree = 'livree';
    case Annulee = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Acceptee => 'Acceptée',
            self::EnRouteRetrait => 'En route vers le retrait',
            self::ColisRecupere => 'Colis récupéré',
            self::EnRouteLivraison => 'En route vers la livraison',
            self::Livree => 'Livrée',
            self::Annulee => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EnAttente => 'gray',
            self::Acceptee, self::EnRouteRetrait => 'info',
            self::ColisRecupere, self::EnRouteLivraison => 'warning',
            self::Livree => 'success',
            self::Annulee => 'danger',
        };
    }

    public function estTerminal(): bool
    {
        return in_array($this, [self::Livree, self::Annulee], true);
    }

    /** @return list<self> */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::EnAttente => [self::Acceptee, self::Annulee],
            self::Acceptee => [self::EnRouteRetrait, self::EnAttente, self::Annulee],
            self::EnRouteRetrait => [self::ColisRecupere, self::Annulee],
            self::ColisRecupere => [self::EnRouteLivraison],
            self::EnRouteLivraison => [self::Livree],
            self::Livree, self::Annulee => [],
        };
    }

    public function peutPasserA(self $suivant): bool
    {
        return in_array($suivant, $this->transitionsAutorisees(), true);
    }

    /** Statuts pendant lesquels le livreur envoie sa position GPS. */
    public function declencheSuiviGps(): bool
    {
        return in_array($this, [
            self::Acceptee,
            self::EnRouteRetrait,
            self::ColisRecupere,
            self::EnRouteLivraison,
        ], true);
    }
}

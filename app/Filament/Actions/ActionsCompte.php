<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Actions\ChangerEtatCompteAction;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/** Boutons partagés par les écrans Livreurs (modèle ProfilLivreur) et Clients (modèle Utilisateur). */
final class ActionsCompte
{
    public static function desactiver(): Action
    {
        return Action::make('desactiver')
            ->label('Désactiver')
            ->color('danger')
            ->visible(fn (Model $record): bool => self::utilisateurDe($record)->est_actif)
            ->requiresConfirmation()
            ->modalHeading('Désactiver ce compte ?')
            ->modalDescription('La personne ne pourra plus se connecter ni passer de commande.')
            ->action(fn (Model $record) => self::changer($record, false));
    }

    public static function reactiver(): Action
    {
        return Action::make('reactiver')
            ->label('Réactiver')
            ->color('success')
            ->visible(fn (Model $record): bool => ! self::utilisateurDe($record)->est_actif)
            ->requiresConfirmation()
            ->action(fn (Model $record) => self::changer($record, true));
    }

    private static function utilisateurDe(Model $record): Utilisateur
    {
        return $record instanceof ProfilLivreur ? $record->utilisateur : $record;
    }

    private static function changer(Model $record, bool $actif): void
    {
        try {
            app(ChangerEtatCompteAction::class)->executer(self::utilisateurDe($record), $actif, auth()->user());

            Notification::make()->title($actif ? 'Compte réactivé' : 'Compte désactivé')->success()->send();
        } catch (DomainException $e) {
            Notification::make()->title('Opération impossible')->body($e->getMessage())->danger()->send();
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\Commandes\Tables;

use App\Actions\AttribuerCommandeAction;
use App\Actions\ChangerStatutCommandeAction;
use App\Enums\RoleUtilisateur;
use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Enums\TypeCommande;
use App\Models\Commande;
use App\Models\Utilisateur;
use App\Support\Format;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CommandesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['client', 'livreur']))
            ->defaultSort('created_at', 'desc')
            ->poll('15s')   // rafraîchissement automatique, en attendant le temps réel (Reverb)
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable()->copyable(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('statut')->label('Statut')->badge(),
                TextColumn::make('client.nom')->label('Client')->searchable(),
                TextColumn::make('livreur.nom')->label('Livreur')->placeholder('Pas encore attribuée'),
                TextColumn::make('attribuee_le')->label('Offre envoyée')->since()->placeholder('Aucune'),
                TextColumn::make('montant_total')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state): string => Format::fcfa($state))
                    ->sortable(),
                TextColumn::make('mode_paiement')->label('Paiement')->badge(),
                TextColumn::make('created_at')->label('Créée le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Filter::make('a_attribuer')
                    ->label('À attribuer')
                    ->query(fn (Builder $query): Builder => $query->where('statut', StatutCommande::EnAttente->value)),
                SelectFilter::make('statut')->label('Statut')->options(StatutCommande::class),
                SelectFilter::make('type')->label('Type')->options(TypeCommande::class),
            ])
            ->recordActions([
                ViewAction::make()->label('Voir'),
                self::actionAttribuer(),
                self::actionAnnuler(),
            ]);
    }

    private static function actionAttribuer(): Action
    {
        return Action::make('attribuer')
            ->label('Attribuer à un livreur')
            ->color('primary')
            ->visible(fn (Commande $record): bool => $record->statut === StatutCommande::EnAttente)
            ->modalHeading('Proposer cette course à un livreur')
            ->schema([
                Select::make('livreur_id')
                    ->label('Livreur')
                    ->options(fn (): array => self::livreursProposables())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, Commande $record): void {
                try {
                    app(AttribuerCommandeAction::class)->executer(
                        $record,
                        Utilisateur::query()->findOrFail($data['livreur_id']),
                        auth()->user(),
                    );

                    Notification::make()->title('Offre envoyée au livreur')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title('Attribution impossible')->body($e->getMessage())->danger()->send();
                }
            });
    }

    private static function actionAnnuler(): Action
    {
        return Action::make('annuler')
            ->label('Annuler')
            ->color('danger')
            ->visible(fn (Commande $record): bool => $record->statut->peutPasserA(StatutCommande::Annulee))
            ->requiresConfirmation()
            ->modalHeading('Annuler cette commande ?')
            ->schema([
                Textarea::make('motif')->label('Motif de l\'annulation')->required()->maxLength(255),
            ])
            ->action(function (array $data, Commande $record): void {
                try {
                    app(ChangerStatutCommandeAction::class)->executer(
                        $record,
                        StatutCommande::Annulee,
                        auteur: auth()->user(),
                        note: $data['motif'],
                    );

                    Notification::make()->title('Commande annulée')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title('Annulation impossible')->body($e->getMessage())->danger()->send();
                }
            });
    }

    /** @return array<int, string> Livreurs validés et en ligne, avec leur statut du moment. */
    private static function livreursProposables(): array
    {
        return Utilisateur::query()
            ->whereHas('roles', fn (Builder $q) => $q->where('name', RoleUtilisateur::Livreur->value))
            ->where('est_actif', true)
            ->whereHas('profilLivreur', fn (Builder $q) => $q
                ->where('est_valide', true)
                ->where('statut', '!=', StatutLivreur::HorsLigne->value))
            ->with('profilLivreur')
            ->orderBy('nom')
            ->get()
            ->mapWithKeys(fn (Utilisateur $u): array => [$u->id => "{$u->nom} ({$u->profilLivreur->statut->libelle()})"])
            ->all();
    }
}

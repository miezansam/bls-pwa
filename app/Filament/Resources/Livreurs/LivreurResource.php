<?php

declare(strict_types=1);

namespace App\Filament\Resources\Livreurs;

use App\Actions\ReverserCaisseAction;
use App\Enums\StatutLivreur;
use App\Enums\TypeVehicule;
use App\Filament\Actions\ActionsCompte;
use App\Filament\Resources\Livreurs\Pages\ManageLivreurs;
use App\Models\ProfilLivreur;
use App\Support\Format;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LivreurResource extends Resource
{
    protected static ?string $model = ProfilLivreur::class;

    protected static ?string $slug = 'livreurs';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'livreur';
    }

    public static function getPluralModelLabel(): string
    {
        return 'livreurs';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')
                ->label('Nom complet')
                ->required()
                ->maxLength(255)
                ->visibleOn(Operation::Create),
            TextInput::make('telephone')
                ->label('Téléphone')
                ->tel()
                ->required()
                ->regex('/^\+\d{8,15}$/')
                ->unique('utilisateurs', 'telephone')
                ->helperText('Format international sans espaces, ex : +2250700000000. Non modifiable ensuite.')
                ->visibleOn(Operation::Create),
            Select::make('type_vehicule')
                ->label('Véhicule')
                ->options(TypeVehicule::class)
                ->default(TypeVehicule::Moto->value)
                ->required(),
            TextInput::make('plaque')->label('Plaque d\'immatriculation')->maxLength(20),
            Toggle::make('est_valide')
                ->label('Livreur validé')
                ->helperText('Un livreur non validé ne peut recevoir aucune course.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('utilisateur'))
            ->defaultSort('solde_caisse', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('utilisateur.nom')->label('Livreur')->searchable(),
                TextColumn::make('utilisateur.telephone')->label('Téléphone')->searchable(),
                TextColumn::make('statut')->label('Statut')->badge(),
                TextColumn::make('type_vehicule')->label('Véhicule')->badge(),
                IconColumn::make('est_valide')->label('Validé')->boolean(),
                IconColumn::make('utilisateur.est_actif')->label('Compte actif')->boolean(),
                TextColumn::make('solde_caisse')
                    ->label('Caisse à reverser')
                    ->formatStateUsing(fn ($state): string => Format::fcfa($state))
                    ->sortable(),
                TextColumn::make('derniere_position_le')->label('Dernière position')->since()->placeholder('Jamais'),
            ])
            ->filters([
                SelectFilter::make('statut')->label('Statut')->options(StatutLivreur::class),
                Filter::make('caisse_a_reverser')
                    ->label('Caisse à reverser')
                    ->query(fn (Builder $query): Builder => $query->where('solde_caisse', '>', 0)),
            ])
            ->recordActions([
                EditAction::make(),
                self::actionReverserCaisse(),
                ActionsCompte::desactiver(),
                ActionsCompte::reactiver(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLivreurs::route('/'),
        ];
    }

    private static function actionReverserCaisse(): Action
    {
        return Action::make('reverser')
            ->label('Reverser la caisse')
            ->color('success')
            ->visible(fn (ProfilLivreur $record): bool => $record->solde_caisse > 0)
            ->modalHeading('Enregistrer un reversement en espèces')
            ->fillForm(fn (ProfilLivreur $record): array => ['montant' => $record->solde_caisse])
            ->schema([
                TextInput::make('montant')
                    ->label('Montant remis par le livreur')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->suffix('FCFA'),
                Textarea::make('note')->label('Note (facultatif)')->maxLength(255),
            ])
            ->action(function (array $data, ProfilLivreur $record): void {
                try {
                    app(ReverserCaisseAction::class)->executer(
                        $record,
                        (int) $data['montant'],
                        auth()->user(),
                        $data['note'] ?? null,
                    );

                    Notification::make()->title('Reversement enregistré')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title('Reversement impossible')->body($e->getMessage())->danger()->send();
                }
            });
    }
}

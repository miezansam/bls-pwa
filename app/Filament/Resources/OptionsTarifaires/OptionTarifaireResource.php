<?php

declare(strict_types=1);

namespace App\Filament\Resources\OptionsTarifaires;

use App\Enums\RegleOption;
use App\Filament\Resources\OptionsTarifaires\Pages\ManageOptionsTarifaires;
use App\Models\OptionTarifaire;
use App\Support\Format;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class OptionTarifaireResource extends Resource
{
    protected static ?string $model = OptionTarifaire::class;

    protected static ?string $slug = 'options-tarifaires';

    protected static string|UnitEnum|null $navigationGroup = 'Tarification';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'option tarifaire';
    }

    public static function getPluralModelLabel(): string
    {
        return 'options tarifaires';
    }

    /** Les options sont définies par le code (express, nuit...) : on règle leur prix, on n'en crée pas. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('regle')
                ->label('Règle')
                ->options(RegleOption::class)
                ->required()
                ->helperText('« Prix minimum » : le prix ne descend jamais sous ce montant. « Supplément » : le montant s\'ajoute au prix de base.'),
            TextInput::make('montant')->label('Montant')->numeric()->minValue(0)->required()->suffix('FCFA'),
            TextInput::make('heure_debut')
                ->label('Début de la nuit (heure de 0 à 23)')
                ->numeric()->minValue(0)->maxValue(23)
                ->helperText('Uniquement pour la livraison de nuit.'),
            TextInput::make('heure_fin')
                ->label('Fin de la nuit (heure de 0 à 23)')
                ->numeric()->minValue(0)->maxValue(23)
                ->helperText('Uniquement pour la livraison de nuit.'),
            TextInput::make('nombre_minimum')
                ->label('À partir de combien de repas')
                ->numeric()->minValue(2)
                ->helperText('Uniquement pour « Plusieurs commandes de repas ».'),
            Toggle::make('est_active')->label('Option active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Option')->badge(),
                TextColumn::make('regle')->label('Règle')->badge(),
                TextColumn::make('montant')->label('Montant')->formatStateUsing(fn ($state): string => Format::fcfa($state)),
                TextColumn::make('heure_debut')
                    ->label('Nuit dès')
                    ->formatStateUsing(fn ($state): ?string => $state === null ? null : $state . ' h')
                    ->placeholder('-'),
                TextColumn::make('heure_fin')
                    ->label('Jusqu\'à')
                    ->formatStateUsing(fn ($state): ?string => $state === null ? null : $state . ' h')
                    ->placeholder('-'),
                TextColumn::make('nombre_minimum')->label('Repas minimum')->placeholder('-'),
                IconColumn::make('est_active')->label('Active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOptionsTarifaires::route('/'),
        ];
    }
}

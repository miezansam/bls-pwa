<?php

declare(strict_types=1);

namespace App\Filament\Resources\Zones;

use App\Filament\Resources\Zones\Pages\ManageZones;
use App\Models\Zone;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;
use App\Support\Format;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tarification';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'zone';
    }

    public static function getPluralModelLabel(): string
    {
        return 'zones';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')
                ->label('Nom du quartier ou de la zone')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Textarea::make('description')->label('Description')->maxLength(500),
            TextInput::make('frais_livraison')
                ->label('Prix de livraison vers ou depuis cette zone')
                ->numeric()
                ->minValue(0)
                ->suffix('FCFA')
                ->helperText('Laisse vide si la zone n\'est pas desservie : aucune commande ne pourra y aller.'),
            Toggle::make('accepte_livraison_nuit')
                ->label('Livraison de nuit possible')
                ->helperText('Désactivé pour les villages : pas de livraison de nuit en dehors d\'Adzopé.')
                ->default(true),
            Toggle::make('est_active')->label('Zone active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nom')
            ->columns([
                TextColumn::make('nom')->label('Zone')->searchable()->sortable(),
                TextColumn::make('description')->label('Description')->limit(60)->placeholder('Aucune'),
                TextColumn::make('frais_livraison')
                    ->label('Prix de livraison vers cette zone')
                    ->formatStateUsing(fn ($state): string => $state === null ? 'Non desservie' : Format::fcfa($state))
                    ->placeholder('Non desservie')
                    ->sortable(),
                IconColumn::make('accepte_livraison_nuit')->label('Nuit')->boolean(),
                IconColumn::make('est_active')->label('Active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageZones::route('/'),
        ];
    }
}

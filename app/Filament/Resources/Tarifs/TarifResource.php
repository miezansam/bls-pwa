<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tarifs;

use App\Enums\ModeTarif;
use App\Enums\TypeCommande;
use App\Filament\Resources\Tarifs\Pages\ManageTarifs;
use App\Models\Tarif;
use App\Models\Zone;
use App\Support\Format;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TarifResource extends Resource
{
    protected static ?string $model = Tarif::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tarification';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'tarif exceptionnel';
    }

    public static function getPluralModelLabel(): string
    {
        return 'tarifs exceptionnels';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tarif')
                ->schema([
                    TextInput::make('nom')->label('Nom')->required()->maxLength(255),
                    Select::make('mode')
                        ->label('Mode de calcul')
                        ->options(ModeTarif::class)
                        ->default(ModeTarif::ForfaitZone->value)
                        ->required(),
                    Select::make('type_commande')
                        ->label('Type de commande')
                        ->options(TypeCommande::class)
                        ->placeholder('Tous les types'),
                    Select::make('zone_retrait_id')
                        ->label('Zone de retrait')
                        ->options(fn (): array => self::zones())
                        ->searchable()
                        ->helperText('Obligatoire pour un forfait par zone.'),
                    Select::make('zone_livraison_id')
                        ->label('Zone de livraison')
                        ->options(fn (): array => self::zones())
                        ->searchable()
                        ->helperText('Obligatoire pour un forfait par zone.'),
                    Toggle::make('est_actif')->label('Tarif actif')->default(true),
                ])
                ->columns(2),

            Section::make('Montants (FCFA)')
                ->schema([
                    TextInput::make('frais_forfait')
                        ->label('Forfait de la paire de zones')
                        ->numeric()->minValue(0)->default(0)->required()->suffix('FCFA')
                        ->helperText('Utilisé en mode « Forfait par zone ».'),
                    TextInput::make('frais_base')
                        ->label('Prise en charge')
                        ->numeric()->minValue(0)->default(0)->required()->suffix('FCFA')
                        ->helperText('Utilisé en mode « Au kilomètre ».'),
                    TextInput::make('frais_par_km')
                        ->label('Prix par kilomètre')
                        ->numeric()->minValue(0)->default(0)->required()->suffix('FCFA')
                        ->helperText('Utilisé en mode « Au kilomètre ».'),
                    TextInput::make('frais_minimum')
                        ->label('Minimum facturé')
                        ->numeric()->minValue(0)->default(0)->required()->suffix('FCFA'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['zoneRetrait', 'zoneLivraison']))
            ->defaultSort('nom')
            ->columns([
                TextColumn::make('nom')->label('Tarif')->searchable()->sortable(),
                TextColumn::make('mode')->label('Mode')->badge(),
                TextColumn::make('type_commande')->label('Type')->badge()->placeholder('Tous'),
                TextColumn::make('zoneRetrait.nom')->label('De')->placeholder('Toutes'),
                TextColumn::make('zoneLivraison.nom')->label('Vers')->placeholder('Toutes'),
                TextColumn::make('frais_forfait')->label('Forfait')->formatStateUsing(fn ($state): string => Format::fcfa($state)),
                TextColumn::make('frais_par_km')->label('Au km')->formatStateUsing(fn ($state): string => Format::fcfa($state)),
                IconColumn::make('est_actif')->label('Actif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('mode')->label('Mode')->options(ModeTarif::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTarifs::route('/'),
        ];
    }

    /** @return array<int, string> */
    private static function zones(): array
    {
        return Zone::query()->orderBy('nom')->pluck('nom', 'id')->all();
    }
}

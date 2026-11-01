<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReversementsCaisse;

use App\Filament\Resources\ReversementsCaisse\Pages\ListReversementsCaisse;
use App\Models\ReversementCaisse;
use App\Support\Format;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ReversementCaisseResource extends Resource
{
    protected static ?string $model = ReversementCaisse::class;

    protected static ?string $slug = 'reversements-caisse';

    protected static string|UnitEnum|null $navigationGroup = 'Caisse';

    public static function getModelLabel(): string
    {
        return 'reversement';
    }

    public static function getPluralModelLabel(): string
    {
        return 'reversements de caisse';
    }

    /** Un reversement s'enregistre depuis l'écran Livreurs, et ne se modifie plus ensuite. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['livreur', 'recuPar']))
            ->defaultSort('reverse_le', 'desc')
            ->columns([
                TextColumn::make('reverse_le')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('livreur.nom')->label('Livreur')->searchable(),
                TextColumn::make('montant')->label('Montant')->formatStateUsing(fn ($state): string => Format::fcfa($state))->sortable(),
                TextColumn::make('recuPar.nom')->label('Reçu par'),
                TextColumn::make('note')->label('Note')->placeholder('Aucune'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReversementsCaisse::route('/'),
        ];
    }
}

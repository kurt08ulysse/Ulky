<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketStallResource\Pages;
use App\Models\MarketStall;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarketStallResource extends Resource
{
    protected static ?string $model = MarketStall::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Marchés';

    protected static ?string $modelLabel = 'emplacement';

    protected static ?string $pluralModelLabel = 'emplacements';

    /**
     * Cloisonnement commune via le marché parent (la table n'a pas de commune_id).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->hasRole('super_admin')) {
            $query->whereHas('market', fn (Builder $q) => $q->where('commune_id', $user->commune_id));
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('market_id')
                    ->relationship('market', 'name')
                    ->required(),
                Forms\Components\TextInput::make('stall_number')
                    ->required(),
                Forms\Components\TextInput::make('stall_type')
                    ->required(),
                Forms\Components\TextInput::make('rent_amount_cents')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Select::make('occupant_id')
                    ->relationship('occupant', 'name'),
                Forms\Components\DatePicker::make('occupancy_start_date'),
                Forms\Components\DatePicker::make('occupancy_end_date'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('market.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stall_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stall_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rent_amount_cents')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('occupant.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('occupancy_start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('occupancy_end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketStalls::route('/'),
            'create' => Pages\CreateMarketStall::route('/create'),
            'edit' => Pages\EditMarketStall::route('/{record}/edit'),
        ];
    }
}

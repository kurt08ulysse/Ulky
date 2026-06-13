<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopesToCommune;
use App\Filament\Resources\StallRentResource\Pages;
use App\Models\StallRent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StallRentResource extends Resource
{
    use ScopesToCommune;

    protected static ?string $model = StallRent::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Marchés';

    protected static ?string $modelLabel = 'loyer';

    protected static ?string $pluralModelLabel = 'loyers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('market_stall_id')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('occupant_id')
                    ->relationship('occupant', 'name')
                    ->required(),
                Forms\Components\TextInput::make('amount_cents')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('period')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\DateTimePicker::make('paid_at'),
                Forms\Components\TextInput::make('commune_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('market_stall_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('occupant.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('commune_id')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListStallRents::route('/'),
            'create' => Pages\CreateStallRent::route('/create'),
            'edit' => Pages\EditStallRent::route('/{record}/edit'),
        ];
    }
}

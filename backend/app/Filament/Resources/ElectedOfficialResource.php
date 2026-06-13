<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopesToCommune;
use App\Filament\Resources\ElectedOfficialResource\Pages;
use App\Models\ElectedOfficial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ElectedOfficialResource extends Resource
{
    use ScopesToCommune;

    protected static ?string $model = ElectedOfficial::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Élus';

    protected static ?string $modelLabel = 'élu';

    protected static ?string $pluralModelLabel = 'élus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('Fonction (en anglais, ex. Mayor)')
                    ->required(),
                Forms\Components\FileUpload::make('photo_path')
                    ->label('Photo')
                    ->image()
                    ->disk('public')
                    ->directory('officials')
                    ->imageEditor(),
                Forms\Components\Textarea::make('description')
                    ->label('Description (en anglais)')
                    ->rows(6)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('display_order')
                    ->label('Ordre d\'affichage')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_published')
                    ->label('Publié')
                    ->default(true),
                Forms\Components\TextInput::make('commune_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('display_order')
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Fonction')
                    ->searchable(),
                Tables\Columns\TextColumn::make('display_order')
                    ->label('Ordre')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),
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
            'index' => Pages\ListElectedOfficials::route('/'),
            'create' => Pages\CreateElectedOfficial::route('/create'),
            'edit' => Pages\EditElectedOfficial::route('/{record}/edit'),
        ];
    }
}

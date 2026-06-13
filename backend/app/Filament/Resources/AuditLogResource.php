<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    // Journal append-only : consultation seule, jamais de création/modification/suppression.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('actor_type'),
                Forms\Components\TextInput::make('actor_id')
                    ->numeric(),
                Forms\Components\TextInput::make('action')
                    ->required(),
                Forms\Components\TextInput::make('subject_type'),
                Forms\Components\TextInput::make('subject_id')
                    ->numeric(),
                Forms\Components\Textarea::make('payload')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? (json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '')
                        : (string) $state)
                    ->rows(8)
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ip'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('actor_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('actor_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Append-only : aucune action de suppression.
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
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}

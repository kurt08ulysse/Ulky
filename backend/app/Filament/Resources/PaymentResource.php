<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Paiements — générés par le système (SingPay), donc CONSULTATION SEULE
 * dans le back-office (intégrité financière : pas de création/édition à la main).
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

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
                Forms\Components\TextInput::make('payable_type')
                    ->label('Type réglé')
                    ->formatStateUsing(fn ($state): string => $state ? class_basename($state) : '—')
                    ->disabled(),
                Forms\Components\TextInput::make('payable_id')
                    ->label('Réf. objet réglé')
                    ->disabled(),
                Forms\Components\TextInput::make('transaction_id')->disabled(),
                Forms\Components\TextInput::make('amount')->numeric()->disabled(),
                Forms\Components\TextInput::make('operator')->disabled(),
                Forms\Components\TextInput::make('phone')->disabled(),
                Forms\Components\TextInput::make('status')->disabled(),
                Forms\Components\Textarea::make('raw_response')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? (json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '')
                        : (string) $state)
                    ->rows(6)
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payable_type')
                    ->label('Réglé')
                    ->formatStateUsing(fn ($state): string => $state ? Str::headline(class_basename($state)) : '—')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payable_id')
                    ->label('Réf.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('XAF', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('operator')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'successful' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Intégrité financière : aucune suppression.
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
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}

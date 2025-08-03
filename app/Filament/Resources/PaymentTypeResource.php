<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentTypeResource\Pages;
use App\Models\PaymentType;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class PaymentTypeResource extends Resource
{
    protected static ?string $model = PaymentType::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-list';
    protected static ?string $navigationGroup = 'Pembayaran';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Jenis Pembayaran')->required(),
            Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Jenis Pembayaran'),
            Tables\Columns\TextColumn::make('description')->label('Deskripsi')->limit(50),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTypes::route('/'),
            'create' => Pages\CreatePaymentType::route('/create'),
            'edit' => Pages\EditPaymentType::route('/{record}/edit'),
        ];
    }
}

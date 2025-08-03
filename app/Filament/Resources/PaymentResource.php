<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-cash';
    protected static ?string $navigationGroup = 'Pembayaran';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_bill_id')
                ->relationship('studentBill', 'id')
                ->label('Tagihan')
                ->required(),
            Forms\Components\Select::make('payment_method_id')
                ->relationship('paymentMethod', 'name')
                ->label('Metode Pembayaran')
                ->required(),
            Forms\Components\TextInput::make('amount_paid')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->label('Jumlah Dibayar'),
            Forms\Components\DatePicker::make('payment_date')
                ->default(now())
                ->label('Tanggal Bayar')
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'success' => 'Berhasil',
                    'failed' => 'Gagal',
                ])
                ->default('pending'),
            Forms\Components\FileUpload::make('proof')
                ->label('Bukti Pembayaran')
                ->directory('payment_proofs')
                ->image()
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('studentBill.student.name')->label('Siswa'),
            Tables\Columns\TextColumn::make('paymentMethod.name')->label('Metode'),
            Tables\Columns\TextColumn::make('amount_paid')->money('IDR')->label('Jumlah'),
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'warning' => 'pending',
                    'success' => 'success',
                    'danger' => 'failed',
                ])
                ->label('Status'),
            Tables\Columns\TextColumn::make('payment_date')->date()->label('Tanggal Bayar'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

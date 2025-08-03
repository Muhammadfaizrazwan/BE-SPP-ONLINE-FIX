<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentBillResource\Pages;
use App\Models\StudentBill;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class StudentBillResource extends Resource
{
    protected static ?string $model = StudentBill::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationGroup = 'Pembayaran';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')
                ->relationship('student', 'name')
                ->required()
                ->label('Siswa'),
            Forms\Components\Select::make('academic_year_id')
                ->relationship('academicYear', 'name')
                ->required()
                ->label('Tahun Ajaran'),
            Forms\Components\Select::make('month')
                ->options([
                    '1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April',
                    '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus',
                    '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                ])
                ->required()
                ->label('Bulan'),
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->label('Nominal'),
            Forms\Components\DatePicker::make('due_date')
                ->required()
                ->label('Jatuh Tempo'),
            Forms\Components\Select::make('status')
                ->options([
                    'unpaid' => 'Belum Dibayar',
                    'paid' => 'Lunas',
                    'overdue' => 'Terlambat',
                ])
                ->default('unpaid')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('student.name')->label('Siswa')->searchable(),
            Tables\Columns\TextColumn::make('academicYear.name')->label('Tahun Ajaran'),
            Tables\Columns\TextColumn::make('month')->label('Bulan'),
            Tables\Columns\TextColumn::make('amount')->money('IDR')->label('Nominal'),
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'warning' => 'unpaid',
                    'success' => 'paid',
                    'danger' => 'overdue',
                ])
                ->label('Status'),
            Tables\Columns\TextColumn::make('due_date')->date()->label('Jatuh Tempo'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentBills::route('/'),
            'create' => Pages\CreateStudentBill::route('/create'),
            'edit' => Pages\EditStudentBill::route('/{record}/edit'),
        ];
    }
}

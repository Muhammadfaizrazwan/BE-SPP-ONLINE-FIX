<?php

namespace App\Filament\Resources\StudentBillResource\Pages;

use App\Filament\Resources\StudentBillResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentBill extends EditRecord
{
    protected static string $resource = StudentBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

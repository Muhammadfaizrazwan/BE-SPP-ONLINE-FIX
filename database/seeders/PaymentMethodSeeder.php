<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;
use Illuminate\Support\Str;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = '11111111-1111-1111-1111-111111111111'; // ganti dengan id sekolah yang valid

        $methods = [
            [
                'code' => 'BANK_TRANSFER',
                'name' => 'Bank Transfer',
                'description' => 'Transfer manual ke rekening bank',
                'school_id' => $schoolId,
                'is_active' => true,
            ],
            [
                'code' => 'CASH',
                'name' => 'Cash',
                'description' => 'Pembayaran tunai di sekolah',
                'school_id' => $schoolId,
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code'], 'school_id' => $schoolId],
                $method
            );
        }
    }
}

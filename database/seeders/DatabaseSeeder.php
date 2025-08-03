<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\School;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\PaymentType;
use App\Models\PaymentMethod;
use App\Models\SystemConfig;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo school
        $school = School::create([
            'id' => Str::uuid(),
            'name' => 'SMA Negeri 1 Jakarta',
            'address' => 'Jl. Merdeka No. 123, Jakarta Pusat',
            'phone' => '021-12345678',
            'email' => 'info@sman1jakarta.sch.id',
            'principal_name' => 'Dr. Budi Santoso, M.Pd',
            'bank_account' => '1234567890',
            'bank_name' => 'Bank BCA',
            'is_active' => true,
        ]);

        // Create super admin user (global)
        $superAdmin = User::create([
            'id' => Str::uuid(),
            'name' => 'Super Admin', // <-- ditambah
            'username' => 'superadmin',
            'email' => 'admin@example.com', // <-- mudah diingat
            'password' => Hash::make('password123'), // <-- mudah diingat
            'role' => 'admin',
            'school_id' => null,
            'is_active' => true,
        ]);

        // Create school admin user
        $schoolAdmin = User::create([
            'id' => Str::uuid(),
            'name' => 'Admin SMAN 1 Jakarta', // <-- ditambah
            'username' => 'admin_sman1',
            'email' => 'admin@sman1jakarta.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        // Academic year
        $academicYear = AcademicYear::create([
            'id' => Str::uuid(),
            'school_id' => $school->id,
            'year' => '2024/2025',
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
            'is_active' => true,
        ]);

        // Payment types
        $paymentTypes = [
            [
                'id' => Str::uuid(),
                'school_id' => $school->id,
                'code' => 'SPP',
                'name' => 'SPP Bulanan',
                'description' => 'Sumbangan Pembinaan Pendidikan bulanan',
            ],
            [
                'id' => Str::uuid(),
                'school_id' => $school->id,
                'code' => 'SERAGAM',
                'name' => 'Seragam Sekolah',
                'description' => 'Pembelian seragam sekolah',
            ],
            [
                'id' => Str::uuid(),
                'school_id' => $school->id,
                'code' => 'BUKU',
                'name' => 'Buku Pelajaran',
                'description' => 'Pembelian buku pelajaran',
            ],
            [
                'id' => Str::uuid(),
                'school_id' => $school->id,
                'code' => 'KEGIATAN',
                'name' => 'Kegiatan Sekolah',
                'description' => 'Biaya kegiatan ekstrakurikuler dan acara sekolah',
            ],
        ];
        foreach ($paymentTypes as $paymentType) {
            PaymentType::create($paymentType);
        }

        // Payment methods
        $paymentMethods = [
            [
                'school_id' => $school->id,
                'code' => 'BCA_VA',
                'name' => 'BCA Virtual Account',
                'type' => 'va',
                'provider' => 'Midtrans',
                'is_active' => true,
            ],
            [
                'school_id' => $school->id,
                'code' => 'BNI_VA',
                'name' => 'BNI Virtual Account',
                'type' => 'va',
                'provider' => 'Midtrans',
                'is_active' => true,
            ],
            [
                'school_id' => $school->id,
                'code' => 'BRI_VA',
                'name' => 'BRI Virtual Account',
                'type' => 'va',
                'provider' => 'Midtrans',
                'is_active' => true,
            ],
            [
                'school_id' => $school->id,
                'code' => 'GOPAY',
                'name' => 'GoPay',
                'type' => 'e_wallet',
                'provider' => 'Midtrans',
                'is_active' => true,
            ],
            [
                'school_id' => $school->id,
                'code' => 'DANA',
                'name' => 'DANA',
                'type' => 'e_wallet',
                'provider' => 'Midtrans',
                'is_active' => true,
            ],
            [
                'school_id' => $school->id,
                'code' => 'QRIS',
                'name' => 'QRIS',
                'type' => 'qris',
                'provider' => 'Midtrans',
                'is_active' => true,
            ],
            [
                'school_id' => $school->id,
                'code' => 'CASH',
                'name' => 'Tunai',
                'type' => 'cash',
                'provider' => null,
                'is_active' => true,
            ],
        ];
        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::create($paymentMethod);
        }

        // System configs
        $systemConfigs = [
            [
                'school_id' => null,
                'config_key' => 'app_name',
                'config_value' => 'SPP Online System',
                'data_type' => 'string',
                'description' => 'Nama aplikasi',
            ],
            [
                'school_id' => null,
                'config_key' => 'maintenance_mode',
                'config_value' => 'false',
                'data_type' => 'boolean',
                'description' => 'Mode maintenance aplikasi',
            ],
        ];
        foreach ($systemConfigs as $config) {
            SystemConfig::create($config);
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Super Admin: admin@example.com / password123');
        $this->command->info('School Admin: admin@sman1jakarta.sch.id / password123');
    }
}

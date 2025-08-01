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
    /**
     * Seed the application's database.
     */
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

        // Create super admin user
        $superAdmin = User::create([
            'id' => Str::uuid(),
            'username' => 'superadmin',
            'email' => 'admin@spponline.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'school_id' => null, // Super admin tidak terikat sekolah
            'is_active' => true,
        ]);

        // Create school admin user
        $schoolAdmin = User::create([
            'id' => Str::uuid(),
            'username' => 'admin_sman1',
            'email' => 'admin@sman1jakarta.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        // Create academic year
        $academicYear = AcademicYear::create([
            'id' => Str::uuid(),
            'school_id' => $school->id,
            'year' => '2024/2025',
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
            'is_active' => true,
        ]);

        // Create payment types
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

        // Create payment methods
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

        // Create system configs
        $systemConfigs = [
            // Global configs
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

            // School specific configs
            [
                'school_id' => $school->id,
                'config_key' => 'spp_amount_grade_10',
                'config_value' => '500000',
                'data_type' => 'integer',
                'description' => 'Nominal SPP untuk kelas 10',
            ],
            [
                'school_id' => $school->id,
                'config_key' => 'spp_amount_grade_11',
                'config_value' => '550000',
                'data_type' => 'integer',
                'description' => 'Nominal SPP untuk kelas 11',
            ],
            [
                'school_id' => $school->id,
                'config_key' => 'spp_amount_grade_12',
                'config_value' => '600000',
                'data_type' => 'integer',
                'description' => 'Nominal SPP untuk kelas 12',
            ],
            [
                'school_id' => $school->id,
                'config_key' => 'payment_due_date',
                'config_value' => '10',
                'data_type' => 'integer',
                'description' => 'Tanggal jatuh tempo pembayaran setiap bulan',
            ],
            [
                'school_id' => $school->id,
                'config_key' => 'late_fee_amount',
                'config_value' => '50000',
                'data_type' => 'integer',
                'description' => 'Denda keterlambatan pembayaran',
            ],
            [
                'school_id' => $school->id,
                'config_key' => 'notification_channels',
                'config_value' => '["email", "whatsapp"]',
                'data_type' => 'json',
                'description' => 'Channel notifikasi yang aktif',
            ],
            [
                'school_id' => $school->id,
                'config_key' => 'midtrans_server_key',
                'config_value' => 'SB-Mid-server-your-key-here',
                'data_type' => 'string',
                'description' => 'Midtrans server key',
                'is_sensitive' => true,
            ],
            [
                'school_id' => $school->id,
                'config_key' => 'midtrans_client_key',
                'config_value' => 'SB-Mid-client-your-key-here',
                'data_type' => 'string',
                'description' => 'Midtrans client key',
                'is_sensitive' => true,
            ],
        ];

        foreach ($systemConfigs as $config) {
            SystemConfig::create($config);
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Super Admin: superadmin / password');
        $this->command->info('School Admin: admin_sman1 / password');
    }
}

<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Employee;
use App\Models\Signatory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. App Settings
        AppSetting::set('satker_code', 'BBLSDM.73');
        AppSetting::set('satker_name', 'Balai Besar Pelatihan Sumber Daya Manusia Komunikasi dan Digital Makassar');
        AppSetting::set('satker_address', 'Jl. Prof. Abdurrahman Basalamah II No 25 Makassar 90231');
        AppSetting::set('sla_minutes', [
            'step1' => 5,
            'step2' => 10,
            'step3' => 1,
            'step4' => 5,
            'step5' => 5,
            'step6' => 5,
            'step7' => 1,
        ]);

        // 2. Asset Categories
        $categories = [
            [
                'code' => 'LAPTOP',
                'label' => 'Laptop / Komputer Jinjing',
                'default_loan_type' => 'ANNUAL',
                'requires_schedule_check' => false,
            ],
            [
                'code' => 'MOBILE_PHONE',
                'label' => 'Telepon Seluler (HP Dinas)',
                'default_loan_type' => 'ANNUAL',
                'requires_schedule_check' => false,
            ],
            [
                'code' => 'CAR',
                'label' => 'Mobil Dinas',
                'default_loan_type' => 'SHORT_TERM',
                'requires_schedule_check' => true,
            ],
            [
                'code' => 'MOTORCYCLE',
                'label' => 'Sepeda Motor Dinas',
                'default_loan_type' => 'ANNUAL',
                'requires_schedule_check' => false,
            ],
        ];

        foreach ($categories as $cat) {
            AssetCategory::updateOrCreate(['code' => $cat['code']], $cat);
        }

        // 3. Employees (Data fiktif terstruktur)
        $empKetua = Employee::create([
            'nip' => '198206082009012001',
            'full_name' => 'Olga Olivia Sombolayuk, SE',
            'rank_grade' => 'Penata Tk. I, III/d',
            'position' => 'Ketua Tim Keuangan dan Aset',
            'home_address' => 'Jl. Boulevard Raya No. 45, Panakkukang, Makassar',
            'employment_status' => 'PNS',
            'email' => 'olga@komdigi.go.id',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $empPengelola = Employee::create([
            'nip' => '199803112025041003',
            'full_name' => 'Muhammad Agung, S.Ds.',
            'rank_grade' => 'Penata Muda, III/a',
            'position' => 'Pengelola BMN / Pihak Pertama',
            'home_address' => 'Jl. Urip Sumoharjo KM 4 No. 12, Makassar',
            'employment_status' => 'CPNS',
            'email' => 'agung@komdigi.go.id',
            'phone' => '081234567891',
            'is_active' => true,
        ]);

        $empPeminjam1 = Employee::create([
            'nip' => '199005152019031005',
            'full_name' => 'Andi Pratama, S.Kom.',
            'rank_grade' => 'Penata Muda Tk. I, III/b',
            'position' => 'Pranata Komputer Ahli Muda',
            'home_address' => 'Perumahan Dosen Unhas Tamalanrea Blok C/10, Makassar',
            'employment_status' => 'PNS',
            'email' => 'andi.pratama@komdigi.go.id',
            'phone' => '081234567892',
            'is_active' => true,
        ]);

        $empPeminjam2 = Employee::create([
            'nip' => '199508202022032008',
            'full_name' => 'Siti Rahmawati, S.AP.',
            'rank_grade' => 'Penata Muda, III/a',
            'position' => 'Analis Tata Usaha',
            'home_address' => 'Jl. Hertasning Baru No. 88, Makassar',
            'employment_status' => 'PPPK',
            'email' => 'siti.rahmawati@komdigi.go.id',
            'phone' => '081234567893',
            'is_active' => true,
        ]);

        $empPpnpn = Employee::create([
            'nip' => null, // PPNPN tidak memiliki NIP 18 digit
            'full_name' => 'Rahmat Hidayat',
            'rank_grade' => '-',
            'position' => 'Petugas Operasional Lapangan',
            'home_address' => 'Jl. Toddopuli VI No. 21, Makassar',
            'employment_status' => 'PPPNPN',
            'email' => 'rahmat@komdigi.go.id',
            'phone' => '081234567894',
            'is_active' => true,
        ]);

        // 4. Signatories
        Signatory::create([
            'role' => 'KETUA_TIM_KEUANGAN_ASET',
            'employee_id' => $empKetua->id,
            'title_line' => 'Ketua Tim Keuangan dan Aset BBLSDM Makassar,',
            'valid_from' => '2025-01-01',
            'valid_to' => null,
        ]);

        Signatory::create([
            'role' => 'PENGELOLA_ASET',
            'employee_id' => $empPengelola->id,
            'title_line' => 'Pengelola Aset / Pihak Pertama BBLSDM Makassar,',
            'valid_from' => '2025-01-01',
            'valid_to' => null,
        ]);

        // 5. Users
        $defaultPassword = Hash::make('password');

        User::create([
            'name' => 'Andi Pratama (Peminjam)',
            'email' => 'peminjam@komdigi.go.id',
            'password' => $defaultPassword,
            'employee_id' => $empPeminjam1->id,
            'role' => 'PEMINJAM',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Muhammad Agung (Pengelola BMN)',
            'email' => 'pengelola@komdigi.go.id',
            'password' => $defaultPassword,
            'employee_id' => $empPengelola->id,
            'role' => 'PENGELOLA_BMN',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Olga Olivia (Ketua Tim Keuangan & Aset)',
            'email' => 'ketuatim@komdigi.go.id',
            'password' => $defaultPassword,
            'employee_id' => $empKetua->id,
            'role' => 'KETUA_TIM_ASET',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Administrator Sistem',
            'email' => 'admin@komdigi.go.id',
            'password' => $defaultPassword,
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        // 6. Assets (Barang BMN realistis fiktif)
        $assets = [
            [
                'category_code' => 'LAPTOP',
                'item_name' => 'Lap Top',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad T14 Gen 3',
                'bmn_code' => '3100102002',
                'nup' => 12,
                'acquisition_year' => 2023,
                'acquisition_value' => 18500000,
                'condition' => 'BAIK',
                'status' => 'AVAILABLE',
                'notes' => 'Kondisi mulus, kelengkapan adaptor charger original',
            ],
            [
                'category_code' => 'LAPTOP',
                'item_name' => 'Lap Top',
                'brand' => 'Asus',
                'model' => 'ExpertBook B5 Flip',
                'bmn_code' => '3100102002',
                'nup' => 15,
                'acquisition_year' => 2024,
                'acquisition_value' => 21000000,
                'condition' => 'BAIK',
                'status' => 'AVAILABLE',
                'notes' => 'Layar sentuh + stylus pen',
            ],
            [
                'category_code' => 'LAPTOP',
                'item_name' => 'Lap Top',
                'brand' => 'Dell',
                'model' => 'Latitude 5430',
                'bmn_code' => '3100102002',
                'nup' => 18,
                'acquisition_year' => 2023,
                'acquisition_value' => 16200000,
                'condition' => 'BAIK',
                'status' => 'AVAILABLE',
                'notes' => 'Lengkap tas laptop & charger',
            ],
            [
                'category_code' => 'CAR',
                'item_name' => 'Mobil Dinas',
                'brand' => 'Toyota',
                'model' => 'Innova Zenix 2.0 V',
                'bmn_code' => '3020102003',
                'nup' => 2,
                'plate_number' => 'DD 1820 XAS',
                'acquisition_year' => 2023,
                'acquisition_value' => 420000000,
                'condition' => 'BAIK',
                'status' => 'AVAILABLE',
                'notes' => 'Operasional dinas luar kota, servis rutin dealer resmi',
            ],
            [
                'category_code' => 'CAR',
                'item_name' => 'Mobil Dinas',
                'brand' => 'Toyota',
                'model' => 'Avanza 1.5 G MT',
                'bmn_code' => '3020102003',
                'nup' => 5,
                'plate_number' => 'DD 1124 XX',
                'acquisition_year' => 2022,
                'acquisition_value' => 235000000,
                'condition' => 'BAIK',
                'status' => 'AVAILABLE',
                'notes' => 'Operasional koordinasi dalam kota',
            ],
            [
                'category_code' => 'MOBILE_PHONE',
                'item_name' => 'Telepon Seluler',
                'brand' => 'Samsung',
                'model' => 'Galaxy A54 5G 256GB',
                'bmn_code' => '3060201004',
                'nup' => 8,
                'acquisition_year' => 2023,
                'acquisition_value' => 6200000,
                'condition' => 'BAIK',
                'status' => 'AVAILABLE',
                'notes' => 'HP koordinasi media & publikasi',
            ],
            [
                'category_code' => 'MOTORCYCLE',
                'item_name' => 'Sepeda Motor Dinas',
                'brand' => 'Honda',
                'model' => 'Vario 160 CBS',
                'bmn_code' => '3020104001',
                'nup' => 3,
                'plate_number' => 'DD 4521 AB',
                'acquisition_year' => 2023,
                'acquisition_value' => 28000000,
                'condition' => 'BAIK',
                'status' => 'AVAILABLE',
                'notes' => 'Kurir & surat menyurat kantor',
            ],
        ];

        foreach ($assets as $assetData) {
            Asset::create($assetData);
        }
    }
}

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

        // 3. Employees (Data fiktif terstruktur - 4 Pegawai)
        $empPeminjam1 = Employee::create([
            'nip' => '199005152019031005',
            'full_name' => 'Budi',
            'rank_grade' => 'Penata Muda Tk. I, III/b',
            'position' => 'Instruktur Ahli Pertama',
            'home_address' => 'Racing Centre No. 1',
            'employment_status' => 'PNS',
            'email' => 'peminjam@komdigi.go.id',
            'phone' => '081234567892',
            'is_active' => true,
        ]);

        $empPengelola = Employee::create([
            'nip' => '199803112025041003',
            'full_name' => 'Muhammad Agung, S.Ds.',
            'rank_grade' => 'Penata Muda, III/a',
            'position' => 'Instruktur Ahli Pertama',
            'home_address' => 'Racing Centre No. 2',
            'employment_status' => 'CPNS',
            'email' => 'pengelola@komdigi.go.id',
            'phone' => '081234567891',
            'is_active' => true,
        ]);

        $empKetua = Employee::create([
            'nip' => '198206082009012001',
            'full_name' => 'Olga Olivia Sombolayuk, SE',
            'rank_grade' => 'Penata Tk. I, III/d',
            'position' => 'Ketua Tim Keuangan dan Aset',
            'home_address' => 'Racing Centre No. 3',
            'employment_status' => 'PNS',
            'email' => 'olga@komdigi.go.id',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $empAdmin = Employee::create([
            'nip' => '198501012010011001',
            'full_name' => 'Administrator Sistem',
            'rank_grade' => 'Penata Tk. I, III/d',
            'position' => 'Administrator Sistem',
            'home_address' => 'Racing Centre No. 4',
            'employment_status' => 'PNS',
            'email' => 'admin@komdigi.go.id',
            'phone' => '081234567899',
            'is_active' => true,
        ]);

        // 4. Signatories
        Signatory::create([
            'role' => 'KETUA_TIM_KEUANGAN_ASET',
            'employee_id' => $empKetua->id,
            'title_line' => 'Ketua Tim Keuangan dan Aset BBLSDM Komdigi Makassar,',
            'valid_from' => '2025-01-01',
            'valid_to' => null,
        ]);

        Signatory::create([
            'role' => 'PENGELOLA_ASET',
            'employee_id' => $empPengelola->id,
            'title_line' => 'Pengelola Aset / Pihak Pertama BBLSDM Komdigi Makassar,',
            'valid_from' => '2025-01-01',
            'valid_to' => null,
        ]);

        // 5. Users
        $defaultPassword = Hash::make('password');

        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('signatures');
        $createDummySig = function ($filename) {
            $path = 'signatures/' . $filename;
            if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $img = imagecreatetruecolor(300, 120);
                imagesavealpha($img, true);
                $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
                imagefill($img, 0, 0, $trans);
                $ink = imagecolorallocate($img, 15, 23, 42);
                imagesetthickness($img, 3);
                $points = [[30, 75], [50, 35], [70, 85], [95, 45], [120, 65], [150, 40], [180, 80], [210, 50], [240, 60], [270, 75]];
                for ($i = 0; $i < count($points) - 1; $i++) {
                    imageline($img, $points[$i][0], $points[$i][1], $points[$i+1][0], $points[$i+1][1], $ink);
                }
                imageline($img, 25, 90, 275, 88, $ink);
                ob_start();
                imagepng($img);
                $contents = ob_get_clean();
                imagedestroy($img);
                \Illuminate\Support\Facades\Storage::disk('public')->put($path, $contents);
            }
            return $path;
        };

        User::create([
            'name' => 'Budi (Peminjam)',
            'email' => 'peminjam@komdigi.go.id',
            'password' => $defaultPassword,
            'employee_id' => $empPeminjam1->id,
            'role' => 'PEMINJAM',
            'signature_path' => $createDummySig('dummy_peminjam.png'),
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Muhammad Agung (Pengelola BMN)',
            'email' => 'pengelola@komdigi.go.id',
            'password' => $defaultPassword,
            'employee_id' => $empPengelola->id,
            'role' => 'PENGELOLA_BMN',
            'signature_path' => $createDummySig('dummy_pengelola.png'),
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Olga Olivia (Ketua Tim Keuangan & Aset)',
            'email' => 'ketuatim@komdigi.go.id',
            'password' => $defaultPassword,
            'employee_id' => $empKetua->id,
            'role' => 'KETUA_TIM_ASET',
            'signature_path' => $createDummySig('dummy_ketuatim.png'),
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Administrator Sistem',
            'email' => 'admin@komdigi.go.id',
            'password' => $defaultPassword,
            'employee_id' => $empAdmin->id,
            'role' => 'ADMIN',
            'signature_path' => $createDummySig('dummy_admin.png'),
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

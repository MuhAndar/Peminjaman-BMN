<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EntryFormAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['code' => 'LAPTOP', 'label' => 'Laptop / Komputer Jinjing', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
            ['code' => 'MOBILE_PHONE', 'label' => 'Telepon Seluler (HP Dinas)', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
            ['code' => 'CAR', 'label' => 'Mobil Dinas', 'default_loan_type' => 'SHORT_TERM', 'requires_schedule_check' => true],
            ['code' => 'MOTORCYCLE', 'label' => 'Sepeda Motor Dinas', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
            ['code' => 'TIK', 'label' => 'Peralatan TIK & Multimedia', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
            ['code' => 'NON_TIK', 'label' => 'Peralatan Non-TIK & Sarana Kantor', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
            ['code' => 'ALAT_BESAR', 'label' => 'Alat Besar & Mesin Operasional', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
            ['code' => 'ASET_LAINNYA', 'label' => 'Aset Tetap Lainnya & Pustaka', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
            ['code' => 'TANAH', 'label' => 'Tanah & Kawasan', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
            ['code' => 'GEDUNG', 'label' => 'Bangunan Gedung Kantor', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
            ['code' => 'RUMAH_NEGARA', 'label' => 'Rumah Negara', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false],
        ];

        foreach ($categories as $cat) {
            AssetCategory::updateOrCreate(['code' => $cat['code']], $cat);
        }

        $jsonPath = database_path('data/entry_form_assets.json');
        if (file_exists($jsonPath)) {
            $rawList = json_decode(file_get_contents($jsonPath), true) ?: [];
            $now = now();
            $assetsData = [];
            foreach ($rawList as $row) {
                $row['id'] = (string)Str::uuid();
                if (isset($row['extra_attributes']) && is_array($row['extra_attributes'])) {
                    $row['extra_attributes'] = json_encode($row['extra_attributes']);
                }
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
                $assetsData[] = $row;
            }

            DB::transaction(function () use ($assetsData) {
                DB::table('assets')->delete();
                foreach (array_chunk($assetsData, 100) as $chunk) {
                    DB::table('assets')->insert($chunk);
                }
            });
            return;
        }

        $filePath = base_path('Starter Kit/Referensi Dokumen dan SOP BMN/Entry Form Peminjaman.xlsx');
        if (!file_exists($filePath)) {
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        $assetsData = [];
        for ($r = 2; $r <= $highestRow; $r++) {
            $jenisBmn = trim((string)$sheet->getCell([2, $r])->getValue());
            $kodeSatker = trim((string)$sheet->getCell([3, $r])->getValue());
            $namaSatker = trim((string)$sheet->getCell([4, $r])->getValue());
            $bmnCode = trim((string)$sheet->getCell([5, $r])->getValue());
            $nup = (int)trim((string)$sheet->getCell([6, $r])->getValue());
            $itemName = trim((string)$sheet->getCell([7, $r])->getValue());
            $statusBmn = trim((string)$sheet->getCell([8, $r])->getValue());
            $brand = trim((string)$sheet->getCell([9, $r])->getValue());
            if ($brand === '-') $brand = null;
            $model = trim((string)$sheet->getCell([10, $r])->getValue());
            if ($model === '-') $model = null;

            $conditionRaw = trim((string)$sheet->getCell([11, $r])->getValue());
            $condition = 'BAIK';
            if (stripos($conditionRaw, 'rusak') !== false) {
                $condition = 'RUSAK';
            } elseif (stripos($conditionRaw, 'hilang') !== false) {
                $condition = 'TIDAK_LENGKAP_HILANG';
            }

            $umurAset = trim((string)$sheet->getCell([12, $r])->getValue());
            $intraExtra = trim((string)$sheet->getCell([13, $r])->getValue());
            $hentiGuna = trim((string)$sheet->getCell([14, $r])->getValue());
            $noDokumen = trim((string)$sheet->getCell([26, $r])->getValue());
            if ($noDokumen === '-') $noDokumen = null;
            $noBpkp = trim((string)$sheet->getCell([27, $r])->getValue());
            if ($noBpkp === '-') $noBpkp = null;

            $plateNumberRaw = trim((string)$sheet->getCell([28, $r])->getValue());
            $plateNumber = (!empty($plateNumberRaw) && $plateNumberRaw !== '-') ? $plateNumberRaw : null;

            $tglPerolehan = $this->cleanDate($sheet->getCell([34, $r])->getValue());
            $yearRaw = $this->cleanNumber($sheet->getCell([35, $r])->getValue());
            $year = $yearRaw > 1900 && $yearRaw < 2100 ? (int)$yearRaw : (int)date('Y');

            $acqValue = $this->cleanNumber($sheet->getCell([39, $r])->getValue());
            $nilaiBuku = $this->cleanNumber($sheet->getCell([41, $r])->getValue());

            $statusPenggunaan = trim((string)$sheet->getCell([51, $r])->getValue());
            $noPsp = trim((string)$sheet->getCell([52, $r])->getValue());
            if ($noPsp === '-') $noPsp = null;
            $tglPsp = $this->cleanDate($sheet->getCell([53, $r])->getValue());
            $alamat = trim((string)$sheet->getCell([54, $r])->getValue());
            if ($alamat === '-') $alamat = null;
            $lokasiRuang = trim((string)$sheet->getCell([74, $r])->getValue());
            $noStnk = trim((string)$sheet->getCell([77, $r])->getValue());
            if ($noStnk === '-') $noStnk = null;
            $namaPengguna = trim((string)$sheet->getCell([78, $r])->getValue());
            if ($namaPengguna === '-') $namaPengguna = null;

            $categoryCode = Asset::detectCategory($jenisBmn, $bmnCode, $itemName);

            $extra = [
                'status_sbsn' => trim((string)$sheet->getCell([15, $r])->getValue()),
                'status_bmn_idle' => trim((string)$sheet->getCell([16, $r])->getValue()),
                'status_kemitraan' => trim((string)$sheet->getCell([17, $r])->getValue()),
                'bpybds' => trim((string)$sheet->getCell([18, $r])->getValue()),
                'usulan_barang_hilang' => trim((string)$sheet->getCell([19, $r])->getValue()),
                'usulan_barang_rb' => trim((string)$sheet->getCell([20, $r])->getValue()),
                'usul_hapus' => trim((string)$sheet->getCell([21, $r])->getValue()),
                'hibah_dktp' => trim((string)$sheet->getCell([22, $r])->getValue()),
                'konsensi_jasa' => trim((string)$sheet->getCell([23, $r])->getValue()),
                'properti_investasi' => trim((string)$sheet->getCell([24, $r])->getValue()),
                'jenis_dokumen' => trim((string)$sheet->getCell([25, $r])->getValue()),
                'status_sertifikasi' => trim((string)$sheet->getCell([29, $r])->getValue()),
                'jenis_sertipikat' => trim((string)$sheet->getCell([30, $r])->getValue()),
                'no_sertifikat' => trim((string)$sheet->getCell([31, $r])->getValue()),
                'nama_di_dokumen' => trim((string)$sheet->getCell([32, $r])->getValue()),
                'tgl_buku_pertama' => $this->cleanDate($sheet->getCell([33, $r])->getValue()),
                'nilai_perolehan_pertama' => $this->cleanNumber($sheet->getCell([37, $r])->getValue()),
                'nilai_mutasi' => $this->cleanNumber($sheet->getCell([38, $r])->getValue()),
                'nilai_penyusutan' => $this->cleanNumber($sheet->getCell([40, $r])->getValue()),
                'luas_tanah_seluruhnya' => trim((string)$sheet->getCell([42, $r])->getValue()),
                'luas_bangunan' => trim((string)$sheet->getCell([46, $r])->getValue()),
                'jumlah_lantai' => trim((string)$sheet->getCell([49, $r])->getValue()),
                'jumlah_foto' => trim((string)$sheet->getCell([50, $r])->getValue()),
                'kab_kota' => trim((string)$sheet->getCell([58, $r])->getValue()),
                'provinsi' => trim((string)$sheet->getCell([60, $r])->getValue()),
                'kode_pos' => trim((string)$sheet->getCell([62, $r])->getValue()),
                'uraian_kpknl' => trim((string)$sheet->getCell([68, $r])->getValue()),
                'uraian_kanwil_djkn' => trim((string)$sheet->getCell([69, $r])->getValue()),
                'nama_kl' => trim((string)$sheet->getCell([70, $r])->getValue()),
                'kode_register' => trim((string)$sheet->getCell([73, $r])->getValue()),
                'status_pmk' => trim((string)$sheet->getCell([79, $r])->getValue()),
            ];

            $assetsData[] = [
                'id' => (string)Str::uuid(),
                'category_code' => $categoryCode,
                'jenis_bmn' => $jenisBmn,
                'kode_satker' => $kodeSatker,
                'nama_satker' => $namaSatker,
                'bmn_code' => $bmnCode,
                'nup' => $nup,
                'item_name' => $itemName,
                'status_bmn' => $statusBmn,
                'brand' => $brand,
                'model' => $model,
                'condition' => $condition,
                'umur_aset' => $umurAset,
                'intra_extra' => $intraExtra,
                'henti_guna' => $hentiGuna,
                'no_dokumen' => $noDokumen,
                'no_bpkp' => $noBpkp,
                'plate_number' => $plateNumber,
                'tgl_perolehan' => $tglPerolehan,
                'acquisition_year' => $year,
                'acquisition_value' => $acqValue,
                'nilai_buku' => $nilaiBuku,
                'status_penggunaan' => $statusPenggunaan,
                'no_psp' => $noPsp,
                'tgl_psp' => $tglPsp,
                'alamat' => $alamat,
                'lokasi_ruang' => $lokasiRuang,
                'no_stnk' => $noStnk,
                'nama_pengguna' => $namaPengguna,
                'status' => ($condition === 'RUSAK' || $statusBmn === 'Hilang') ? 'NEEDS_FOLLOW_UP' : 'AVAILABLE',
                'notes' => null,
                'extra_attributes' => json_encode($extra),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($assetsData) {
            DB::table('assets')->delete();
            foreach (array_chunk($assetsData, 100) as $chunk) {
                DB::table('assets')->insert($chunk);
            }
        });
    }



    private function cleanDate($val): ?string
    {
        if (empty($val)) return null;
        $val = trim((string)$val);
        if ($val === '0001-01-01' || $val === '9999-12-31' || $val === '-') return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;
        return null;
    }

    private function cleanNumber($val): float
    {
        if (empty($val)) return 0;
        $val = preg_replace('/[^0-9]/', '', (string)$val);
        return !empty($val) ? (float)$val : 0;
    }
}

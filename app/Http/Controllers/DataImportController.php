<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DataImportController extends Controller
{
    private function authorizeImport(): void
    {
        abort_unless(
            auth()->check() && (auth()->user()->isPengelola() || auth()->user()->isAdmin()),
            403,
            'Fitur unggah dan impor data BMN hanya dapat diakses oleh Pengelola BMN atau Administrator Sistem.'
        );
    }

    private function authorizeExport(): void
    {
        abort_unless(
            auth()->check() && (auth()->user()->isPengelola() || auth()->user()->isKetuaTim() || auth()->user()->isAdmin()),
            403,
            'Fitur ekspor inventaris BMN dapat diakses oleh Pengelola BMN, Ketua Tim Aset, atau Administrator Sistem.'
        );
    }

    /**
     * Display import data page.
     */
    public function index()
    {
        $this->authorizeImport();
        return view('kelola.import');
    }

    /**
     * Parse and preview uploaded .xlsx spreadsheet.
     */
    public function upload(Request $request)
    {
        $this->authorizeImport();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'data_type' => 'required|string',
        ], [
            'file.required' => 'Pilih berkas Excel (.xlsx / .xls) untuk diunggah.',
            'file.mimes' => 'Format berkas harus berupa Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran maksimal berkas adalah 10 MB.',
        ]);

        $file = $request->file('file');
        $dataType = $request->input('data_type');
        $originalName = $file->getClientOriginalName();
        $fileSize = number_format($file->getSize() / 1024, 1, ',', '.') . ' KB';

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $sheetName = $sheet->getTitle();
            $sheetNames = $spreadsheet->getSheetNames();

            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            // Baca baris header (Baris 1)
            $headers = [];
            for ($col = 1; $col <= min($highestColIndex, 30); $col++) {
                $cellVal = $sheet->getCell([$col, 1])->getFormattedValue();
                if ($cellVal !== null && trim((string)$cellVal) !== '') {
                    $headers[] = trim((string)$cellVal);
                }
            }

            if (empty($headers)) {
                return back()->with('error', 'Berkas Excel tidak memiliki baris header yang valid pada baris pertama.');
            }

            // Baca seluruh data baris untuk antrean impor
            $allDataRows = [];
            $previewRows = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                $rowAssoc = [];
                $hasContent = false;
                for ($col = 1; $col <= count($headers); $col++) {
                    $val = $sheet->getCell([$col, $row])->getFormattedValue();
                    $strVal = $val !== null ? trim((string)$val) : '';
                    if ($strVal !== '') {
                        $hasContent = true;
                    }
                    $rowData[] = $strVal;
                    $rowAssoc[$headers[$col - 1]] = $strVal;
                }

                if ($hasContent) {
                    $allDataRows[] = $rowAssoc;
                    if (count($previewRows) < 10) {
                        $previewRows[] = $rowData;
                    }
                }
            }

            $totalDataRows = count($allDataRows);

            // Simpan data antrean impor ke dalam session
            session([
                'bmn_import_pending' => [
                    'dataType' => $dataType,
                    'fileName' => $originalName,
                    'sheetName' => $sheetName,
                    'headers' => $headers,
                    'rows' => $allDataRows,
                    'totalRows' => $totalDataRows,
                ]
            ]);

            AuditLog::record(
                'UPLOAD_EXCEL_FILE',
                'DataImport',
                null,
                null,
                [
                    'file' => $originalName,
                    'type' => $dataType,
                    'rows' => $totalDataRows,
                    'sheet' => $sheetName,
                ]
            );

            return view('kelola.import', [
                'success_parsed' => true,
                'fileName' => $originalName,
                'fileSize' => $fileSize,
                'dataType' => $dataType,
                'sheetName' => $sheetName,
                'allSheets' => $sheetNames,
                'totalRows' => $totalDataRows,
                'headers' => $headers,
                'previewRows' => $previewRows,
            ]);

        } catch (Exception $e) {
            return back()->with('error', 'Gagal membaca berkas Excel: ' . $e->getMessage());
        }
    }

    /**
     * Commit parsed spreadsheet rows into the database.
     */
    public function commitImport(Request $request)
    {
        $this->authorizeImport();

        $pending = session('bmn_import_pending');
        if (!$pending || empty($pending['rows'])) {
            return redirect()->route('kelola.import.index')->with('error', 'Tidak ada antrean data impor yang siap dieksekusi. Silakan unggah berkas terlebih dahulu.');
        }

        $rows = $pending['rows'];
        $dataType = $pending['dataType'] ?? 'ASSETS';
        $fileName = $pending['fileName'] ?? 'Spreadsheet.xlsx';

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        if ($dataType === 'ASSETS') {
            foreach ($rows as $row) {
                // Normalisasi nama kolom (huruf kecil & hapus karakter khusus)
                $normalized = [];
                foreach ($row as $k => $v) {
                    $keyClean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$k)));
                    $normalized[$keyClean] = $v;
                }

                // Ambil Nama Barang
                $itemName = $normalized['namabarang'] ?? $normalized['itemname'] ?? $normalized['namaaset'] ?? null;
                if (!$itemName) {
                    $skipped++;
                    continue;
                }

                // Ambil Kode BMN & NUP
                $bmnCode = $normalized['kodebarang'] ?? $normalized['kodebmn'] ?? $normalized['bmncode'] ?? null;
                $nup = isset($normalized['nup']) && is_numeric($normalized['nup']) ? (int)$normalized['nup'] : null;

                if (!$bmnCode) {
                    $bmnCode = '3.05.02.06';
                }

                if ($nup === null) {
                    $maxNup = Asset::where('bmn_code', $bmnCode)->max('nup') ?? 0;
                    $nup = $maxNup + 1;
                }

                // Kategori Aset
                $rawCategory = strtolower(($normalized['jenisbmn'] ?? '') . ' ' . ($normalized['kategori'] ?? '') . ' ' . $itemName);
                if (str_contains($rawCategory, 'mobil')) {
                    $categoryCode = 'CAR';
                } elseif (str_contains($rawCategory, 'motor') || str_contains($rawCategory, 'sepeda motor')) {
                    $categoryCode = 'MOTORCYCLE';
                } elseif (str_contains($rawCategory, 'telepon') || str_contains($rawCategory, 'hp') || str_contains($rawCategory, 'handphone')) {
                    $categoryCode = 'MOBILE_PHONE';
                } elseif (str_contains($rawCategory, 'laptop') || str_contains($rawCategory, 'komputer') || str_contains($rawCategory, 'pc') || str_contains($rawCategory, 'notebook')) {
                    $categoryCode = 'LAPTOP';
                } else {
                    $catCodeClean = 'PERALATAN_KANTOR';
                    \App\Models\AssetCategory::firstOrCreate(
                        ['code' => $catCodeClean],
                        ['label' => 'Peralatan & Perlengkapan Kantor', 'default_loan_type' => 'ANNUAL', 'requires_schedule_check' => false]
                    );
                    $categoryCode = $catCodeClean;
                }

                $brand = $normalized['merk'] ?? $normalized['brand'] ?? null;
                $model = $normalized['tipe'] ?? $normalized['type'] ?? $normalized['model'] ?? null;
                $plateNumber = $normalized['nopolisi'] ?? $normalized['platnomor'] ?? null;

                $year = isset($normalized['tahun']) ? (int)$normalized['tahun'] : (isset($normalized['tahunperolehan']) ? (int)$normalized['tahunperolehan'] : (int)date('Y'));
                if ($year < 1900 || $year > 2100) {
                    $year = (int)date('Y');
                }

                $valRaw = $normalized['nilaiperolehanrp'] ?? $normalized['nilaiperolehan'] ?? $normalized['nilai'] ?? '0';
                $valNum = preg_replace('/[^0-9]/', '', (string)$valRaw);
                $acqValue = !empty($valNum) ? (float)$valNum : 0;

                $conditionRaw = strtoupper((string)($normalized['kondisi'] ?? 'BAIK'));
                $condition = str_contains($conditionRaw, 'RUSAK') ? 'RUSAK' : (str_contains($conditionRaw, 'HILANG') ? 'TIDAK_LENGKAP_HILANG' : 'BAIK');

                $asset = Asset::where('bmn_code', $bmnCode)->where('nup', $nup)->first();
                if ($asset) {
                    $asset->update([
                        'category_code' => $categoryCode,
                        'item_name' => $itemName,
                        'brand' => $brand ?: $asset->brand,
                        'model' => $model ?: $asset->model,
                        'plate_number' => $plateNumber ?: $asset->plate_number,
                        'acquisition_year' => $year,
                        'acquisition_value' => $acqValue ?: $asset->acquisition_value,
                        'condition' => $condition,
                    ]);
                    $updated++;
                } else {
                    Asset::create([
                        'id' => (string)\Illuminate\Support\Str::uuid(),
                        'category_code' => $categoryCode,
                        'bmn_code' => $bmnCode,
                        'nup' => $nup,
                        'item_name' => $itemName,
                        'brand' => $brand,
                        'model' => $model,
                        'plate_number' => $plateNumber,
                        'acquisition_year' => $year,
                        'acquisition_value' => $acqValue,
                        'condition' => $condition,
                        'status' => 'AVAILABLE',
                    ]);
                    $imported++;
                }
            }
        }

        AuditLog::record(
            'IMPORT_BMN_ASSETS',
            'Asset',
            null,
            null,
            [
                'file' => $fileName,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
            ]
        );

        session()->forget('bmn_import_pending');

        return redirect()->route('kelola.import.index')->with('import_success', [
            'file' => $fileName,
            'total' => $imported + $updated,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Download starter template .xlsx.
     */
    public function downloadTemplate()
    {
        $this->authorizeExport();

        $starterKitFile = base_path('Starter Kit/Referensi Dokumen dan SOP BMN/Contoh Entry Form Peminjaman.xlsx');
        if (file_exists($starterKitFile)) {
            return response()->download($starterKitFile, 'Contoh_Entry_Form_Peminjaman_BMN.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template BMN');

        // Headers
        $headers = [
            'A1' => 'KODE BMN',
            'B1' => 'NUP',
            'C1' => 'NAMA BARANG',
            'D1' => 'MERK',
            'E1' => 'TIPE / MODEL',
            'F1' => 'TAHUN PEROLEHAN',
            'G1' => 'NILAI PEROLEHAN (RP)',
            'H1' => 'KONDISI',
            'I1' => 'STATUS',
            'J1' => 'KETERANGAN',
        ];

        foreach ($headers as $cell => $title) {
            $sheet->setCellValue($cell, $title);
        }

        // Header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B63B5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Dummy sample row
        $dummyRows = [
            ['3.05.02.06.002', '1', 'Laptop / Notebook', 'Lenovo', 'ThinkPad T14 Gen 2', 2023, 18500000, 'BAIK', 'AVAILABLE', 'Unit operasional staf'],
            ['3.05.02.06.002', '2', 'Laptop / Notebook', 'Dell', 'Latitude 5420', 2022, 17200000, 'BAIK', 'AVAILABLE', 'Unit pelatihan'],
            ['3.02.01.01.002', '1', 'Mobil Dinas', 'Toyota', 'Innova Reborn 2.4 G', 2021, 385000000, 'BAIK', 'AVAILABLE', 'Kendaraan dinas operasional'],
        ];

        $r = 2;
        foreach ($dummyRows as $row) {
            $sheet->fromArray($row, null, 'A' . $r);
            $r++;
        }

        $sheet->getStyle('A2:J4')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'font' => ['size' => 9],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('B2:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F2:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G2:G4')->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Template_Data_BMN_Makassar.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export all registered BMN assets to Excel .xlsx.
     */
    public function exportAssets()
    {
        $this->authorizeExport();

        $assets = Asset::with(['category', 'loanItems.loanRequest.borrower', 'loanItems.loanRequest.documents'])
            ->orderBy('category_code')
            ->orderBy('nup')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaris BMN');

        $headers = [
            'A1' => 'NO',
            'B1' => 'KODE BMN',
            'C1' => 'NUP',
            'D1' => 'NAMA BARANG',
            'E1' => 'KATEGORI',
            'F1' => 'MERK',
            'G1' => 'TIPE / MODEL',
            'H1' => 'NO. POLISI',
            'I1' => 'TAHUN',
            'J1' => 'NILAI PEROLEHAN (RP)',
            'K1' => 'KONDISI',
            'L1' => 'STATUS BARANG',
            'M1' => 'STATUS OPERASIONAL',
            'N1' => 'PEMINJAM AKTIF',
            'O1' => 'NIP PEMINJAM',
            'P1' => 'NO. DOKUMEN PINJAM',
            'Q1' => 'KETERANGAN',
        ];

        foreach ($headers as $cell => $title) {
            $sheet->setCellValue($cell, $title);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B63B5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowNum = 2;
        foreach ($assets as $index => $asset) {
            $activeLoanItem = $asset->loanItems->first(function ($li) {
                return $li->loanRequest && in_array($li->loanRequest->status, [
                    'SUBMITTED', 'VERIFIED', 'APPROVED', 'BAST_ISSUED', 'ACTIVE', 'RETURN_REQUESTED', 'RETURN_CHECKED'
                ]);
            });

            $activeLoan = $activeLoanItem?->loanRequest;
            $borrowerName = $activeLoan?->snap_full_name ?? $activeLoan?->borrower?->full_name ?? $activeLoan?->borrower?->name ?? '-';
            $borrowerNip = $activeLoan?->snap_nip ?? $activeLoan?->borrower?->nip ?? '-';
            $issuedDoc = $activeLoan?->documents?->firstWhere('status', 'ISSUED');
            $docNumber = $issuedDoc?->doc_number ?? ($activeLoan ? 'Pengajuan #' . substr($activeLoan->id, 0, 8) : '-');

            $statusOperasional = 'Tersedia';
            if ($activeLoan) {
                if (in_array($activeLoan->status, ['BAST_ISSUED', 'ACTIVE', 'RETURN_REQUESTED', 'RETURN_CHECKED'])) {
                    $statusOperasional = 'Sedang Dipinjam';
                } else {
                    $statusOperasional = 'Dalam Proses Pengajuan';
                }
            } elseif ($asset->status === 'MAINTENANCE') {
                $statusOperasional = 'Dalam Pemeliharaan';
            } elseif ($asset->status === 'DISPOSED') {
                $statusOperasional = 'Dihapuskan';
            }

            $sheet->setCellValueExplicit('A' . $rowNum, $index + 1, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('B' . $rowNum, $asset->bmn_code ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $rowNum, $asset->nup, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValue('D' . $rowNum, $asset->item_name);
            $sheet->setCellValue('E' . $rowNum, $asset->category?->name ?? $asset->category_code);
            $sheet->setCellValue('F' . $rowNum, $asset->brand ?? '-');
            $sheet->setCellValue('G' . $rowNum, $asset->model ?? '-');
            $sheet->setCellValue('H' . $rowNum, $asset->plate_number ?? '-');
            $sheet->setCellValueExplicit('I' . $rowNum, $asset->acquisition_year ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('J' . $rowNum, (float) $asset->acquisition_value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValue('K' . $rowNum, $asset->condition ?? 'BAIK');
            $sheet->setCellValue('L' . $rowNum, $asset->status);
            $sheet->setCellValue('M' . $rowNum, $statusOperasional);
            $sheet->setCellValue('N' . $rowNum, $borrowerName);
            $sheet->setCellValueExplicit('O' . $rowNum, $borrowerNip, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('P' . $rowNum, $docNumber);
            $sheet->setCellValue('Q' . $rowNum, $asset->notes ?? '-');

            $rowNum++;
        }

        $lastRow = max($rowNum - 1, 2);

        $sheet->getStyle('A2:Q' . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'font' => ['size' => 9],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K2:M' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J2:J' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Katalog_Inventaris_BMN_Makassar_' . date('Ymd_His') . '.xlsx';

        AuditLog::record(
            'EXPORT_BMN_ASSETS',
            'Asset',
            null,
            null,
            [
                'total_exported' => $assets->count(),
                'file_name' => $filename,
            ]
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

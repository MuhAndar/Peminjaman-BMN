<?php

namespace App\Http\Controllers;

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
    private function authorizeManager(): void
    {
        abort_unless(
            auth()->check() && (auth()->user()->isPengelola() || auth()->user()->isAdmin()),
            403,
            'Fitur ini hanya dapat diakses oleh Pengelola BMN atau Administrator Sistem.'
        );
    }

    /**
     * Display import data page.
     */
    public function index()
    {
        $this->authorizeManager();
        return view('kelola.import');
    }

    /**
     * Parse and preview uploaded .xlsx spreadsheet.
     */
    public function upload(Request $request)
    {
        $this->authorizeManager();

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
            for ($col = 1; $col <= min($highestColIndex, 25); $col++) {
                $cellVal = $sheet->getCell([$col, 1])->getFormattedValue();
                if ($cellVal !== null && $cellVal !== '') {
                    $headers[] = (string)$cellVal;
                }
            }

            // Baca sampel data (Maksimal 10 baris pertama)
            $previewRows = [];
            $maxPreview = min($highestRow, 11);
            for ($row = 2; $row <= $maxPreview; $row++) {
                $rowData = [];
                for ($col = 1; $col <= count($headers); $col++) {
                    $val = $sheet->getCell([$col, $row])->getFormattedValue();
                    $rowData[] = $val !== null ? (string)$val : '';
                }
                // Skip jika semua kolom kosong
                if (array_filter($rowData, fn($v) => trim($v) !== '')) {
                    $previewRows[] = $rowData;
                }
            }

            $totalDataRows = max(0, $highestRow - 1);

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
     * Download starter template .xlsx.
     */
    public function downloadTemplate()
    {
        $this->authorizeManager();

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
        ]);
    }
}

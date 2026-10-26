<?php

namespace App\Http\Controllers;

use App\Helpers\FormatHelper;
use App\Models\AuditLog;
use App\Models\LoanRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanAuditController extends Controller
{
    /**
     * Memastikan hanya Pengelola BMN, Ketua Tim, atau Admin yang dapat mengakses Audit Peminjaman.
     */
    private function authorizeAccess(): void
    {
        abort_unless(
            auth()->check() && (auth()->user()->isPengelola() || auth()->user()->isKetuaTim() || auth()->user()->isAdmin()),
            403,
            'Akses terbatas untuk Pengelola BMN, Tim Aset, dan Administrator.'
        );
    }

    /**
     * Menampilkan daftar jejak audit aktivitas peminjaman barang BMN (tanpa data sensitif sistem).
     */
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $query = AuditLog::with(['actor.employee', 'loanRequest.items.asset', 'loanRequest.documents'])
            ->where('entity', 'LoanRequest')
            ->orderBy('created_at', 'desc');

        // Filter berdasarkan kata kunci pencarian (Peminjam, Barang, Catatan, dsb.)
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('entity_id', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('after->note', 'like', "%{$search}%")
                    ->orWhere('after->status', 'like', "%{$search}%")
                    ->orWhereHas('actor', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('loanRequest', function ($sub) use ($search) {
                        $sub->where('snap_full_name', 'like', "%{$search}%")
                            ->orWhere('snap_nip', 'like', "%{$search}%")
                            ->orWhere('snap_position', 'like', "%{$search}%")
                            ->orWhereHas('items', function ($itemSub) use ($search) {
                                $itemSub->where('snap_item_name', 'like', "%{$search}%")
                                    ->orWhere('snap_brand_model', 'like', "%{$search}%")
                                    ->orWhere('snap_nup', 'like', "%{$search}%")
                                    ->orWhereHas('asset', function ($assetSub) use ($search) {
                                        $assetSub->where('bmn_code', 'like', "%{$search}%")
                                            ->orWhere('nup', 'like', "%{$search}%");
                                    });
                            });
                    });
            });
        }

        // Filter berdasarkan status hasil transisi
        if ($request->filled('status')) {
            $status = $request->query('status');
            $query->where('after->status', $status);
        }

        // Filter berdasarkan pelaksana / aktor
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->query('actor_id'));
        }

        // Filter berdasarkan tanggal spesifik
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->query('date'));
        }

        $logs = $query->paginate(20)->withQueryString();

        // Statistik KPI Aktivitas Peminjaman
        $totalLoanEvents = AuditLog::where('entity', 'LoanRequest')->count();
        $todayLoanEvents = AuditLog::where('entity', 'LoanRequest')->whereDate('created_at', Carbon::today('Asia/Makassar'))->count();
        $activeLoans = LoanRequest::where('status', 'ACTIVE')->count();
        $completedLoans = LoanRequest::where('status', 'RETURNED')->count();

        // Dropdown Opsi Filter
        $actors = User::whereIn('role', ['PEMINJAM', 'PENGELOLA_BMN', 'KETUA_TIM_ASET', 'ADMIN'])
            ->orderBy('name')
            ->get();

        $statuses = [
            'SUBMITTED' => 'Diajukan Peminjam',
            'UNDER_APPROVAL' => 'Diteruskan ke Ketua Tim',
            'REVISION_REQUESTED' => 'Diminta Revisi',
            'APPROVED' => 'Disetujui Ketua Tim',
            'REJECTED' => 'Ditolak',
            'BAST_ISSUED' => 'BAST Diterbitkan',
            'ACTIVE' => 'Barang Diserahkan (Aktif)',
            'RETURN_REQUESTED' => 'Pengembalian Diajukan',
            'RETURN_CHECKED' => 'Pemeriksaan Fisik Selesai',
            'RETURNED' => 'Selesai & Dikembalikan',
        ];

        return view('audit.loans', compact(
            'logs',
            'totalLoanEvents',
            'todayLoanEvents',
            'activeLoans',
            'completedLoans',
            'actors',
            'statuses'
        ));
    }

    /**
     * Ekspor Rekapan Audit Aktivitas Peminjaman BMN (Excel .xlsx / CSV).
     * Format resmi tanpa memuat IP address, hash, atau data teknis sistem di luar peminjaman.
     */
    public function export(Request $request)
    {
        $this->authorizeAccess();

        $periodType = $request->input('filter_mode', $request->input('period_type', 'month'));
        $format = strtolower($request->input('format', 'xlsx'));

        $query = AuditLog::with(['actor.employee', 'loanRequest.items.asset', 'loanRequest.documents'])
            ->where('entity', 'LoanRequest')
            ->orderBy('created_at', 'desc');

        $periodeLabel = '';
        $nowMakassar = Carbon::now('Asia/Makassar');

        if ($periodType === 'month') {
            $year = (int)$request->input('year', $nowMakassar->year);
            $month = (int)$request->input('month', $nowMakassar->month);
            $startDate = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Makassar')->startOfMonth();
            $endDate = Carbon::create($year, $month, 1, 23, 59, 59, 'Asia/Makassar')->endOfMonth();
            $query->whereBetween('created_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
            $periodeLabel = 'Bulan ' . FormatHelper::getMonthName($month) . ' ' . $year;
        } elseif ($periodType === 'year') {
            $year = (int)$request->input('year', $nowMakassar->year);
            $startDate = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Makassar')->startOfYear();
            $endDate = Carbon::create($year, 12, 31, 23, 59, 59, 'Asia/Makassar')->endOfYear();
            $query->whereBetween('created_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
            $periodeLabel = 'Tahun ' . $year;
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date') ?: $startDate;
            if ($startDate) {
                $startDt = Carbon::parse($startDate, 'Asia/Makassar')->startOfDay();
                $endDt = Carbon::parse($endDate, 'Asia/Makassar')->endOfDay();
                $query->whereBetween('created_at', [$startDt->toDateTimeString(), $endDt->toDateTimeString()]);
                $periodeLabel = Carbon::parse($startDate)->format('d/m/Y') . ' s.d ' . Carbon::parse($endDate)->format('d/m/Y');
            } else {
                $periodeLabel = 'Semua Periode Peminjaman';
            }
        }

        $logs = $query->get();

        if ($format === 'csv') {
            return $this->exportCsv($logs, $periodeLabel);
        }

        return $this->exportExcel($logs, $periodeLabel);
    }

    /**
     * Memformat satu baris rekapan audit peminjaman menjadi data terstruktur dan bersih.
     */
    private function formatLogRow(AuditLog $log): array
    {
        $createdAt = $log->created_at ? Carbon::parse($log->created_at)->setTimezone('Asia/Makassar')->format('d/m/Y H:i') : '-';
        $loan = $log->loanRequest;
        $borrowerName = $loan ? $loan->snap_full_name : '-';
        $borrowerNip = ($loan && $loan->snap_nip) ? $loan->snap_nip : '-';
        $borrowerPosition = ($loan && $loan->snap_position) ? $loan->snap_position : '-';

        // Nomor Dokumen Resmi (Surat Peminjaman / BAST jika ada)
        $docNumber = '-';
        if ($loan && $loan->documents->isNotEmpty()) {
            $surat = $loan->documents->firstWhere('doc_type', 'SURAT_PEMINJAMAN');
            $bast = $loan->documents->firstWhere('doc_type', 'BAST_PEMINJAMAN');
            $docNumber = $surat ? $surat->doc_number : ($bast ? $bast->doc_number : $loan->documents->first()->doc_number);
        }

        // Pemformatan Barang BMN, Kode Barang, dan NUP
        $items = ($loan && $loan->items->isNotEmpty()) ? $loan->items : collect();
        if ($items->isEmpty()) {
            $itemCodes = '-';
            $itemNames = '-';
        } elseif ($items->count() === 1) {
            $it = $items->first();
            $nup = $it->snap_nup ?: ($it->asset ? $it->asset->nup : null);
            $itemNames = $it->snap_item_name . ($nup !== null ? ' (NUP ' . $nup . ')' : '');
            $itemCodes = ($it->asset && $it->asset->bmn_code) ? $it->asset->bmn_code : '-';
        } else {
            $nameParts = [];
            $codeParts = [];
            $idx = 1;
            foreach ($items as $it) {
                $nup = $it->snap_nup ?: ($it->asset ? $it->asset->nup : null);
                $nameParts[] = "({$idx}) " . $it->snap_item_name . ($nup !== null ? ' (NUP ' . $nup . ')' : '');
                $codeParts[] = "({$idx}) " . (($it->asset && $it->asset->bmn_code) ? $it->asset->bmn_code : '-');
                $idx++;
            }
            $itemNames = implode(', ', $nameParts);
            $itemCodes = implode(', ', $codeParts);
        }

        // Jenis dan Periode Peminjaman
        $loanTypeMap = [
            'ANNUAL' => 'Tahunan',
            'SHORT_TERM' => 'Sementara',
        ];
        $loanTypeLabel = $loan ? ($loanTypeMap[$loan->loan_type] ?? $loan->loan_type) : '-';
        $periodStr = ($loan && $loan->start_date && $loan->end_date)
            ? $loan->start_date->format('d/m/Y') . ' s.d ' . $loan->end_date->format('d/m/Y')
            : '-';
        $typeAndPeriod = $loan ? "{$loanTypeLabel} ({$periodStr})" : '-';

        // Transisi / Aksi SOP
        $rawFrom = $log->before['status'] ?? null;
        $rawTo = $log->after['status'] ?? null;
        $statusIndo = [
            'DRAFT' => 'Draf Pengajuan',
            'SUBMITTED' => 'Diajukan',
            'NEEDS_REVISION' => 'Perlu Revisi',
            'REVISION_REQUESTED' => 'Perlu Revisi',
            'UNDER_APPROVAL' => 'Persetujuan Ketua Tim',
            'APPROVED' => 'Disetujui',
            'BAST_ISSUED' => 'BAST Diterbitkan',
            'ACTIVE' => 'Aktif Dipinjam',
            'RETURN_REQUESTED' => 'Pengembalian Diajukan',
            'RETURN_CHECKED' => 'Fisik Diperiksa',
            'RETURNED' => 'Selesai & Dikembalikan',
            'REJECTED' => 'Ditolak',
        ];

        if ($rawFrom !== null && $rawTo !== null && $rawFrom !== $rawTo) {
            $fromLabel = $statusIndo[$rawFrom] ?? $rawFrom;
            $toLabel = $statusIndo[$rawTo] ?? $rawTo;
            $transition = "{$fromLabel} → {$toLabel}";
        } else {
            $actionTranslations = [
                'CREATE_LOAN_DRAFT' => 'Pembuatan Draf Pengajuan',
                'UPDATE_LOAN_DRAFT' => 'Pembaruan Draf Pengajuan',
                'DELETE_LOAN_REQUEST' => 'Penghapusan Berkas Pengajuan',
                'TRANSITION_LOAN' => 'Transisi Status Pengajuan',
                'SIGN_LOAN_BORROWER' => 'Tanda Tangan Peminjam',
                'SIGN_LOAN_OFFICER' => 'Tanda Tangan Petugas BMN',
                'SIGN_LOAN_APPROVER' => 'Tanda Tangan Ketua Tim Aset',
                'SIGN_LOAN_borrower' => 'Tanda Tangan Peminjam (BAST)',
                'SIGN_LOAN_officer' => 'Tanda Tangan Petugas (BAST)',
                'SIGN_LOAN_approver' => 'Tanda Tangan Ketua Tim Aset',
                'RESET_LOAN_TO_SUBMITTED' => 'Reset Antrean ke Pengajuan',
                'UPLOAD_BAST_PHOTO' => 'Unggah Foto Bukti Fisik',
                'DELETE_BAST_PHOTO' => 'Hapus Foto Fisik',
                'DELETE_LOAN_PHOTO' => 'Hapus Foto Fisik',
                'DOWNLOAD_PDF' => 'Unduh Dokumen PDF',
            ];
            $transition = $actionTranslations[$log->action] ?? ($rawTo ? ($statusIndo[$rawTo] ?? $rawTo) : ucwords(str_replace('_', ' ', strtolower($log->action))));
        }

        $toStatus = $rawTo ? ($statusIndo[$rawTo] ?? $rawTo) : '-';
        $note = $log->after['note'] ?? '-';

        // Pelaksana / Aktor tanpa duplikasi
        if ($log->actor) {
            $name = trim($log->actor->name);
            $roleMap = [
                'PEMINJAM' => 'Peminjam',
                'PENGELOLA_BMN' => 'Pengelola BMN',
                'KETUA_TIM_ASET' => 'Ketua Tim Aset',
                'ADMIN' => 'Administrator',
            ];
            $roleLabel = $roleMap[$log->actor->role] ?? $log->actor->role;

            if (preg_match('/\((peminjam|pengelola|ketua tim|admin)[^)]*\)$/i', $name)) {
                $actor = $name;
            } else {
                $actor = $name . ' (' . $roleLabel . ')';
            }
        } else {
            $actor = 'Sistem';
        }

        return [
            'created_at' => $createdAt,
            'entity_id' => (string)$log->entity_id,
            'doc_number' => $docNumber,
            'borrower_name' => $borrowerName,
            'borrower_nip' => $borrowerNip,
            'borrower_position' => $borrowerPosition,
            'item_codes' => $itemCodes,
            'item_names' => $itemNames,
            'type_and_period' => $typeAndPeriod,
            'transition' => $transition,
            'to_status' => $toStatus,
            'actor' => $actor,
            'note' => $note,
        ];
    }

    /**
     * Ekspor ke Spreadsheet Excel (.xlsx).
     */
    private function exportExcel($logs, string $periodeLabel)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Audit Peminjaman BMN');

        // Header Dokumen Resmi
        $sheet->setCellValue('A1', 'KEMENTERIAN KOMUNIKASI DAN DIGITAL RI');
        $sheet->setCellValue('A2', 'BALAI BESAR PENGEMBANGAN SDM DAN PENELITIAN KOMDIGI MAKASSAR');
        $sheet->setCellValue('A3', 'REKAPAN AUDIT AKTIVITAS PEMINJAMAN BARANG MILIK NEGARA (BMN)');
        $sheet->setCellValue('A4', 'Periode: ' . $periodeLabel . ' | Tanggal Cetak: ' . Carbon::now('Asia/Makassar')->format('d/m/Y H:i') . ' WITA');

        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->mergeCells('A3:N3');
        $sheet->mergeCells('A4:N4');

        $sheet->getStyle('A1:A3')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Kolom Tabel
        $headers = [
            'A6' => 'No',
            'B6' => 'Waktu (WITA)',
            'C6' => 'ID Pengajuan',
            'D6' => 'Nomor Dokumen Resmi',
            'E6' => 'Nama Peminjam',
            'F6' => 'NIP Peminjam',
            'G6' => 'Jabatan Peminjam',
            'H6' => 'Kode Barang BMN',
            'I6' => 'Barang BMN & NUP',
            'J6' => 'Jenis & Periode Peminjaman',
            'K6' => 'Tahapan SOP / Transisi',
            'L6' => 'Status Akhir',
            'M6' => 'Pelaksana / Aktor',
            'N6' => 'Catatan / Disposisi Resmi',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $sheet->getStyle('A6:N6')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B63B5'], // Kominfo Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(6)->setRowHeight(28);

        $row = 7;
        $no = 1;

        foreach ($logs as $log) {
            $data = $this->formatLogRow($log);

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $data['created_at']);
            $sheet->setCellValue('C' . $row, $data['entity_id']);
            $sheet->setCellValue('D' . $row, $data['doc_number']);
            $sheet->setCellValue('E' . $row, $data['borrower_name']);
            $sheet->setCellValue('F' . $row, $data['borrower_nip']);
            $sheet->setCellValue('G' . $row, $data['borrower_position']);
            $sheet->setCellValue('H' . $row, $data['item_codes']);
            $sheet->setCellValue('I' . $row, $data['item_names']);
            $sheet->setCellValue('J' . $row, $data['type_and_period']);
            $sheet->setCellValue('K' . $row, $data['transition']);
            $sheet->setCellValue('L' . $row, $data['to_status']);
            $sheet->setCellValue('M' . $row, $data['actor']);
            $sheet->setCellValue('N' . $row, $data['note']);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(false);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(false);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('L' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':N' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
            $no++;
        }

        $lastRow = max(7, $row - 1);
        $sheet->getStyle('A6:N' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Audit_Peminjaman_BMN_' . Carbon::now('Asia/Makassar')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Ekspor ke CSV.
     */
    private function exportCsv($logs, string $periodeLabel)
    {
        $filename = 'Audit_Peminjaman_BMN_' . Carbon::now('Asia/Makassar')->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs, $periodeLabel) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($handle, ['REKAPAN AUDIT AKTIVITAS PEMINJAMAN BMN — BBLSDM KOMDIGI MAKASSAR']);
            fputcsv($handle, ['Periode:', $periodeLabel]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No',
                'Waktu (WITA)',
                'ID Pengajuan',
                'Nomor Dokumen Resmi',
                'Nama Peminjam',
                'NIP Peminjam',
                'Jabatan Peminjam',
                'Kode Barang BMN',
                'Barang BMN & NUP',
                'Jenis & Periode Peminjaman',
                'Tahapan SOP / Transisi',
                'Status Akhir',
                'Pelaksana / Aktor',
                'Catatan / Disposisi Resmi',
            ]);

            $no = 1;
            foreach ($logs as $log) {
                $data = $this->formatLogRow($log);

                fputcsv($handle, [
                    $no++,
                    $data['created_at'],
                    $data['entity_id'],
                    $data['doc_number'],
                    $data['borrower_name'],
                    $data['borrower_nip'],
                    $data['borrower_position'],
                    $data['item_codes'],
                    $data['item_names'],
                    $data['type_and_period'],
                    $data['transition'],
                    $data['to_status'],
                    $data['actor'],
                    $data['note'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}

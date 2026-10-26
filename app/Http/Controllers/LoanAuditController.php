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

        $query = AuditLog::with(['actor.employee', 'loanRequest.items'])
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
                            ->orWhere('snap_unit_kerja', 'like', "%{$search}%")
                            ->orWhere('loan_purpose', 'like', "%{$search}%")
                            ->orWhereHas('items', function ($itemSub) use ($search) {
                                $itemSub->where('snap_item_name', 'like', "%{$search}%")
                                    ->orWhere('bmn_asset_code', 'like', "%{$search}%")
                                    ->orWhere('nup', 'like', "%{$search}%");
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

        $query = AuditLog::with(['actor.employee', 'loanRequest.items'])
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

        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');
        $sheet->mergeCells('A4:J4');

        $sheet->getStyle('A1:A3')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A4')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Kolom Tabel
        $headers = [
            'A6' => 'No',
            'B6' => 'Waktu (WITA)',
            'C6' => 'ID Pengajuan',
            'D6' => 'Nama Peminjam',
            'E6' => 'Unit Kerja',
            'F6' => 'Barang BMN & NUP',
            'G6' => 'Tahapan SOP / Transisi',
            'H6' => 'Status Akhir',
            'I6' => 'Pelaksana / Aktor',
            'J6' => 'Catatan / Disposisi Resmi',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $sheet->getStyle('A6:J6')->applyFromArray([
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
            $createdAt = $log->created_at ? Carbon::parse($log->created_at)->setTimezone('Asia/Makassar')->format('d/m/Y H:i') : '-';
            $loan = $log->loanRequest;
            $borrower = $loan ? $loan->snap_full_name : '-';
            $unitKerja = $loan ? $loan->snap_unit_kerja : '-';
            $items = $loan && $loan->items->isNotEmpty()
                ? $loan->items->map(fn($it) => $it->snap_item_name . ($it->nup ? ' (NUP: ' . $it->nup . ')' : ''))->implode(', ')
                : '-';

            $rawFrom = $log->before['status'] ?? null;
            $rawTo = $log->after['status'] ?? null;
            if ($rawFrom !== null && $rawTo !== null && $rawFrom !== $rawTo) {
                $transition = $rawFrom . ' -> ' . $rawTo;
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
                $transition = $actionTranslations[$log->action] ?? ($rawTo ?: ucwords(str_replace('_', ' ', strtolower($log->action))));
            }
            $toStatus = $rawTo ?? '-';
            $note = $log->after['note'] ?? '-';
            $actor = $log->actor ? $log->actor->name . ' (' . $log->actor->role . ')' : 'Sistem';

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $createdAt);
            $sheet->setCellValue('C' . $row, substr((string)$log->entity_id, 0, 8) . '...');
            $sheet->setCellValue('D' . $row, $borrower);
            $sheet->setCellValue('E' . $row, $unitKerja);
            $sheet->setCellValue('F' . $row, $items);
            $sheet->setCellValue('G' . $row, $transition);
            $sheet->setCellValue('H' . $row, $toStatus);
            $sheet->setCellValue('I' . $row, $actor);
            $sheet->setCellValue('J' . $row, $note);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
            $no++;
        }

        $lastRow = max(7, $row - 1);
        $sheet->getStyle('A6:J' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        foreach (range('A', 'J') as $col) {
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
                'Nama Peminjam',
                'Unit Kerja',
                'Barang BMN & NUP',
                'Tahapan SOP / Transisi',
                'Status Akhir',
                'Pelaksana / Aktor',
                'Catatan / Disposisi Resmi',
            ]);

            $no = 1;
            foreach ($logs as $log) {
                $createdAt = $log->created_at ? Carbon::parse($log->created_at)->setTimezone('Asia/Makassar')->format('d/m/Y H:i') : '-';
                $loan = $log->loanRequest;
                $borrower = $loan ? $loan->snap_full_name : '-';
                $unitKerja = $loan ? $loan->snap_unit_kerja : '-';
                $items = $loan && $loan->items->isNotEmpty()
                    ? $loan->items->map(fn($it) => $it->snap_item_name . ($it->nup ? ' (NUP: ' . $it->nup . ')' : ''))->implode(', ')
                    : '-';

                $rawFrom = $log->before['status'] ?? null;
                $rawTo = $log->after['status'] ?? null;
                if ($rawFrom !== null && $rawTo !== null && $rawFrom !== $rawTo) {
                    $transition = $rawFrom . ' -> ' . $rawTo;
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
                    $transition = $actionTranslations[$log->action] ?? ($rawTo ?: ucwords(str_replace('_', ' ', strtolower($log->action))));
                }
                $toStatus = $rawTo ?? '-';
                $note = $log->after['note'] ?? '-';
                $actor = $log->actor ? $log->actor->name . ' (' . $log->actor->role . ')' : 'Sistem';

                fputcsv($handle, [
                    $no++,
                    $createdAt,
                    (string)$log->entity_id,
                    $borrower,
                    $unitKerja,
                    $items,
                    $transition,
                    $toStatus,
                    $actor,
                    $note,
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

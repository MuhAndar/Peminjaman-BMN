<?php

namespace App\Http\Controllers;

use App\Helpers\FormatHelper;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Signatory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdminController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403, 'Akses terbatas untuk Administrator Sistem.');
    }

    /**
     * Display system audit trail / activity history.
     */
    public function auditLogs(Request $request)
    {
        $this->authorizeAdmin();
        $query = AuditLog::with('actor.employee')->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        // Filter by actor
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->query('actor_id'));
        }

        // Filter by entity
        if ($request->filled('entity')) {
            $query->where('entity', $request->query('entity'));
        }

        // Filter by date range
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->query('date'));
        }

        // Search text
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('entity', 'like', "%{$search}%")
                    ->orWhere('entity_id', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%")
                    ->orWhereHas('actor', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // Statistics
        $totalLogs = AuditLog::count();
        $todayLogs = AuditLog::whereDate('created_at', Carbon::today('Asia/Makassar'))->count();
        $activeUsers = AuditLog::distinct('actor_id')->count('actor_id');
        $statusTransitions = AuditLog::where('action', 'like', 'TRANSITION_%')->count();

        // Filter Dropdowns
        $users = User::orderBy('name')->get();
        $entities = AuditLog::select('entity')->distinct()->pluck('entity');
        $actions = AuditLog::select('action')->distinct()->pluck('action');

        return view('admin.audit-logs', compact(
            'logs',
            'users',
            'entities',
            'actions',
            'totalLogs',
            'todayLogs',
            'activeUsers',
            'statusTransitions'
        ));
    }

    /**
     * Display user management.
     */
    public function users()
    {
        $this->authorizeAdmin();
        $users = User::with('employee')->paginate(15);
        return view('admin.users', compact('users'));
    }

    /**
     * Display signatory management.
     */
    public function signatories()
    {
        $this->authorizeAdmin();
        $signatories = Signatory::with('employee')->get();
        return view('admin.signatories', compact('signatories'));
    }

    /**
     * Display system settings.
     */
    public function settings()
    {
        $this->authorizeAdmin();
        $satkerCode = AppSetting::get('satker_code', 'BBLSDM.73');
        $satkerName = AppSetting::get('satker_name', 'Balai Besar Pelatihan Sumber Daya Manusia Komunikasi dan Digital Makassar');
        $satkerAddress = AppSetting::get('satker_address', 'Jl. Prof. Abdurrahman Basalamah II No 25 Makassar 90231');
        $slaMinutes = AppSetting::get('sla_minutes', []);

        return view('admin.settings', compact('satkerCode', 'satkerName', 'satkerAddress', 'slaMinutes'));
    }

    /**
     * Export Audit Logs with filter by month, year, or specific dates.
     */
    public function exportAuditLogs(Request $request)
    {
        $this->authorizeAdmin();

        $periodType = $request->input('period_type', 'month'); // 'month', 'year', 'custom'
        $format = $request->input('format', 'xlsx');          // 'xlsx', 'csv'
        $query = AuditLog::with('actor.employee')->orderBy('created_at', 'desc');

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
        } elseif ($periodType === 'custom') {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date') ?: $startDate;
            if ($startDate) {
                $startDt = Carbon::parse($startDate, 'Asia/Makassar')->startOfDay();
                $endDt = Carbon::parse($endDate, 'Asia/Makassar')->endOfDay();
                $query->whereBetween('created_at', [$startDt->toDateTimeString(), $endDt->toDateTimeString()]);
                $periodeLabel = FormatHelper::formatDateIndo($startDate) . ' s.d. ' . FormatHelper::formatDateIndo($endDate);
            } else {
                $periodeLabel = 'Semua Periode';
            }
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->input('actor_id'));
        }

        $logs = $query->get();

        if ($format === 'csv') {
            return $this->exportAuditLogsCsv($logs, $periodeLabel);
        }

        return $this->exportAuditLogsXlsx($logs, $periodeLabel);
    }

    private function exportAuditLogsXlsx($logs, string $periodeLabel)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Audit BMN');

        // Satker Header
        $sheet->setCellValue('A1', 'BALAI BESAR PELATIHAN SDM KOMUNIKASI DAN DIGITAL MAKASSAR');
        $sheet->setCellValue('A2', 'REKAPITULASI LOG AUDIT & AKTIVITAS SISTEM PEMINJAMAN BMN');
        $sheet->setCellValue('A3', 'Filter Periode: ' . $periodeLabel . ' | Tanggal Unduh: ' . Carbon::now('Asia/Makassar')->format('d/m/Y H:i') . ' WITA | Operator: ' . auth()->user()->name);

        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0B63B5');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('64748B');

        // Table Header
        $headers = [
            'A5' => 'NO',
            'B5' => 'WAKTU (WITA)',
            'C5' => 'NAMA PENGGUNA',
            'D5' => 'NIP / IDENTITAS',
            'E5' => 'PERAN',
            'F5' => 'TIPE AKSI',
            'G5' => 'ENTITAS',
            'H5' => 'ID REFERENSI',
            'I5' => 'DETAIL PERUBAHAN / CATATAN',
            'J5' => 'ALAMAT IP',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B63B5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $sheet->getStyle('A5:J5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(25);

        // Data Rows
        $row = 6;
        foreach ($logs as $index => $log) {
            $timeStr = $log->created_at ? $log->created_at->timezone('Asia/Makassar')->format('d/m/Y H:i:s') : '-';
            $actorName = $log->actor->name ?? 'Sistem / Anonim';
            $actorNip = $log->actor->employee->nip ?? $log->actor->email ?? '-';
            $actorRole = $log->actor->role ?? '-';

            // Detail perubahan
            $detail = '';
            if (!empty($log->after) && is_array($log->after)) {
                $detailArr = [];
                foreach ($log->after as $k => $v) {
                    if (is_scalar($v)) {
                        $detailArr[] = "{$k}: {$v}";
                    }
                }
                $detail = implode(', ', $detailArr);
            } elseif ($log->before && $log->after) {
                $detail = json_encode(['before' => $log->before, 'after' => $log->after]);
            }

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $timeStr);
            $sheet->setCellValue('C' . $row, $actorName);
            $sheet->setCellValue('D' . $row, (string)$actorNip);
            $sheet->setCellValue('E' . $row, str_replace('_', ' ', $actorRole));
            $sheet->setCellValue('F' . $row, $log->action);
            $sheet->setCellValue('G' . $row, $log->entity);
            $sheet->setCellValue('H' . $row, (string)($log->entity_id ?? '-'));
            $sheet->setCellValue('I' . $row, $detail ?: '-');
            $sheet->setCellValue('J' . $row, $log->ip ?? '-');

            $row++;
        }

        $lastRow = max(6, $row - 1);
        $dataRange = 'A6:J' . $lastRow;
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'font' => ['size' => 9],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A6:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B6:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J6:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $cleanPeriod = preg_replace('/[^A-Za-z0-9_]/', '_', $periodeLabel);
        $filename = 'Rekap_Audit_BMN_' . $cleanPeriod . '_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache',
        ]);
    }

    private function exportAuditLogsCsv($logs, string $periodeLabel)
    {
        $cleanPeriod = preg_replace('/[^A-Za-z0-9_]/', '_', $periodeLabel);
        $filename = 'Rekap_Audit_BMN_' . $cleanPeriod . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, ['No', 'Waktu (WITA)', 'Pengguna', 'NIP / Identitas', 'Peran', 'Aksi', 'Entitas', 'ID Entitas', 'Detail Perubahan', 'IP Address']);

            foreach ($logs as $index => $log) {
                $timeStr = $log->created_at ? $log->created_at->timezone('Asia/Makassar')->format('d/m/Y H:i:s') : '-';
                $actorName = $log->actor->name ?? 'Sistem';
                $actorNip = $log->actor->employee->nip ?? $log->actor->email ?? '-';
                $actorRole = $log->actor->role ?? '-';

                $detail = '';
                if (!empty($log->after) && is_array($log->after)) {
                    $detailArr = [];
                    foreach ($log->after as $k => $v) {
                        if (is_scalar($v)) {
                            $detailArr[] = "{$k}: {$v}";
                        }
                    }
                    $detail = implode(', ', $detailArr);
                }

                fputcsv($output, [
                    $index + 1,
                    $timeStr,
                    $actorName,
                    $actorNip,
                    str_replace('_', ' ', $actorRole),
                    $log->action,
                    $log->entity,
                    $log->entity_id ?? '-',
                    $detail ?: '-',
                    $log->ip ?? '-',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache',
        ]);
    }
}

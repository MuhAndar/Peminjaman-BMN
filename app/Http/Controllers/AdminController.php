<?php

namespace App\Http\Controllers;

use App\Helpers\FormatHelper;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LoanRequest;
use App\Models\Signatory;
use App\Models\User;
use App\Services\LoanStateMachine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
    public function users(Request $request)
    {
        $this->authorizeAdmin();
        $query = User::with('employee');

        if ($request->filled('search')) {
            $term = trim($request->query('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhereHas('employee', function ($eq) use ($term) {
                      $eq->where('nip', 'like', "%{$term}%")
                         ->orWhere('position', 'like', "%{$term}%");
                  });
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        return view('admin.users', compact('users'));
    }

    /**
     * Store newly created user (Roles: PEMINJAM, PENGELOLA_BMN, KETUA_TIM_ASET. Admin is strictly disallowed).
     */
    public function storeUser(Request $request)
    {
        $this->authorizeAdmin();

        $allowedRoles = [
            User::ROLE_PEMINJAM,
            User::ROLE_PENGELOLA_BMN,
            User::ROLE_KETUA_TIM_ASET,
        ];

        // Explicit check against creating ADMIN accounts
        if ($request->input('role') === User::ROLE_ADMIN || $request->input('role') === 'ADMIN') {
            return back()->withInput()->with('error', 'Pembuatan akun dengan peran Administrator Sistem (ADMIN) tidak diizinkan melalui menu ini demi alasan keamanan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:' . implode(',', $allowedRoles),
            'nip' => 'nullable|string|max:30',
            'rank_grade' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:255',
            'home_address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
        ], [
            'name.required' => 'Nama lengkap pegawai wajib diisi.',
            'email.required' => 'Email kedinasan wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini telah terdaftar di sistem.',
            'password.required' => 'Kata sandi akun wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'role.required' => 'Peran (role) pengguna wajib dipilih.',
            'role.in' => 'Peran yang dipilih tidak valid. Pembuatan akun dibatasi untuk Peminjam, Pengelola BMN, atau Ketua Tim Aset.',
        ]);

        $cleanNip = null;
        if (!empty($validated['nip'])) {
            $cleanNip = preg_replace('/\D/', '', $validated['nip']);
        }

        DB::transaction(function () use ($validated, $cleanNip) {
            // 1. Cari atau buat Employee
            $employee = null;
            if (!empty($cleanNip)) {
                $employee = Employee::where('nip', $cleanNip)->first();
            }

            if (!$employee) {
                $employee = Employee::create([
                    'nip' => $cleanNip,
                    'full_name' => $validated['name'],
                    'rank_grade' => $validated['rank_grade'] ?? null,
                    'position' => $validated['position'] ?? null,
                    'home_address' => $validated['home_address'] ?? null,
                    'employment_status' => $validated['employment_status'] ?? 'PNS',
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'is_active' => true,
                ]);
            } else {
                // Perbarui profil pegawai jika ada data baru
                $employee->update(array_filter([
                    'full_name' => $validated['name'],
                    'rank_grade' => $validated['rank_grade'] ?? $employee->rank_grade,
                    'position' => $validated['position'] ?? $employee->position,
                    'home_address' => $validated['home_address'] ?? $employee->home_address,
                    'phone' => $validated['phone'] ?? $employee->phone,
                ]));
            }

            // 2. Buat User
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'employee_id' => $employee->id,
                'is_active' => true,
            ]);

            // 3. Catat Audit Log
            AuditLog::record(
                'ADMIN_CREATE_USER',
                'User',
                (string)$newUser->id,
                null,
                [
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'role' => $newUser->role,
                    'employee_id' => $employee->id,
                    'nip' => $cleanNip,
                ]
            );
        });

        $roleLabels = [
            'PEMINJAM' => 'Peminjam (Pegawai)',
            'PENGELOLA_BMN' => 'Pengelola BMN',
            'KETUA_TIM_ASET' => 'Ketua Tim Keuangan & Aset',
        ];

        return redirect()->route('admin.users')->with('success', 'Akun pengguna baru "' . $validated['name'] . '" dengan peran ' . ($roleLabels[$validated['role']] ?? $validated['role']) . ' berhasil dibuat.');
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

    /**
     * Halaman Monitoring Seluruh Pengajuan BMN & Kontrol Rollback (Khusus Admin).
     */
    public function loans(Request $request)
    {
        $this->authorizeAdmin();

        $todayMakassar = Carbon::now('Asia/Makassar')->toDateString();
        $baseQuery = LoanRequest::with(['borrower.user', 'items.asset', 'documents', 'photos']);

        // KPI Statistics
        $stats = [
            'total' => LoanRequest::count(),
            'active' => LoanRequest::where('status', 'ACTIVE')->count(),
            'pending' => LoanRequest::whereIn('status', ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED', 'BAST_ISSUED', 'RETURN_REQUESTED'])->count(),
            'overdue' => LoanRequest::whereIn('status', ['ACTIVE', 'RETURN_REQUESTED'])->where('end_date', '<', $todayMakassar)->count(),
            'returned' => LoanRequest::where('status', 'RETURNED')->count(),
        ];

        $statusFilter = $request->query('status', 'all');
        $query = (clone $baseQuery)->orderBy('created_at', 'desc');

        if ($statusFilter === 'active') {
            $query->where('status', 'ACTIVE');
        } elseif ($statusFilter === 'pending') {
            $query->whereIn('status', ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED', 'BAST_ISSUED', 'RETURN_REQUESTED']);
        } elseif ($statusFilter === 'returned') {
            $query->where('status', 'RETURNED');
        } elseif ($statusFilter === 'overdue') {
            $query->whereIn('status', ['ACTIVE', 'RETURN_REQUESTED'])->where('end_date', '<', $todayMakassar);
        } elseif ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('snap_full_name', 'like', "%{$search}%")
                    ->orWhere('snap_nip', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($sub) use ($search) {
                        $sub->where('snap_item_name', 'like', "%{$search}%")
                            ->orWhere('snap_brand_model', 'like', "%{$search}%")
                            ->orWhere('snap_nup', 'like', "%{$search}%");
                    })
                    ->orWhereHas('documents', function ($doc) use ($search) {
                        $doc->where('doc_number', 'like', "%{$search}%");
                    });
            });
        }

        $loans = $query->paginate(20)->withQueryString();

        return view('admin.loans.index', compact('loans', 'stats', 'statusFilter'));
    }

    /**
     * Eksekusi Rollback Alur Pengajuan oleh Administrator.
     */
    public function rollbackLoan(Request $request, string $id)
    {
        $this->authorizeAdmin();

        $loan = LoanRequest::findOrFail($id);
        $user = auth()->user();

        $request->validate([
            'target_status' => 'required|in:DRAFT,SUBMITTED,NEEDS_REVISION,UNDER_APPROVAL,APPROVED,BAST_ISSUED,ACTIVE,RETURN_REQUESTED,RETURN_CHECKED,RETURNED,CANCELLED',
            'reason' => 'required|string|min:5|max:500',
        ], [
            'target_status.required' => 'Pilih status tujuan rollback alur.',
            'target_status.in' => 'Status alur tujuan tidak valid.',
            'reason.required' => 'Wajib mengisi alasan rollback alur untuk dokumentasi audit.',
            'reason.min' => 'Alasan rollback minimal 5 karakter.',
        ]);

        $targetStatus = $request->input('target_status');
        $reason = $request->input('reason');

        try {
            LoanStateMachine::rollbackByAdmin($loan, $targetStatus, $user, $reason);

            $statusLabels = [
                'DRAFT' => 'Draf Pengajuan (Dapat Diedit Ulang Peminjam)',
                'SUBMITTED' => 'Ditelaah Ulang (Antrean Telaah Pengelola)',
                'NEEDS_REVISION' => 'Perlu Perbaikan Dokumen',
                'UNDER_APPROVAL' => 'Disposisi Ulang (Antrean Persetujuan Ketua Tim)',
                'APPROVED' => 'Disetujui (Menunggu Penerbitan BAST Ulang)',
                'BAST_ISSUED' => 'Penerbitan BAST Ulang (Menunggu Tanda Tangan BAST)',
                'ACTIVE' => 'Peminjaman Aktif (Sedang Dipinjam)',
                'RETURN_REQUESTED' => 'Pengembalian Diajukan (Menunggu Cek Fisik Pengembalian)',
                'RETURN_CHECKED' => 'Pemeriksaan Selesai (Menunggu BAST Kembali)',
                'RETURNED' => 'Selesai / Dikembalikan ke Gudang',
                'CANCELLED' => 'Dibatalkan',
            ];

            $targetLabel = $statusLabels[$targetStatus] ?? $targetStatus;

            return back()->with('success', "Pengajuan BMN milik {$loan->snap_full_name} berhasil di-rollback ke posisi: {$targetLabel}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal melakukan rollback: ' . $e->getMessage());
        }
    }
}

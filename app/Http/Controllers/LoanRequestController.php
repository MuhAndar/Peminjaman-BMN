<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AuditLog;
use App\Models\LoanItem;
use App\Models\LoanPhoto;
use App\Models\LoanRequest;
use App\Services\LoanStateMachine;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LoanRequestController extends Controller
{
    /**
     * List user's own loan requests (Peminjam).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $employeeId = $user->employee_id;

        $baseQuery = LoanRequest::where('borrower_id', $employeeId);

        $statusFilter = $request->query('status', 'all');

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'ACTIVE')->count(),
            'process' => (clone $baseQuery)->whereIn('status', ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED', 'BAST_ISSUED', 'RETURN_REQUESTED'])->count(),
            'returned' => (clone $baseQuery)->where('status', 'RETURNED')->count(),
            'rejected' => (clone $baseQuery)->whereIn('status', ['REJECTED', 'NEEDS_REVISION'])->count(),
        ];

        $query = (clone $baseQuery)->with(['items.asset', 'documents'])->orderBy('created_at', 'desc');

        if ($statusFilter === 'active') {
            $query->where('status', 'ACTIVE');
        } elseif ($statusFilter === 'process') {
            $query->whereIn('status', ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED', 'BAST_ISSUED', 'RETURN_REQUESTED']);
        } elseif ($statusFilter === 'returned') {
            $query->where('status', 'RETURNED');
        } elseif ($statusFilter === 'rejected') {
            $query->whereIn('status', ['REJECTED', 'NEEDS_REVISION']);
        } elseif ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $loans = $query->paginate(10)->withQueryString();

        return view('loans.index', compact('loans', 'statusFilter', 'counts'));
    }

    /**
     * Show the 4-step wizard form to submit a new loan request.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('profile.show', ['tab' => 'biodata'])
                ->with('warning', 'Silakan lengkapi data diri peminjam terlebih dahulu sebelum mengajukan peminjaman BMN.');
        }

        // Ambil barang inventaris yang dapat dipinjam (tersedia maupun sedang dipinjam)
        $availableAssets = Asset::whereIn('status', ['AVAILABLE', 'ON_LOAN'])
            ->where('condition', 'BAIK')
            ->with(['category', 'loanItems' => function ($q) {
                $q->whereHas('loanRequest', function ($sub) {
                    $sub->whereIn('status', [
                        'SUBMITTED',
                        'VERIFIED',
                        'APPROVED',
                        'BAST_ISSUED',
                        'ACTIVE',
                        'RETURN_REQUESTED',
                        'RETURN_CHECKED',
                    ]);
                })->with('loanRequest');
            }])
            ->orderBy('item_name')
            ->get();

        $selectedAssetId = $request->query('asset_id');
        if ($selectedAssetId) {
            $preAsset = $availableAssets->firstWhere('id', $selectedAssetId);
            if ($preAsset && $preAsset->is_currently_loaned) {
                $selectedAssetId = null;
            }
        }

        // Ambil semua kategori untuk filter dropdown (eager-load count aset untuk optimasi performa)
        $categories = AssetCategory::withCount('assets')->orderBy('label')->get();

        // 5 jenis/kategori barang yang paling sering diajukan secara riil (Rolling Window 60 hari + Cache harian reset 00:00)
        $frequentCategoryCodes = Cache::remember('bmn_popular_category_codes', now()->endOfDay(), function () {
            // 1. Ambil dari pengajuan riil (loan_items) 60 hari terakhir
            $codes = DB::table('loan_items')
                ->join('assets', 'loan_items.asset_id', '=', 'assets.id')
                ->where('loan_items.created_at', '>=', now()->subDays(60))
                ->select('assets.category_code', DB::raw('count(*) as loan_count'))
                ->groupBy('assets.category_code')
                ->orderByDesc('loan_count')
                ->limit(5)
                ->pluck('category_code')
                ->toArray();

            // 2. Jika pengajuan riil dalam 60 hari terakhir belum mencukupi 5 kategori, lengkapi dari riwayat pengajuan riil keseluruhan
            if (count($codes) < 5) {
                $allTimeCodes = DB::table('loan_items')
                    ->join('assets', 'loan_items.asset_id', '=', 'assets.id')
                    ->whereNotIn('assets.category_code', $codes)
                    ->select('assets.category_code', DB::raw('count(*) as loan_count'))
                    ->groupBy('assets.category_code')
                    ->orderByDesc('loan_count')
                    ->limit(5 - count($codes))
                    ->pluck('category_code')
                    ->toArray();

                $codes = array_merge($codes, $allTimeCodes);
            }

            return $codes;
        });

        // Susun 5 kategori quick filter terpopuler (fallback jumlah unit barang tersedia jika data pengajuan masih belum ada)
        $popularCategories = $categories->sortByDesc(function ($cat) use ($frequentCategoryCodes) {
            $idx = array_search($cat->code, $frequentCategoryCodes);
            return $idx !== false ? (100 - $idx) : ($cat->assets_count ?? 0);
        })->take(5)->values();

        // Siapkan struktur data ringan untuk paginasi instan & hemat memori di sisi klien
        $assetsData = $availableAssets->map(function ($asset) {
            $opStatus = $asset->operational_status;
            $isSelectable = $opStatus['is_selectable'];
            $activeLoan = $opStatus['loan'];
            $isOverdue = false;
            $loanPeriodCompact = '';
            $loanEndDateStr = '';

            if ($activeLoan && $activeLoan->start_date && $activeLoan->end_date) {
                $todayMakassar = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
                $startDate = \Carbon\Carbon::parse($activeLoan->start_date)->setTimezone('Asia/Makassar');
                $endDate = \Carbon\Carbon::parse($activeLoan->end_date)->setTimezone('Asia/Makassar');
                $isOverdue = $todayMakassar->gt($endDate->copy()->startOfDay());
                $loanEndDateStr = $endDate->translatedFormat('d M Y');
                if ($startDate->year === $endDate->year) {
                    $loanPeriodCompact = $startDate->translatedFormat('d M') . ' – ' . $endDate->translatedFormat('d M Y');
                } else {
                    $loanPeriodCompact = $startDate->translatedFormat('d M Y') . ' – ' . $endDate->translatedFormat('d M Y');
                }
            }

            return [
                'id' => (string)$asset->id,
                'item_name' => $asset->item_name,
                'brand_model' => $asset->brand_model_combined,
                'nup' => $asset->nup,
                'acquisition_year' => $asset->acquisition_year,
                'formatted_value' => $asset->formatted_value,
                'category_code' => $asset->category_code,
                'category_label' => $asset->category->label ?? '-',
                'plate_number' => $asset->plate_number,
                'is_selectable' => $isSelectable,
                'op_code' => $opStatus['code'],
                'op_label' => $opStatus['label'],
                'is_overdue' => $isOverdue,
                'loan_period' => $loanPeriodCompact,
                'loan_end_date' => $loanEndDateStr,
            ];
        });

        return view('loans.create', compact('employee', 'availableAssets', 'selectedAssetId', 'categories', 'popularCategories', 'assetsData'));
    }

    /**
     * Store new loan request (Wizard submission).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('profile.show', ['tab' => 'biodata'])
                ->with('error', 'Silakan lengkapi data diri peminjam terlebih dahulu sebelum mengajukan peminjaman BMN.');
        }

        $validated = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'required|uuid|exists:assets,id',
            'loan_type' => 'required|in:ANNUAL,SHORT_TERM',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'purposes' => 'required|array',
            'purposes.*' => 'required|string|max:500',
            'home_address' => 'required|string|max:500',
        ], [
            'asset_ids.required' => 'Pilih minimal satu barang BMN untuk diajukan.',
            'end_date.after_or_equal' => 'Tanggal pengembalian tidak boleh sebelum tanggal peminjaman.',
            'home_address.required' => 'Alamat rumah peminjam wajib diisi untuk kelengkapan dokumen.',
        ]);

        try {
            DB::beginTransaction();

            // Simpan pembaruan data diri pegawai & user jika diubah lewat form
            if ($request->filled('full_name') && $employee->full_name !== $request->input('full_name')) {
                $employee->full_name = trim($request->input('full_name'));
                if ($user->name !== $employee->full_name) {
                    $user->name = $employee->full_name;
                    $user->save();
                }
            }
            if ($request->filled('nip')) {
                $cleanNip = preg_replace('/\D/', '', $request->input('nip'));
                if (!empty($cleanNip) && $employee->nip !== $cleanNip) {
                    $employee->nip = $cleanNip;
                }
            }
            if ($request->has('rank_grade') && $employee->rank_grade !== $request->input('rank_grade')) {
                $employee->rank_grade = $request->input('rank_grade');
            }
            if ($request->has('position') && $employee->position !== $request->input('position')) {
                $employee->position = $request->input('position');
            }
            if ($request->filled('phone_number') && $employee->phone !== $request->input('phone_number')) {
                $employee->phone = $request->input('phone_number');
            } elseif ($request->filled('phone') && $employee->phone !== $request->input('phone')) {
                $employee->phone = $request->input('phone');
            }
            if ($employee->home_address !== $validated['home_address']) {
                $employee->home_address = $validated['home_address'];
            }
            $employee->save();

            $initialBorrowerSig = !empty($user->signature_path) && Storage::disk('public')->exists($user->signature_path) ? $user->signature_path : null;

            // Buat loan_request dengan status DRAFT (SOP Tahap 1 Draf)
            $loan = LoanRequest::create([
                'borrower_id' => $employee->id,
                'loan_type' => $validated['loan_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'DRAFT',
                'borrower_signature' => $initialBorrowerSig,
                'snap_full_name' => $employee->full_name,
                'snap_nip' => $employee->nip,
                'snap_rank_grade' => $employee->rank_grade,
                'snap_position' => $employee->position,
                'snap_home_address' => $validated['home_address'],
            ]);

            if ($initialBorrowerSig) {
                AuditLog::record(
                    'SIGN_LOAN_BORROWER',
                    'LoanRequest',
                    $loan->id,
                    null,
                    ['source' => 'profile_default', 'file' => $initialBorrowerSig]
                );
            }

            // Tambahkan item barang dengan snapshot data barang (BR-07)
            foreach ($validated['asset_ids'] as $assetId) {
                $asset = Asset::findOrFail($assetId);
                $purpose = $validated['purposes'][$assetId] ?? 'Pelaksanaan tugas kedinasan';

                LoanItem::create([
                    'loan_request_id' => $loan->id,
                    'asset_id' => $asset->id,
                    'purpose' => $purpose,
                    'condition_out' => 'Baik',
                    'snap_item_name' => $asset->item_name,
                    'snap_brand_model' => $asset->brand_model_combined,
                    'snap_nup' => $asset->nup,
                    'snap_year' => $asset->acquisition_year,
                    'snap_value' => $asset->acquisition_value,
                    'is_blocking' => false,
                ]);
            }

            AuditLog::record('CREATE_LOAN_DRAFT', 'LoanRequest', $loan->id, null, [
                'loan_type' => $loan->loan_type,
                'items_count' => count($validated['asset_ids']),
            ]);

            DB::commit();

            return redirect()->route('loans.show', $loan->id)
                ->with('success', 'Draf pengajuan peminjaman BMN berhasil dibuat. Silakan periksa rincian data Anda dan kirim pengajuan ke Pengelola BMN.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show form to edit loan draft (DRAFT or NEEDS_REVISION).
     */
    public function edit(string $id)
    {
        $loan = LoanRequest::with(['items.asset', 'borrower'])->findOrFail($id);
        $user = Auth::user();

        if ($loan->borrower_id !== $user->employee_id && !$user->isAdmin()) {
            abort(403, 'Hanya peminjam bersangkutan yang dapat mengedit draf ini.');
        }

        if (!in_array($loan->status, ['DRAFT', 'NEEDS_REVISION'])) {
            return redirect()->route('loans.show', $loan->id)
                ->with('error', 'Pengajuan dengan status saat ini tidak dapat diedit.');
        }

        $employee = $user->employee;
        $categories = AssetCategory::withCount('assets')->orderBy('label')->get();

        // Ambil barang yang tersedia, ditambah barang yang saat ini sudah ada di dalam draf ini
        $currentAssetIds = $loan->items->pluck('asset_id')->toArray();
        $availableAssets = Asset::where(function ($q) use ($currentAssetIds) {
            $q->where(function ($sub) {
                $sub->whereIn('status', ['AVAILABLE', 'ON_LOAN'])->where('condition', 'BAIK');
            })->orWhereIn('id', $currentAssetIds);
        })->with(['category', 'loanItems' => function ($q) {
            $q->whereHas('loanRequest', function ($sub) {
                $sub->whereIn('status', [
                    'SUBMITTED',
                    'VERIFIED',
                    'APPROVED',
                    'BAST_ISSUED',
                    'ACTIVE',
                    'RETURN_REQUESTED',
                    'RETURN_CHECKED',
                ]);
            })->with('loanRequest');
        }])->orderBy('item_name')->get();

        $frequentCategoryCodes = Cache::remember('bmn_popular_category_codes', now()->endOfDay(), function () {
            return DB::table('loan_items')
                ->join('assets', 'loan_items.asset_id', '=', 'assets.id')
                ->where('loan_items.created_at', '>=', now()->subDays(60))
                ->select('assets.category_code', DB::raw('count(*) as loan_count'))
                ->groupBy('assets.category_code')
                ->orderByDesc('loan_count')
                ->limit(5)
                ->pluck('category_code')
                ->toArray();
        });

        $popularCategories = $categories->sortByDesc(function ($cat) use ($frequentCategoryCodes) {
            $idx = array_search($cat->code, $frequentCategoryCodes);
            return $idx !== false ? (100 - $idx) : ($cat->assets_count ?? 0);
        })->take(5)->values();

        return view('loans.edit', compact('loan', 'employee', 'categories', 'availableAssets', 'popularCategories'));
    }

    /**
     * Update loan draft (DRAFT or NEEDS_REVISION).
     */
    public function update(Request $request, string $id)
    {
        $loan = LoanRequest::with('items')->findOrFail($id);
        $user = Auth::user();

        if ($loan->borrower_id !== $user->employee_id && !$user->isAdmin()) {
            abort(403, 'Hanya peminjam bersangkutan yang dapat mengedit draf ini.');
        }

        if (!in_array($loan->status, ['DRAFT', 'NEEDS_REVISION'])) {
            return redirect()->route('loans.show', $loan->id)
                ->with('error', 'Pengajuan dengan status saat ini tidak dapat diedit.');
        }

        $validated = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'required|uuid|exists:assets,id',
            'loan_type' => 'required|in:ANNUAL,SHORT_TERM',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'purposes' => 'required|array',
            'purposes.*' => 'required|string|max:500',
            'home_address' => 'required|string|max:500',
        ], [
            'asset_ids.required' => 'Pilih minimal satu barang BMN untuk diajukan.',
            'end_date.after_or_equal' => 'Tanggal pengembalian tidak boleh sebelum tanggal peminjaman.',
            'home_address.required' => 'Alamat rumah peminjam wajib diisi untuk kelengkapan dokumen.',
        ]);

        try {
            DB::beginTransaction();

            $employee = $user->employee;
            if ($employee && $employee->home_address !== $validated['home_address']) {
                $employee->home_address = $validated['home_address'];
                $employee->save();
            }

            $loan->update([
                'loan_type' => $validated['loan_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'snap_home_address' => $validated['home_address'],
            ]);

            // Hapus item lama dan tambahkan item baru dengan snapshot
            $loan->items()->delete();
            foreach ($validated['asset_ids'] as $assetId) {
                $asset = Asset::findOrFail($assetId);
                $purpose = $validated['purposes'][$assetId] ?? 'Pelaksanaan tugas kedinasan';

                LoanItem::create([
                    'loan_request_id' => $loan->id,
                    'asset_id' => $asset->id,
                    'purpose' => $purpose,
                    'condition_out' => 'Baik',
                    'snap_item_name' => $asset->item_name,
                    'snap_brand_model' => $asset->brand_model_combined,
                    'snap_nup' => $asset->nup,
                    'snap_year' => $asset->acquisition_year,
                    'snap_value' => $asset->acquisition_value,
                    'is_blocking' => false,
                ]);
            }

            AuditLog::record('UPDATE_LOAN_DRAFT', 'LoanRequest', $loan->id, null, [
                'loan_type' => $loan->loan_type,
                'items_count' => count($validated['asset_ids']),
            ]);

            DB::commit();

            return redirect()->route('loans.show', $loan->id)
                ->with('success', 'Perubahan draf pengajuan peminjaman BMN berhasil disimpan.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit loan draft (DRAFT or NEEDS_REVISION -> SUBMITTED).
     */
    public function submit(Request $request, string $id)
    {
        $loan = LoanRequest::with('items')->findOrFail($id);
        $user = Auth::user();

        if ($loan->borrower_id !== $user->employee_id && !$user->isAdmin()) {
            abort(403, 'Hanya peminjam bersangkutan yang dapat mengirimkan berkas pengajuan ini.');
        }

        if (!in_array($loan->status, ['DRAFT', 'NEEDS_REVISION'])) {
            return redirect()->route('loans.show', $loan->id)
                ->with('error', 'Hanya pengajuan berstatus Draf atau Perlu Perbaikan yang dapat dikirim ke Pengelola BMN.');
        }

        $borrowerSig = $loan->borrower_signature ?: ($user->signature_path ?? null);
        if (empty($borrowerSig) || !Storage::disk('public')->exists($borrowerSig)) {
            return redirect()->route('loans.show', $loan->id)
                ->with('error', 'Anda wajib membubuhkan tanda tangan digital sebelum mengirim berkas pengajuan ke Pengelola BMN.');
        }

        if (empty($loan->borrower_signature)) {
            $loan->borrower_signature = $borrowerSig;
            $loan->save();
        }

        try {
            LoanStateMachine::transition($loan, 'SUBMITTED', $user, 'Pengajuan resmi dikirim oleh peminjam.');
            return redirect()->route('loans.show', $loan->id)
                ->with('success', 'Pengajuan peminjaman BMN berhasil dikirim! Berkas saat ini sedang dalam antrean telaah Pengelola BMN.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show loan request detail with 7-step SOP timeline and documents.
     */
    public function show(string $id)
    {
        $loan = LoanRequest::with(['borrower', 'items.asset', 'history.actor', 'documents.issuer', 'photos.uploader'])
            ->findOrFail($id);

        $user = Auth::user();
        // Otorisasi sederhana: peminjam hanya boleh melihat miliknya, staf/admin boleh melihat semua
        if ($user->role === 'PEMINJAM' && $loan->borrower_id !== $user->employee_id) {
            abort(403, 'Anda tidak berhak melihat pengajuan ini.');
        }

        return view('loans.show', compact('loan'));
    }

    /**
     * Peminjam initiates return request.
     */
    public function requestReturn(string $id)
    {
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        if (!$user->isPeminjam() && !$user->isAdmin()) {
            return back()->with('error', 'Hanya pengguna dengan peran Peminjam yang dapat mengajukan pengembalian BMN.');
        }

        if ($user->role === 'PEMINJAM' && $loan->borrower_id !== $user->employee_id) {
            return back()->with('error', 'Anda hanya dapat mengajukan pengembalian untuk BMN yang Anda pinjam.');
        }

        try {
            LoanStateMachine::transition(
                $loan,
                'RETURN_REQUESTED',
                $user,
                'Peminjam mengajukan pengembalian fisik BMN ke kantor.'
            );
            return back()->with('success', 'Pengajuan pengembalian berhasil dikirim. Silakan serahkan fisik BMN ke Petugas BMN untuk pemeriksaan.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pengelola BMN queue page.
     */
    public function manageQueue(Request $request)
    {
        abort_unless(
            Auth::check() && (Auth::user()->isPengelola() || Auth::user()->isAdmin()),
            403,
            'Akses antrean telaah terbatas untuk Pengelola BMN dan Administrator Sistem.'
        );

        $statusFilter = $request->query('status');

        $baseQuery = LoanRequest::query();

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'SUBMITTED' => (clone $baseQuery)->where('status', 'SUBMITTED')->count(),
            'UNDER_APPROVAL' => (clone $baseQuery)->where('status', 'UNDER_APPROVAL')->count(),
            'APPROVED' => (clone $baseQuery)->where('status', 'APPROVED')->count(),
            'BAST_ISSUED' => (clone $baseQuery)->where('status', 'BAST_ISSUED')->count(),
            'ACTIVE' => (clone $baseQuery)->where('status', 'ACTIVE')->count(),
            'RETURN_REQUESTED' => (clone $baseQuery)->where('status', 'RETURN_REQUESTED')->count(),
            'RETURNED' => (clone $baseQuery)->where('status', 'RETURNED')->count(),
            'REJECTED' => (clone $baseQuery)->whereIn('status', ['REJECTED', 'NEEDS_REVISION'])->count(),
        ];

        $query = LoanRequest::with(['borrower', 'items.asset'])
            ->orderBy('created_at', 'desc');

        if ($statusFilter) {
            if ($statusFilter === 'REJECTED') {
                $query->whereIn('status', ['REJECTED', 'NEEDS_REVISION']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        $loans = $query->paginate(12)->withQueryString();

        return view('kelola.queue', compact('loans', 'statusFilter', 'counts'));
    }

    /**
     * Pengelola BMN review action (SUBMITTED -> UNDER_APPROVAL / NEEDS_REVISION).
     */
    public function review(Request $request, string $id)
    {
        abort_unless(
            Auth::check() && (Auth::user()->isPengelola() || Auth::user()->isAdmin()),
            403,
            'Akses telaah pengajuan terbatas untuk Pengelola BMN dan Administrator Sistem.'
        );

        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        $action = $request->input('action'); // 'approve_review' atau 'request_revision'
        $note = $request->input('note');

        try {
            if ($action === 'approve_review') {
                $officerSig = $loan->officer_signature ?: $user->signature_path;
                if (!$officerSig || !Storage::disk('public')->exists($officerSig)) {
                    return back()->with('error', 'Pengelola BMN wajib memiliki tanda tangan digital sebelum meneruskan berkas telaah ke Ketua Tim.');
                }
                if (empty($loan->officer_signature)) {
                    $loan->officer_signature = $officerSig;
                    $loan->save();
                }

                LoanStateMachine::transition(
                    $loan,
                    'UNDER_APPROVAL',
                    $user,
                    $note ?: 'Dokumen telah lengkap dan barang tersedia dengan kondisi baik. Diteruskan ke Ketua Tim.'
                );
                return redirect()->route('kelola.queue')->with('success', 'Pengajuan telah ditelaah dan diteruskan ke Ketua Tim Keuangan dan Aset.');
            } elseif ($action === 'request_revision') {
                $request->validate(['note' => 'required|string']);
                LoanStateMachine::transition($loan, 'NEEDS_REVISION', $user, $note);
                return redirect()->route('kelola.queue')->with('warning', 'Pengajuan telah dikembalikan ke peminjam untuk perbaikan.');
            }
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Pengelola BMN / Admin deletes a loan request prior to Ketua Tim approval.
     */
    public function destroy(string $id)
    {
        abort_unless(
            Auth::check() && (Auth::user()->isPengelola() || Auth::user()->isAdmin()),
            403,
            'Hanya Pengelola BMN dan Administrator yang memiliki hak untuk menghapus berkas pengajuan ini.'
        );

        $loan = LoanRequest::with(['items', 'photos'])->findOrFail($id);
        $user = Auth::user();

        // Tombol dan fungsi hapus hanya ada sampai ketua aset menyetujui disposisi (sebelum APPROVED)
        $allowedStatuses = ['DRAFT', 'SUBMITTED', 'NEEDS_REVISION', 'UNDER_APPROVAL'];
        if (!in_array($loan->status, $allowedStatuses)) {
            return back()->with('error', 'Pengajuan ini tidak dapat dihapus karena disposisi telah disetujui oleh Ketua Tim Keuangan & Aset.');
        }

        try {
            DB::beginTransaction();

            $loanId = $loan->id;
            $borrowerName = $loan->snap_full_name;
            $itemCount = $loan->items->count();

            AuditLog::record(
                'DELETE_LOAN_REQUEST',
                'LoanRequest',
                $loanId,
                [
                    'status' => $loan->status,
                    'borrower' => $borrowerName,
                    'items_count' => $itemCount,
                ],
                [
                    'deleted_by' => $user->name,
                    'reason' => 'Pengajuan dihapus oleh Pengelola BMN sebelum persetujuan disposisi. Seluruh barang inventaris terkait kembali tersedia penuh.',
                ]
            );

            // Bersihkan file foto dari storage jika ada
            if (Storage::disk('public')->exists('loan_photos/' . $loanId)) {
                Storage::disk('public')->deleteDirectory('loan_photos/' . $loanId);
            }

            // Hapus pengajuan (cascade delete items, status history, documents, photos)
            $loan->delete();

            // Reset cache kategori populer
            Cache::forget('bmn_popular_category_codes');

            DB::commit();

            return redirect()->route('kelola.queue')
                ->with('success', "Pengajuan peminjaman #{$loanId} atas nama {$borrowerName} berhasil dihapus. Seluruh barang inventaris terkait kini telah kembali berstatus Tersedia.");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus pengajuan: ' . $e->getMessage());
        }
    }

    /**
     * Pengelola BMN issues BAST Peminjaman (APPROVED -> BAST_ISSUED).
     */
    public function issueBast(Request $request, string $id)
    {
        abort_unless(
            Auth::check() && (Auth::user()->isPengelola() || Auth::user()->isAdmin()),
            403,
            'Akses penerbitan BAST Peminjaman terbatas untuk Pengelola BMN dan Administrator.'
        );

        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        $officerSig = $loan->officer_signature ?: $user->signature_path;
        if (!$officerSig || !Storage::disk('public')->exists($officerSig)) {
            return back()->with('error', 'Petugas Pengelola BMN wajib memiliki tanda tangan digital sebelum menerbitkan BAST Peminjaman resmi.');
        }
        if (empty($loan->officer_signature)) {
            $loan->officer_signature = $officerSig;
            $loan->save();
        }

        // Validasi ketersediaan foto fisik pendukung BAST Peminjaman (maksimal 5 foto)
        $existingPhotosCount = $loan->photos()->where('stage', 'BAST_PEMINJAMAN')->count();
        $rawFiles = (array)$request->file('photos', []);
        $validFiles = array_values(array_filter($rawFiles, fn($f) => $f instanceof \Illuminate\Http\UploadedFile && $f->isValid()));

        if ($existingPhotosCount === 0 && empty($validFiles)) {
            return back()->with('error', 'Dokumentasi foto fisik BMN wajib dilampirkan sebelum menerbitkan BAST Peminjaman agar dokumen memiliki bukti dukung fisik.');
        }

        try {
            // Simpan foto dokumentasi fisik BMN (maksimal 5 foto terlampir)
            if (!empty($validFiles)) {
                $maxAllowed = max(1, 5 - $existingPhotosCount);
                $filesToSave = array_slice($validFiles, 0, $maxAllowed);
                foreach ($filesToSave as $photoFile) {
                    $fileName = 'loan_photos/' . $loan->id . '/' . time() . '_' . Str::random(6) . '.' . $photoFile->getClientOriginalExtension();
                    $path = $photoFile->storeAs('loan_photos/' . $loan->id, basename($fileName), 'public');

                    LoanPhoto::create([
                        'loan_request_id' => $loan->id,
                        'stage' => 'BAST_PEMINJAMAN',
                        'file_path' => $path,
                        'original_name' => $photoFile->getClientOriginalName(),
                        'caption' => 'Foto fisik BMN saat penerbitan BAST Peminjaman',
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            LoanStateMachine::transition(
                $loan,
                'BAST_ISSUED',
                $user,
                'Draf BAST Peminjaman telah diterbitkan oleh Pengelola BMN.'
            );
            return back()->with('success', 'BAST Peminjaman bernomor berhasil diterbitkan. Silakan cetak untuk ditandatangani.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pengelola BMN confirms physical handover & signed BAST (BAST_ISSUED -> ACTIVE).
     */
    public function confirmActive(Request $request, string $id)
    {
        abort_unless(
            Auth::check() && (Auth::user()->isPengelola() || Auth::user()->isAdmin()),
            403,
            'Akses konfirmasi penyerahan BMN terbatas untuk Pengelola BMN.'
        );

        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        try {
            // Simpan foto dokumentasi jika dilampirkan
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photoFile) {
                    if (!$photoFile || !$photoFile->isValid()) {
                        continue;
                    }
                    $fileName = 'loan_photos/' . $loan->id . '/' . time() . '_' . Str::random(6) . '.' . $photoFile->getClientOriginalExtension();
                    $path = $photoFile->storeAs('loan_photos/' . $loan->id, basename($fileName), 'public');

                    LoanPhoto::create([
                        'loan_request_id' => $loan->id,
                        'stage' => 'BAST_PEMINJAMAN',
                        'file_path' => $path,
                        'original_name' => $photoFile->getClientOriginalName(),
                        'caption' => 'Foto dokumentasi serah terima BAST Peminjaman',
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            LoanStateMachine::transition(
                $loan,
                'ACTIVE',
                $user,
                'BAST Peminjaman telah ditandatangani dan fisik BMN diserahkan ke Peminjam.'
            );
            return back()->with('success', 'Status pengajuan kini AKTIF (Sedang Dipinjam). Status fisik BMN diperbarui ke ON_LOAN.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pengelola BMN inspects returned items condition (RETURN_REQUESTED -> RETURN_CHECKED).
     */
    public function processReturnCheck(Request $request, string $id)
    {
        abort_unless(
            Auth::check() && (Auth::user()->isPengelola() || Auth::user()->isAdmin()),
            403,
            'Akses pemeriksaan fisik pengembalian terbatas untuk Pengelola BMN.'
        );

        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        $officerSig = $loan->officer_signature ?: $user->signature_path;
        if (!$officerSig || !Storage::disk('public')->exists($officerSig)) {
            return back()->with('error', 'Petugas Pengelola BMN wajib memiliki tanda tangan digital sebelum menerbitkan BAST Pengembalian resmi.');
        }
        if (empty($loan->officer_signature)) {
            $loan->officer_signature = $officerSig;
            $loan->save();
        }

        // Validasi ketersediaan foto fisik pendukung BAST Pengembalian (maksimal 5 foto)
        $existingPhotosCount = $loan->photos()->where('stage', 'BAST_PENGEMBALIAN')->count();
        $rawFiles = (array)$request->file('photos', []);
        $validFiles = array_values(array_filter($rawFiles, fn($f) => $f instanceof \Illuminate\Http\UploadedFile && $f->isValid()));

        if ($existingPhotosCount === 0 && empty($validFiles)) {
            return back()->with('error', 'Dokumentasi foto fisik pengembalian BMN wajib dilampirkan sebelum menerbitkan BAST Pengembalian agar kondisi akhir tercatat valid.');
        }

        $itemsCondition = $request->input('items', []);

        try {
            // Simpan foto dokumentasi fisik BMN (maksimal 5 foto terlampir)
            if (!empty($validFiles)) {
                $maxAllowed = max(1, 5 - $existingPhotosCount);
                $filesToSave = array_slice($validFiles, 0, $maxAllowed);
                foreach ($filesToSave as $photoFile) {
                    $fileName = 'loan_photos/' . $loan->id . '/' . time() . '_' . Str::random(6) . '.' . $photoFile->getClientOriginalExtension();
                    $path = $photoFile->storeAs('loan_photos/' . $loan->id, basename($fileName), 'public');

                    LoanPhoto::create([
                        'loan_request_id' => $loan->id,
                        'stage' => 'BAST_PENGEMBALIAN',
                        'file_path' => $path,
                        'original_name' => $photoFile->getClientOriginalName(),
                        'caption' => 'Foto dokumentasi fisik saat pengembalian BMN',
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            LoanStateMachine::transition(
                $loan,
                'RETURN_CHECKED',
                $user,
                'Pengecekan kesesuaian fisik BMN telah selesai dilakukan oleh Petugas BMN.',
                ['items_condition' => $itemsCondition]
            );
            return back()->with('success', 'Pemeriksaan fisik selesai dan BAST Pengembalian bernomor berhasil diterbitkan.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pengelola BMN finalizes return receipt (RETURN_CHECKED -> RETURNED).
     */
    public function finalizeReturn(string $id)
    {
        abort_unless(
            Auth::check() && (Auth::user()->isPengelola() || Auth::user()->isAdmin()),
            403,
            'Akses penyelesaian pengembalian BMN terbatas untuk Pengelola BMN.'
        );

        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        try {
            LoanStateMachine::transition(
                $loan,
                'RETURNED',
                $user,
                'BAST Pengembalian bertanda tangan diterima. Siklus peminjaman selesai dan BMN disimpan kembali.'
            );
            return back()->with('success', 'Pengembalian BMN telah selesai diproses. Status BMN kembali tersedia di inventaris.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unggah foto dokumentasi BAST (Peminjaman / Pengembalian) sewaktu-waktu.
     */
    public function uploadPhotos(Request $request, string $id)
    {
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        // Otorisasi: Peminjam hanya boleh untuk peminjamannya sendiri, pengelola & admin boleh untuk semua
        if ($user->role === 'PEMINJAM' && $loan->borrower_id !== $user->employee_id) {
            abort(403, 'Anda tidak berhak mengunggah foto untuk pengajuan peminjaman ini.');
        }
        if (!$user->isPeminjam() && !$user->isPengelola() && !$user->isAdmin()) {
            abort(403, 'Peran Anda tidak diizinkan untuk mengunggah foto dokumentasi.');
        }

        $rawFiles = (array) $request->file('photos');
        $cleanFiles = array_values(array_filter($rawFiles, function ($f) {
            return $f instanceof \Illuminate\Http\UploadedFile && $f->isValid();
        }));

        $validator = \Illuminate\Support\Facades\Validator::make([
            'stage' => $request->input('stage'),
            'caption' => $request->input('caption'),
            'photos' => $cleanFiles,
        ], [
            'stage' => 'required|in:BAST_PEMINJAMAN,BAST_PENGEMBALIAN,GENERAL',
            'caption' => 'nullable|string|max:255',
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240',
        ], [
            'photos.required' => 'Pilih minimal 1 berkas foto.',
            'photos.*.image' => 'Berkas harus berupa gambar.',
            'photos.*.max' => 'Ukuran maksimal berkas foto adalah 10 MB.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $stage = $request->input('stage');
        $caption = $request->input('caption');
        $uploadedCount = 0;

        foreach ($cleanFiles as $photoFile) {
            $fileName = 'loan_photos/' . $loan->id . '/' . time() . '_' . Str::random(6) . '.' . $photoFile->getClientOriginalExtension();
            $path = $photoFile->storeAs('loan_photos/' . $loan->id, basename($fileName), 'public');

            LoanPhoto::create([
                'loan_request_id' => $loan->id,
                'stage' => $stage,
                'file_path' => $path,
                'original_name' => $photoFile->getClientOriginalName(),
                'caption' => $caption,
                'uploaded_by' => $user->id,
            ]);

            $uploadedCount++;
        }

        AuditLog::record(
            'UPLOAD_LOAN_PHOTOS',
            'LoanRequest',
            $loan->id,
            null,
            ['count' => $uploadedCount, 'stage' => $stage]
        );

        return back()->with('success', "{$uploadedCount} foto dokumentasi BAST berhasil diunggah.");
    }

    /**
     * Hapus foto dokumentasi BAST.
     */
    public function deletePhoto(string $photoId)
    {
        $photo = LoanPhoto::findOrFail($photoId);
        $user = Auth::user();

        // Otorisasi: pemilik foto, pengelola BMN, atau admin
        if ($photo->uploaded_by !== $user->id && !$user->isPengelola() && !$user->isAdmin()) {
            abort(403, 'Anda tidak berhak menghapus foto ini.');
        }

        if (Storage::disk('public')->exists($photo->file_path)) {
            Storage::disk('public')->delete($photo->file_path);
        }

        $loanId = $photo->loan_request_id;
        $photo->delete();

        AuditLog::record('DELETE_LOAN_PHOTO', 'LoanPhoto', $photoId, ['loan_id' => $loanId]);

        return back()->with('success', 'Foto dokumentasi berhasil dihapus.');
    }

    /**
     * Tanda tangani dokumen peminjaman / BAST secara digital.
     */
    public function signDocument(Request $request, string $id)
    {
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        $signAs = $request->input('sign_as'); // 'borrower' atau 'officer'
        $source = $request->input('source');   // 'profile', 'pad', 'file'

        // Otorisasi peran penandatangan
        if ($signAs === 'borrower') {
            if ($loan->borrower_id !== $user->employee_id && !$user->isAdmin()) {
                return back()->with('error', 'Hanya peminjam bersangkutan yang dapat menandatangani sebagai Pihak Kedua.');
            }
        } elseif ($signAs === 'officer') {
            if (!$user->isPengelola() && !$user->isAdmin()) {
                return back()->with('error', 'Hanya Pengelola BMN yang berhak menandatangani sebagai Pihak Pertama.');
            }
        } else {
            return back()->with('error', 'Pilihan pihak penandatangan tidak valid.');
        }

        $signaturePath = null;

        // Opsi 1: Gunakan dari profil
        if ($source === 'profile') {
            if (!$user->signature_path || !Storage::disk('public')->exists($user->signature_path)) {
                return back()->with('error', 'Anda belum memiliki spesimen tanda tangan di profil. Silakan gambar di kanvas atau unggah baru.');
            }
            $signaturePath = $user->signature_path;
        }

        // Opsi 2: Gambar di kanvas (Base64)
        elseif ($source === 'pad') {
            $dataUri = $request->input('signature_data');
            if (empty($dataUri) || !preg_match('/^data:image\/(png|jpeg);base64,/', $dataUri)) {
                return back()->with('error', 'Goresan tanda tangan kanvas tidak valid.');
            }

            $encodedImage = substr($dataUri, strpos($dataUri, ',') + 1);
            $decodedImage = base64_decode($encodedImage);
            if ($decodedImage === false) {
                return back()->with('error', 'Gagal memproses gambar tanda tangan.');
            }

            $fileName = 'signatures/loan_' . $loan->id . '_' . $signAs . '_' . time() . '.png';
            Storage::disk('public')->put($fileName, $decodedImage);
            $signaturePath = $fileName;

            // Jika dicentang simpan ke profil
            if ($request->boolean('save_to_profile')) {
                $user->signature_path = $fileName;
                $user->save();
            }
        }

        // Opsi 3: Unggah berkas gambar
        elseif ($source === 'file') {
            $request->validate([
                'signature_file' => 'required|image|mimes:png,jpg,jpeg|max:2048',
            ]);

            $file = $request->file('signature_file');
            $fileName = 'signatures/loan_' . $loan->id . '_' . $signAs . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('signatures', basename($fileName), 'public');
            $signaturePath = $path;

            if ($request->boolean('save_to_profile')) {
                $user->signature_path = $path;
                $user->save();
            }
        } else {
            return back()->with('error', 'Metode tanda tangan tidak valid.');
        }

        // Simpan ke kolom loan_requests
        if ($signAs === 'borrower') {
            $loan->borrower_signature = $signaturePath;
        } else {
            $loan->officer_signature = $signaturePath;
        }
        $loan->save();

        AuditLog::record(
            "SIGN_LOAN_{$signAs}",
            'LoanRequest',
            $loan->id,
            null,
            ['sign_as' => $signAs, 'source' => $source, 'file' => $signaturePath]
        );

        return back()->with('success', 'Tanda tangan digital berhasil dibubuhkan pada dokumen pengajuan ini.');
    }
}

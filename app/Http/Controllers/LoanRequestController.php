<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\LoanItem;
use App\Models\LoanRequest;
use App\Services\LoanStateMachine;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanRequestController extends Controller
{
    /**
     * List user's own loan requests (Peminjam).
     */
    public function index()
    {
        $user = Auth::user();
        $loans = LoanRequest::where('borrower_id', $user->employee_id)
            ->with(['items.asset', 'documents'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('loans.index', compact('loans'));
    }

    /**
     * Show the 4-step wizard form to submit a new loan request.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Akun Anda belum terhubung dengan data pegawai.');
        }

        // Ambil barang yang tersedia dan berkondisi baik (BR-03)
        $availableAssets = Asset::where('status', 'AVAILABLE')
            ->where('condition', 'BAIK')
            ->with('category')
            ->orderBy('item_name')
            ->get();

        $selectedAssetId = $request->query('asset_id');

        return view('loans.create', compact('employee', 'availableAssets', 'selectedAssetId'));
    }

    /**
     * Store new loan request (Wizard submission).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

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

            // Simpan pembaruan alamat rumah pegawai jika diubah
            if ($employee->home_address !== $validated['home_address']) {
                $employee->home_address = $validated['home_address'];
                $employee->save();
            }

            // Buat loan_request dengan snapshot data diri peminjam (BR-07)
            $loan = LoanRequest::create([
                'borrower_id' => $employee->id,
                'loan_type' => $validated['loan_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'DRAFT',
                'snap_full_name' => $employee->full_name,
                'snap_nip' => $employee->nip,
                'snap_rank_grade' => $employee->rank_grade,
                'snap_position' => $employee->position,
                'snap_home_address' => $validated['home_address'],
            ]);

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

            // Transisi ke SUBMITTED via state machine (cek bentrok & kunci barang)
            LoanStateMachine::transition($loan, 'SUBMITTED', $user, 'Pengajuan baru diajukan oleh peminjam.');

            DB::commit();

            return redirect()->route('loans.show', $loan->id)
                ->with('success', 'Pengajuan peminjaman BMN berhasil dikirim! Menunggu telaah Pengelola BMN.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show loan request detail with 7-step SOP timeline and documents.
     */
    public function show(string $id)
    {
        $loan = LoanRequest::with(['borrower', 'items.asset', 'history.actor', 'documents.issuer'])
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
        $statusFilter = $request->query('status');

        $query = LoanRequest::with(['borrower', 'items.asset'])
            ->orderBy('created_at', 'desc');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $loans = $query->paginate(12)->withQueryString();

        return view('kelola.queue', compact('loans', 'statusFilter'));
    }

    /**
     * Pengelola BMN review action (SUBMITTED -> UNDER_APPROVAL / NEEDS_REVISION).
     */
    public function review(Request $request, string $id)
    {
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        $action = $request->input('action'); // 'approve_review' atau 'request_revision'
        $note = $request->input('note');

        try {
            if ($action === 'approve_review') {
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
     * Pengelola BMN issues BAST Peminjaman (APPROVED -> BAST_ISSUED).
     */
    public function issueBast(string $id)
    {
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        try {
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
    public function confirmActive(string $id)
    {
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        try {
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
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        $itemsCondition = $request->input('items', []);

        try {
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
}

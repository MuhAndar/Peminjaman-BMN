<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoanRequest;
use App\Services\LoanStateMachine;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{
    /**
     * Memastikan hanya Ketua Tim Keuangan & Aset atau Admin yang dapat mengakses modul persetujuan.
     */
    private function authorizeApproval(): void
    {
        abort_unless(
            Auth::check() && (Auth::user()->isKetuaTim() || Auth::user()->isAdmin()),
            403,
            'Akses khusus Ketua Tim Keuangan & Aset atau Administrator Sistem.'
        );
    }

    public function index(Request $request)
    {
        $this->authorizeApproval();

        $tab = $request->query('tab', 'waiting');

        $query = LoanRequest::with(['borrower', 'items.asset', 'documents']);

        if ($tab === 'approved') {
            $query->whereIn('status', ['APPROVED', 'BAST_ISSUED', 'ACTIVE', 'RETURN_REQUESTED', 'RETURN_CHECKED', 'RETURNED'])
                ->orderBy('updated_at', 'desc');
        } elseif ($tab === 'rejected') {
            $query->whereIn('status', ['REJECTED', 'NEEDS_REVISION', 'REVISION_REQUESTED'])
                ->orderBy('updated_at', 'desc');
        } elseif ($tab === 'all') {
            $query->orderBy('created_at', 'desc');
        } else {
            $tab = 'waiting';
            $query->where('status', 'UNDER_APPROVAL')
                ->orderBy('created_at', 'asc');
        }

        $loans = $query->paginate(10)->withQueryString();

        $counts = [
            'waiting' => LoanRequest::where('status', 'UNDER_APPROVAL')->count(),
            'approved' => LoanRequest::whereIn('status', ['APPROVED', 'BAST_ISSUED', 'ACTIVE', 'RETURN_REQUESTED', 'RETURN_CHECKED', 'RETURNED'])->count(),
            'rejected' => LoanRequest::whereIn('status', ['REJECTED', 'NEEDS_REVISION', 'REVISION_REQUESTED'])->count(),
            'all' => LoanRequest::count(),
        ];

        return view('approval.index', compact('loans', 'tab', 'counts'));
    }

    public function show(string $id)
    {
        $this->authorizeApproval();

        $loan = LoanRequest::with(['borrower', 'items.asset', 'history.actor', 'documents'])
            ->findOrFail($id);

        $user = Auth::user();
        $isSelf = ($user->employee_id && $user->employee_id === $loan->borrower_id);

        return view('approval.show', compact('loan', 'isSelf'));
    }

    /**
     * Menyimpan tanda tangan digital Ketua Tim sebagai draft autosave pada pengajuan
     * sehingga saat halaman di-refresh, tanda tangan tetap terpasang tanpa hilang.
     */
    public function saveDraftSignature(Request $request, string $id)
    {
        $this->authorizeApproval();

        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        $source = $request->input('signature_source'); // 'profile', 'pad', 'file'
        $signaturePath = null;

        if ($source === 'profile') {
            if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                $signaturePath = $user->signature_path;
            }
        } elseif ($source === 'pad') {
            $dataUri = $request->input('signature_data');
            if (!empty($dataUri) && preg_match('/^data:image\/(png|jpeg);base64,/', $dataUri)) {
                $encodedImage = substr($dataUri, strpos($dataUri, ',') + 1);
                $decodedImage = base64_decode($encodedImage);
                if ($decodedImage !== false) {
                    $fileName = 'signatures/approver_' . $loan->id . '_' . time() . '_' . Str::random(4) . '.png';
                    Storage::disk('public')->put($fileName, $decodedImage);
                    $signaturePath = $fileName;

                    if ($request->boolean('save_to_profile')) {
                        $user->signature_path = $fileName;
                        $user->save();
                    }
                }
            }
        } elseif ($source === 'file') {
            if ($request->hasFile('signature_file') && $request->file('signature_file')->isValid()) {
                $request->validate([
                    'signature_file' => 'required|image|mimes:png,jpg,jpeg|max:2048',
                ]);
                $file = $request->file('signature_file');
                $fileName = 'signatures/approver_' . $loan->id . '_' . time() . '_' . Str::random(4) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('signatures', basename($fileName), 'public');
                $signaturePath = $path;

                if ($request->boolean('save_to_profile')) {
                    $user->signature_path = $path;
                    $user->save();
                }
            }
        }

        if ($signaturePath) {
            $loan->approver_signature = $signaturePath;
            $loan->save();

            return response()->json([
                'ok' => true,
                'signature_url' => asset('storage/' . $signaturePath),
                'signature_path' => $signaturePath,
                'source' => $source,
            ]);
        }

        return response()->json(['ok' => false, 'message' => 'Tanda tangan tidak valid atau belum dipilih'], 422);
    }

    public function approve(Request $request, string $id)
    {
        $this->authorizeApproval();

        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();
        $note = $request->input('note', 'Pengajuan peminjaman BMN disetujui oleh Ketua Tim Keuangan dan Aset.');

        // Proses tanda tangan digital Ketua Tim jika diberikan
        $source = $request->input('signature_source'); // 'profile', 'pad', 'file', null
        $signaturePath = null;

        if ($source === 'profile') {
            if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                $signaturePath = $user->signature_path;
            }
        } elseif ($source === 'pad') {
            $dataUri = $request->input('signature_data');
            if (!empty($dataUri) && preg_match('/^data:image\/(png|jpeg);base64,/', $dataUri)) {
                $encodedImage = substr($dataUri, strpos($dataUri, ',') + 1);
                $decodedImage = base64_decode($encodedImage);
                if ($decodedImage !== false) {
                    $fileName = 'signatures/approver_' . $loan->id . '_' . time() . '_' . Str::random(4) . '.png';
                    Storage::disk('public')->put($fileName, $decodedImage);
                    $signaturePath = $fileName;

                    if ($request->boolean('save_to_profile')) {
                        $user->signature_path = $fileName;
                        $user->save();
                    }
                }
            }
        } elseif ($source === 'file') {
            if ($request->hasFile('signature_file') && $request->file('signature_file')->isValid()) {
                $request->validate([
                    'signature_file' => 'required|image|mimes:png,jpg,jpeg|max:2048',
                ]);
                $file = $request->file('signature_file');
                $fileName = 'signatures/approver_' . $loan->id . '_' . time() . '_' . Str::random(4) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('signatures', basename($fileName), 'public');
                $signaturePath = $path;

                if ($request->boolean('save_to_profile')) {
                    $user->signature_path = $path;
                    $user->save();
                }
            }
        }

        // Jika tidak memilih secara eksplisit tapi loan sudah punya approver_signature yang tersimpan (misal dari draft autosave)
        if (!$signaturePath && $loan->approver_signature && Storage::disk('public')->exists($loan->approver_signature)) {
            $signaturePath = $loan->approver_signature;
        }

        // Jika tidak memilih secara eksplisit tapi user punya tanda tangan profil, gunakan sebagai default
        if (!$signaturePath && $user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
            $signaturePath = $user->signature_path;
        }

        // Validasi Wajib: Dokumen resmi Surat Peminjaman tidak boleh terbit tanpa tanda tangan resmi Ketua Tim
        if (!$signaturePath) {
            return back()->with('error', 'Tanda tangan digital Ketua Tim Keuangan & Aset wajib dibubuhkan sebelum menyetujui dan menerbitkan Surat Peminjaman resmi.');
        }

        $loan->approver_signature = $signaturePath;
        $loan->save();

        AuditLog::record(
            'SIGN_LOAN_APPROVER',
            'LoanRequest',
            $loan->id,
            null,
            ['source' => $source ?: 'profile_default', 'file' => $signaturePath]
        );

        try {
            LoanStateMachine::transition($loan, 'APPROVED', $user, $note);
            return redirect()->route('persetujuan.index')->with('success', 'Pengajuan berhasil disetujui! Surat Peminjaman resmi bernomor otomatis telah diterbitkan.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function requestRevision(Request $request, string $id)
    {
        $this->authorizeApproval();

        $request->validate(['note' => 'required|string']);
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        try {
            LoanStateMachine::transition($loan, 'NEEDS_REVISION', $user, $request->input('note'));
            return redirect()->route('persetujuan.index')->with('warning', 'Pengajuan dikembalikan ke peminjam untuk perbaikan.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, string $id)
    {
        $this->authorizeApproval();

        $request->validate(['note' => 'required|string']);
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();

        try {
            LoanStateMachine::transition($loan, 'REJECTED', $user, $request->input('note'));
            return redirect()->route('persetujuan.index')->with('error', 'Pengajuan peminjaman resmi ditolak.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

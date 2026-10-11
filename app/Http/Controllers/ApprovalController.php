<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use App\Services\LoanStateMachine;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function index()
    {
        $loans = LoanRequest::where('status', 'UNDER_APPROVAL')
            ->with(['borrower', 'items.asset'])
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return view('approval.index', compact('loans'));
    }

    public function show(string $id)
    {
        $loan = LoanRequest::with(['borrower', 'items.asset', 'history.actor', 'documents'])
            ->findOrFail($id);

        return view('approval.show', compact('loan'));
    }

    public function approve(Request $request, string $id)
    {
        $loan = LoanRequest::findOrFail($id);
        $user = Auth::user();
        $note = $request->input('note', 'Pengajuan peminjaman BMN disetujui oleh Ketua Tim Keuangan dan Aset.');

        try {
            LoanStateMachine::transition($loan, 'APPROVED', $user, $note);
            return redirect()->route('persetujuan.index')->with('success', 'Pengajuan berhasil disetujui! Surat Peminjaman resmi bernomor otomatis telah diterbitkan.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function requestRevision(Request $request, string $id)
    {
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

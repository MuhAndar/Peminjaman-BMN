<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\LoanRequest;
use App\Services\BmnDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    /**
     * Preview or download official PDF document.
     * Dilengkapi proteksi IDOR / Broken Access Control sesuai UU PDP & Aturan Keamanan.
     */
    public function showPdf(string $loanId, string $docType)
    {
        $user = Auth::user();
        abort_unless($user, 401, 'Silakan login terlebih dahulu.');

        $loan = LoanRequest::with(['borrower', 'items.asset', 'documents'])->findOrFail($loanId);

        // Keamanan Akses Dokumen Resmi BMN:
        // Peminjam hanya berhak melihat atau mengunduh dokumen pengajuannya sendiri.
        if ($user->role === 'PEMINJAM' && $loan->borrower_id !== $user->employee_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat atau mengunduh dokumen peminjaman ini.');
        }

        $document = $loan->documents()->where('doc_type', $docType)->where('status', 'ISSUED')->first();

        return BmnDocumentService::generatePdf($loan, $docType, $document);
    }
}

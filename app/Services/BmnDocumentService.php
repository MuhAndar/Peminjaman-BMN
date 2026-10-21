<?php

namespace App\Services;

use App\Models\Document;
use App\Models\LoanRequest;
use App\Models\Signatory;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Response;

class BmnDocumentService
{
    /**
     * Generate PDF stream/download for a given loan document.
     */
    public static function generatePdf(LoanRequest $loan, string $docType, ?Document $document = null)
    {
        $signatoryKetua = Signatory::where('role', 'KETUA_TIM_KEUANGAN_ASET')->with('employee')->first();
        $signatoryPengelola = Signatory::where('role', 'PENGELOLA_ASET')->with('employee')->first();
        $suratPeminjamanDoc = $loan->documents()->where('doc_type', 'SURAT_PEMINJAMAN')->where('status', 'ISSUED')->first();

        $borrowerSignatureBase64 = null;
        if (!empty($loan->borrower_signature)) {
            if (str_starts_with($loan->borrower_signature, 'data:image')) {
                $borrowerSignatureBase64 = $loan->borrower_signature;
            } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($loan->borrower_signature)) {
                $content = \Illuminate\Support\Facades\Storage::disk('public')->get($loan->borrower_signature);
                $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($loan->borrower_signature) ?: 'image/png';
                $borrowerSignatureBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        }

        $officerSignatureBase64 = null;
        if (!empty($loan->officer_signature)) {
            if (str_starts_with($loan->officer_signature, 'data:image')) {
                $officerSignatureBase64 = $loan->officer_signature;
            } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($loan->officer_signature)) {
                $content = \Illuminate\Support\Facades\Storage::disk('public')->get($loan->officer_signature);
                $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($loan->officer_signature) ?: 'image/png';
                $officerSignatureBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        }

        $data = [
            'loan' => $loan,
            'document' => $document,
            'signatoryKetua' => $signatoryKetua,
            'signatoryPengelola' => $signatoryPengelola,
            'suratPeminjamanDoc' => $suratPeminjamanDoc,
            'borrowerSignatureBase64' => $borrowerSignatureBase64,
            'officerSignatureBase64' => $officerSignatureBase64,
        ];

        switch ($docType) {
            case 'SURAT_PEMINJAMAN':
                $view = 'pdf.surat-peminjaman';
                $filename = 'Surat_Peminjaman_' . ($document->doc_number ? str_replace('/', '_', $document->doc_number) : 'DRAF') . '.pdf';
                break;

            case 'BAST_PEMINJAMAN':
                $view = 'pdf.bast-peminjaman';
                $filename = 'BAST_Peminjaman_' . ($document->doc_number ? str_replace('/', '_', $document->doc_number) : 'DRAF') . '.pdf';
                break;

            case 'BAST_PENGEMBALIAN':
                $view = 'pdf.bast-pengembalian';
                $filename = 'BAST_Pengembalian_' . ($document->doc_number ? str_replace('/', '_', $document->doc_number) : 'DRAF') . '.pdf';
                break;

            default:
                throw new Exception("Tipe dokumen {$docType} tidak dikenal.");
        }

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }
}

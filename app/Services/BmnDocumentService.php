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
        $signatoryKetua = Signatory::where('role', 'KETUA_TIM_KEUANGAN_ASET')->with('employee.user')->first();
        $signatoryPengelola = Signatory::where('role', 'PENGELOLA_ASET')->with('employee.user')->first();
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

        $ketuaSignatureBase64 = null;
        if (in_array($loan->status, ['APPROVED', 'BAST_ISSUED', 'ACTIVE', 'RETURN_REQUESTED', 'RETURN_CHECKED', 'RETURNED'])) {
            $ketuaUser = $signatoryKetua && $signatoryKetua->employee ? $signatoryKetua->employee->user : null;
            if (!$ketuaUser) {
                $ketuaUser = \App\Models\User::where('role', 'KETUA_TIM_ASET')->whereNotNull('signature_path')->first();
            }
            if ($ketuaUser && !empty($ketuaUser->signature_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($ketuaUser->signature_path)) {
                $content = \Illuminate\Support\Facades\Storage::disk('public')->get($ketuaUser->signature_path);
                $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($ketuaUser->signature_path) ?: 'image/png';
                $ketuaSignatureBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        }

        $kopPath = public_path('images/kop_surat.png');
        if (!file_exists($kopPath)) {
            $kopPath = public_path('images/kop_surat_full.png');
        }
        if (!file_exists($kopPath)) {
            $kopPath = base_path('Starter Kit/Referensi Dokumen dan SOP BMN/kop surat.PNG');
        }
        $kopSrc = file_exists($kopPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($kopPath))
            : '';

        // Ambil foto dokumentasi fisik untuk dilampirkan pada halaman terakhir BAST
        $photos = [];
        if (in_array($docType, ['BAST_PEMINJAMAN', 'BAST_PENGEMBALIAN'])) {
            $photoRecords = $loan->photos()->where('stage', $docType)->get();
            foreach ($photoRecords as $pr) {
                $pPath = storage_path('app/public/' . $pr->file_path);
                if (file_exists($pPath)) {
                    $mime = mime_content_type($pPath) ?: 'image/jpeg';
                    $photos[] = [
                        'src' => 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($pPath)),
                        'caption' => $pr->caption ?: $pr->original_name,
                        'date' => $pr->created_at->format('d/m/Y H:i'),
                    ];
                }
            }
        }

        $data = [
            'loan' => $loan,
            'document' => $document,
            'docType' => $docType,
            'photos' => $photos,
            'signatoryKetua' => $signatoryKetua,
            'signatoryPengelola' => $signatoryPengelola,
            'suratPeminjamanDoc' => $suratPeminjamanDoc,
            'borrowerSignatureBase64' => $borrowerSignatureBase64,
            'officerSignatureBase64' => $officerSignatureBase64,
            'ketuaSignatureBase64' => $ketuaSignatureBase64,
            'kopSrc' => $kopSrc,
        ];

        switch ($docType) {
            case 'SURAT_PEMINJAMAN':
                $view = 'pdf.surat-peminjaman';
                $filename = 'Surat_Peminjaman_' . (!empty($document?->doc_number) ? str_replace('/', '_', $document->doc_number) : 'DRAF') . '.pdf';
                break;

            case 'BAST_PEMINJAMAN':
                $view = 'pdf.bast-peminjaman';
                $filename = 'BAST_Peminjaman_' . (!empty($document?->doc_number) ? str_replace('/', '_', $document->doc_number) : 'DRAF') . '.pdf';
                break;

            case 'BAST_PENGEMBALIAN':
                $view = 'pdf.bast-pengembalian';
                $filename = 'BAST_Pengembalian_' . (!empty($document?->doc_number) ? str_replace('/', '_', $document->doc_number) : 'DRAF') . '.pdf';
                break;

            default:
                throw new Exception("Tipe dokumen {$docType} tidak dikenal.");
        }

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }
}

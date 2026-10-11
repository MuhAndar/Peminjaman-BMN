<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\LoanRequest;
use App\Models\LoanStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class LoanStateMachine
{
    /**
     * The single entry point for all loan request state transitions.
     * Enforces BR-04, BR-05, BR-08, BR-10 and single database transaction integrity.
     */
    public static function transition(
        LoanRequest $loan,
        string $toStatus,
        User $actor,
        ?string $note = null,
        array $extraData = []
    ): array {
        return DB::transaction(function () use ($loan, $toStatus, $actor, $note, $extraData) {
            $fromStatus = $loan->status;

            // 1. Validate Valid Transitions & Role
            self::validateTransition($loan, $fromStatus, $toStatus, $actor, $note);

            // 2. Perform state-specific side effects and guards
            switch ($toStatus) {
                case 'SUBMITTED':
                    // Check item count & overlapping loans
                    if ($loan->items()->count() === 0) {
                        throw new Exception('Pengajuan harus memuat minimal 1 barang BMN.');
                    }
                    self::checkConflict($loan);

                    $loan->items()->update(['is_blocking' => true]);
                    $loan->submitted_at = Carbon::now('Asia/Makassar');
                    break;

                case 'NEEDS_REVISION':
                    if (empty($note)) {
                        throw new Exception('Catatan perbaikan wajib diisi.');
                    }
                    $loan->review_note = $note;
                    $loan->items()->update(['is_blocking' => false]);
                    break;

                case 'UNDER_APPROVAL':
                    $loan->review_note = $note ?? 'Dokumen dan ketersediaan telah ditelaah lengkap.';
                    break;

                case 'APPROVED':
                    $loan->decision_note = $note ?? 'Pengajuan peminjaman disetujui.';
                    // Terbitkan Surat Peminjaman bernomor atomik (BR-06)
                    $docNumber = DocumentNumberService::generateNextNumber('SURAT_PEMINJAMAN');
                    Document::create([
                        'loan_request_id' => $loan->id,
                        'doc_type' => 'SURAT_PEMINJAMAN',
                        'doc_number' => $docNumber,
                        'doc_date' => Carbon::now('Asia/Makassar')->toDateString(),
                        'status' => 'ISSUED',
                        'issued_by' => $actor->id,
                        'issued_at' => Carbon::now('Asia/Makassar'),
                    ]);
                    break;

                case 'REJECTED':
                    if (empty($note)) {
                        throw new Exception('Alasan penolakan wajib diisi.');
                    }
                    $loan->decision_note = $note;
                    $loan->items()->update(['is_blocking' => false]);
                    break;

                case 'BAST_ISSUED':
                    // Terbitkan BAST Peminjaman bernomor atomik
                    $docNumber = DocumentNumberService::generateNextNumber('BAST_PEMINJAMAN');
                    Document::create([
                        'loan_request_id' => $loan->id,
                        'doc_type' => 'BAST_PEMINJAMAN',
                        'doc_number' => $docNumber,
                        'doc_date' => Carbon::now('Asia/Makassar')->toDateString(),
                        'status' => 'ISSUED',
                        'issued_by' => $actor->id,
                        'issued_at' => Carbon::now('Asia/Makassar'),
                    ]);
                    break;

                case 'ACTIVE':
                    // Ubah status barang fisik menjadi ON_LOAN
                    foreach ($loan->items as $item) {
                        $asset = $item->asset;
                        if ($asset) {
                            $asset->status = 'ON_LOAN';
                            $asset->save();
                        }
                    }
                    break;

                case 'RETURN_REQUESTED':
                    // Pengembalian diajukan oleh peminjam
                    break;

                case 'RETURN_CHECKED':
                    // Update kondisi fisik tiap barang yang dikembalikan
                    if (isset($extraData['items_condition']) && is_array($extraData['items_condition'])) {
                        foreach ($extraData['items_condition'] as $itemId => $condData) {
                            $item = $loan->items()->where('id', $itemId)->first();
                            if ($item) {
                                $cond = $condData['condition_in'] ?? 'Baik';
                                $followUp = $condData['follow_up_note'] ?? null;

                                // BR-10: Jika Rusak / Hilang wajib isi tindak lanjut
                                if (in_array($cond, ['Rusak', 'Tidak Lengkap/Hilang', 'RUSAK', 'TIDAK_LENGKAP_HILANG']) && empty($followUp)) {
                                    throw new Exception("Kondisi {$cond} pada barang {$item->snap_item_name} mewajibkan pengisian tindak lanjut.");
                                }

                                $item->condition_in = $cond;
                                $item->follow_up_note = $followUp;
                                $item->save();
                            }
                        }
                    }

                    // Terbitkan BAST Pengembalian bernomor atomik
                    $docNumber = DocumentNumberService::generateNextNumber('BAST_PENGEMBALIAN');
                    Document::create([
                        'loan_request_id' => $loan->id,
                        'doc_type' => 'BAST_PENGEMBALIAN',
                        'doc_number' => $docNumber,
                        'doc_date' => Carbon::now('Asia/Makassar')->toDateString(),
                        'status' => 'ISSUED',
                        'issued_by' => $actor->id,
                        'issued_at' => Carbon::now('Asia/Makassar'),
                    ]);
                    break;

                case 'RETURNED':
                    // Kembalikan status barang menjadi AVAILABLE atau NEEDS_FOLLOW_UP
                    $loan->items()->update(['is_blocking' => false]);
                    foreach ($loan->items as $item) {
                        $asset = $item->asset;
                        if ($asset) {
                            if (in_array($item->condition_in, ['Rusak', 'Tidak Lengkap/Hilang', 'RUSAK', 'TIDAK_LENGKAP_HILANG'])) {
                                $asset->condition = 'RUSAK';
                                $asset->status = 'NEEDS_FOLLOW_UP';
                                $asset->notes = $item->follow_up_note;
                            } else {
                                $asset->condition = 'BAIK';
                                $asset->status = 'AVAILABLE';
                            }
                            $asset->save();
                        }
                    }
                    break;

                case 'CANCELLED':
                    $loan->items()->update(['is_blocking' => false]);
                    // Tandai dokumen yang pernah terbit menjadi VOID
                    $loan->documents()->update(['status' => 'VOID']);
                    break;
            }

            // 3. Update Status
            $loan->status = $toStatus;
            $loan->save();

            // 4. Record Loan Status History
            LoanStatusHistory::create([
                'loan_request_id' => $loan->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_id' => $actor->id,
                'note' => $note,
                'created_at' => Carbon::now('Asia/Makassar'),
            ]);

            // 5. Audit Log
            AuditLog::record(
                "TRANSITION_{$fromStatus}_TO_{$toStatus}",
                'LoanRequest',
                $loan->id,
                ['status' => $fromStatus],
                ['status' => $toStatus, 'note' => $note]
            );

            return [
                'ok' => true,
                'from' => $fromStatus,
                'to' => $toStatus,
                'message' => "Status berhasil diperbarui menjadi {$loan->status_meta['label']}.",
            ];
        });
    }

    /**
     * Validation rules for allowed status transitions and role permissions.
     */
    private static function validateTransition(
        LoanRequest $loan,
        string $from,
        string $to,
        User $actor,
        ?string $note
    ): void {
        // BR-08: Pemohon tidak boleh menelaah atau menyetujui pengajuannya sendiri
        $isSelf = ($actor->employee_id && $actor->employee_id === $loan->borrower_id);

        $allowed = [
            'DRAFT' => ['SUBMITTED', 'CANCELLED'],
            'SUBMITTED' => ['NEEDS_REVISION', 'UNDER_APPROVAL', 'CANCELLED'],
            'NEEDS_REVISION' => ['SUBMITTED', 'CANCELLED'],
            'UNDER_APPROVAL' => ['APPROVED', 'NEEDS_REVISION', 'REJECTED', 'CANCELLED'],
            'APPROVED' => ['BAST_ISSUED', 'CANCELLED'],
            'BAST_ISSUED' => ['ACTIVE', 'CANCELLED'],
            'ACTIVE' => ['RETURN_REQUESTED'],
            'RETURN_REQUESTED' => ['RETURN_CHECKED'],
            'RETURN_CHECKED' => ['RETURNED'],
        ];

        if (!isset($allowed[$from]) || !in_array($to, $allowed[$from])) {
            throw new Exception("Transisi dari status {$from} ke {$to} tidak diizinkan oleh SOP.");
        }

        // Role authorization check
        switch ($to) {
            case 'SUBMITTED':
                if (!$actor->isPeminjam() && !$actor->isPengelola() && !$actor->isAdmin()) {
                    throw new Exception('Anda tidak memiliki hak untuk mengajukan peminjaman.');
                }
                break;

            case 'NEEDS_REVISION':
            case 'UNDER_APPROVAL':
                if (!$actor->isPengelola() && !$actor->isAdmin() && !($from === 'UNDER_APPROVAL' && $actor->isKetuaTim())) {
                    throw new Exception('Hanya Pengelola BMN atau Ketua Tim yang berhak melakukan penelaahan.');
                }
                if ($isSelf && !$actor->isAdmin()) {
                    throw new Exception('Pelanggaran BR-08: Anda tidak dapat menelaah pengajuan peminjaman milik Anda sendiri.');
                }
                break;

            case 'APPROVED':
            case 'REJECTED':
                if (!$actor->isKetuaTim() && !$actor->isAdmin()) {
                    throw new Exception('Hanya Ketua Tim Keuangan dan Aset yang berhak menyetujui/menolak pengajuan.');
                }
                if ($isSelf && !$actor->isAdmin()) {
                    throw new Exception('Pelanggaran BR-08: Anda tidak dapat menyetujui pengajuan peminjaman milik Anda sendiri.');
                }
                break;

            case 'BAST_ISSUED':
            case 'ACTIVE':
            case 'RETURN_CHECKED':
            case 'RETURNED':
                if (!$actor->isPengelola() && !$actor->isAdmin()) {
                    throw new Exception('Hanya Pengelola BMN yang berhak memproses serah terima dan pengembalian BMN.');
                }
                break;

            case 'CANCELLED':
                if (!$isSelf && !$actor->isPengelola() && !$actor->isAdmin()) {
                    throw new Exception('Anda tidak memiliki izin membatalkan pengajuan ini.');
                }
                break;
        }
    }

    /**
     * Check overlapping loans for requested assets during the same period (BR-02).
     */
    public static function checkConflict(LoanRequest $loan): void
    {
        $startDate = $loan->start_date->toDateString();
        $endDate = $loan->end_date->toDateString();
        $assetIds = $loan->items()->pluck('asset_id')->toArray();

        // Cari item peminjaman lain yang memegang reservasi (is_blocking = true) dan periodenya beririsan
        $conflicts = DB::table('loan_items')
            ->join('loan_requests', 'loan_items.loan_request_id', '=', 'loan_requests.id')
            ->join('assets', 'loan_items.asset_id', '=', 'assets.id')
            ->whereIn('loan_items.asset_id', $assetIds)
            ->where('loan_items.loan_request_id', '!=', $loan->id)
            ->where('loan_items.is_blocking', true)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('loan_requests.start_date', [$startDate, $endDate])
                    ->orWhereBetween('loan_requests.end_date', [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->where('loan_requests.start_date', '<=', $startDate)
                            ->where('loan_requests.end_date', '>=', $endDate);
                    });
            })
            ->select('assets.item_name', 'assets.brand', 'assets.model', 'loan_requests.start_date', 'loan_requests.end_date')
            ->get();

        if ($conflicts->isNotEmpty()) {
            $conflictDetails = $conflicts->map(function ($c) {
                return "{$c->item_name} ({$c->brand} {$c->model}) sedang dipinjam/diajukan pada periode {$c->start_date} s.d. {$c->end_date}";
            })->implode('; ');

            throw new Exception("Bentrok jadwal peminjaman (BR-02): {$conflictDetails}.");
        }
    }
}

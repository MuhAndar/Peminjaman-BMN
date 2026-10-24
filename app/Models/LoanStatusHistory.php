<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'loan_status_history';

    protected $fillable = [
        'loan_request_id',
        'from_status',
        'to_status',
        'actor_id',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function loanRequest()
    {
        return $this->belongsTo(LoanRequest::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function statusLabel(?string $status): string
    {
        if (empty($status)) {
            return 'Draf Pengajuan';
        }

        $labels = [
            'DRAFT' => 'Draf Pengajuan',
            'SUBMITTED' => 'Menunggu Telaah Surat Peminjaman',
            'NEEDS_REVISION' => 'Perlu Perbaikan Dokumen Peminjaman',
            'UNDER_APPROVAL' => 'Menunggu Persetujuan Surat Peminjaman',
            'APPROVED' => 'Surat Peminjaman Disetujui',
            'BAST_ISSUED' => 'Draf BAST Peminjaman Diterbitkan',
            'ACTIVE' => 'Sedang Dipinjam (BAST Peminjaman Selesai)',
            'RETURN_REQUESTED' => 'Pengembalian Diajukan (Menunggu Pemeriksaan Fisik)',
            'RETURN_CHECKED' => 'Pemeriksaan Fisik Selesai (Draf BAST Pengembalian)',
            'RETURNED' => 'Selesai Dikembalikan (BAST Pengembalian Selesai)',
            'REJECTED' => 'Pengajuan Surat Peminjaman Ditolak',
            'CANCELLED' => 'Pengajuan Dibatalkan',
        ];

        return $labels[$status] ?? str_replace('_', ' ', $status);
    }

    public function getFromStatusLabelAttribute(): string
    {
        return self::statusLabel($this->from_status);
    }

    public function getToStatusLabelAttribute(): string
    {
        return self::statusLabel($this->to_status);
    }

    /**
     * Label deskripsi kontekstual untuk catatan/komentar tindak lanjut per status.
     */
    public function getNoteContextLabelAttribute(): string
    {
        $contextLabels = [
            'SUBMITTED' => 'Catatan Pengajuan Peminjam',
            'UNDER_APPROVAL' => 'Catatan Hasil Telaah Pengelola BMN',
            'APPROVED' => 'Catatan Disposisi / Persetujuan Ketua Tim',
            'NEEDS_REVISION' => 'Catatan Permintaan Revisi Dokumen',
            'REJECTED' => 'Alasan Penolakan Pengajuan',
            'BAST_ISSUED' => 'Catatan Penerbitan Draf BAST Peminjaman',
            'ACTIVE' => 'Catatan Serah Terima Fisik BMN & BAST Peminjaman',
            'RETURN_REQUESTED' => 'Catatan Pengajuan Pengembalian oleh Peminjam',
            'RETURN_CHECKED' => 'Catatan Hasil Pemeriksaan Fisik Pengelola BMN',
            'RETURNED' => 'Catatan Akhir Serah Terima Pengembalian BMN',
            'CANCELLED' => 'Alasan Pembatalan Pengajuan',
        ];

        return $contextLabels[$this->to_status] ?? 'Keterangan / Catatan Tindak Lanjut';
    }
}

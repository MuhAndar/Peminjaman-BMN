<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'borrower_id',
        'loan_type',
        'start_date',
        'end_date',
        'status',
        'snap_full_name',
        'snap_nip',
        'snap_rank_grade',
        'snap_position',
        'snap_home_address',
        'review_note',
        'decision_note',
        'is_legacy',
        'legacy_seq',
        'submitted_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'submitted_at' => 'datetime',
        'is_legacy' => 'boolean',
        'legacy_seq' => 'integer',
    ];

    public function borrower()
    {
        return $this->belongsTo(Employee::class, 'borrower_id');
    }

    public function items()
    {
        return $this->hasMany(LoanItem::class);
    }

    public function history()
    {
        return $this->hasMany(LoanStatusHistory::class)->orderBy('created_at', 'asc');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['ACTIVE', 'RETURN_REQUESTED'])) {
            $today = Carbon::now('Asia/Makassar')->toDateString();
            return $this->end_date->toDateString() < $today;
        }
        return false;
    }

    public function getStatusMetaAttribute(): array
    {
        $status = $this->status;
        $isOverdue = $this->isOverdue();

        $map = [
            'DRAFT' => [
                'label' => 'Draf',
                'bg' => 'bg-slate-100 text-slate-800 border-slate-300',
                'badge' => 'neutral',
                'icon' => 'file-pen',
                'step' => 0,
            ],
            'SUBMITTED' => [
                'label' => 'Menunggu Telaah',
                'bg' => 'bg-blue-50 text-blue-700 border-blue-200',
                'badge' => 'blue',
                'icon' => 'inbox',
                'step' => 1,
            ],
            'NEEDS_REVISION' => [
                'label' => 'Perlu Perbaikan',
                'bg' => 'bg-amber-50 text-amber-700 border-amber-300',
                'badge' => 'warning',
                'icon' => 'undo-2',
                'step' => 1,
            ],
            'UNDER_APPROVAL' => [
                'label' => 'Menunggu Persetujuan',
                'bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'badge' => 'indigo',
                'icon' => 'hourglass',
                'step' => 2,
            ],
            'APPROVED' => [
                'label' => 'Disetujui',
                'bg' => 'bg-teal-50 text-teal-700 border-teal-200',
                'badge' => 'teal',
                'icon' => 'badge-check',
                'step' => 3,
            ],
            'BAST_ISSUED' => [
                'label' => 'Menunggu Tanda Tangan BAST',
                'bg' => 'bg-cyan-50 text-cyan-700 border-cyan-300',
                'badge' => 'cyan',
                'icon' => 'file-signature',
                'step' => 4,
            ],
            'ACTIVE' => [
                'label' => $isOverdue ? 'Terlambat (Aktif)' : 'Sedang Dipinjam',
                'bg' => $isOverdue ? 'bg-red-50 text-red-700 border-red-300' : 'bg-emerald-50 text-emerald-700 border-emerald-300',
                'badge' => $isOverdue ? 'danger' : 'success',
                'icon' => $isOverdue ? 'alarm-clock' : 'package-check',
                'step' => 5,
            ],
            'RETURN_REQUESTED' => [
                'label' => 'Pengembalian Diajukan',
                'bg' => 'bg-blue-50 text-blue-700 border-blue-200',
                'badge' => 'blue',
                'icon' => 'package-open',
                'step' => 6,
            ],
            'RETURN_CHECKED' => [
                'label' => 'Pengecekan Selesai',
                'bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'badge' => 'indigo',
                'icon' => 'clipboard-check',
                'step' => 6,
            ],
            'RETURNED' => [
                'label' => 'Selesai',
                'bg' => 'bg-green-50 text-green-700 border-green-300',
                'badge' => 'success',
                'icon' => 'check-circle-2',
                'step' => 7,
            ],
            'REJECTED' => [
                'label' => 'Ditolak',
                'bg' => 'bg-red-50 text-red-700 border-red-300',
                'badge' => 'danger',
                'icon' => 'x-circle',
                'step' => -1,
            ],
            'CANCELLED' => [
                'label' => 'Dibatalkan',
                'bg' => 'bg-slate-100 text-slate-600 border-slate-300',
                'badge' => 'neutral',
                'icon' => 'ban',
                'step' => -1,
            ],
        ];

        return $map[$status] ?? [
            'label' => $status,
            'bg' => 'bg-slate-100 text-slate-700 border-slate-200',
            'badge' => 'neutral',
            'icon' => 'circle',
            'step' => 0,
        ];
    }
}

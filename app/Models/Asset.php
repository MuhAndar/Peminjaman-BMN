<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category_code',
        'jenis_bmn',
        'kode_satker',
        'nama_satker',
        'bmn_code',
        'nup',
        'item_name',
        'status_bmn',
        'brand',
        'model',
        'condition',
        'umur_aset',
        'intra_extra',
        'henti_guna',
        'no_dokumen',
        'no_bpkp',
        'plate_number',
        'tgl_perolehan',
        'acquisition_year',
        'acquisition_value',
        'nilai_buku',
        'status_penggunaan',
        'no_psp',
        'tgl_psp',
        'alamat',
        'lokasi_ruang',
        'no_stnk',
        'nama_pengguna',
        'status',
        'notes',
        'extra_attributes',
    ];

    protected $casts = [
        'acquisition_year' => 'integer',
        'acquisition_value' => 'decimal:0',
        'nilai_buku' => 'decimal:0',
        'nup' => 'integer',
        'tgl_perolehan' => 'date',
        'tgl_psp' => 'date',
        'extra_attributes' => 'array',
    ];

    public function getFormattedNilaiBukuAttribute(): string
    {
        return $this->nilai_buku ? 'Rp ' . number_format($this->nilai_buku, 0, ',', '.') : '-';
    }

    public static function detectCategory(?string $jenis, ?string $kodeBarang, ?string $namaBarang): string
    {
        $kode = preg_replace('/[^0-9]/', '', (string)$kodeBarang);
        $nama = strtolower(trim((string)$namaBarang));
        $jenis = strtoupper(trim((string)$jenis));

        if ($kode === '3100102002' || str_contains($nama, 'lap top') || str_contains($nama, 'laptop') || str_contains($nama, 'notebook')) {
            return 'LAPTOP';
        }
        if ($kode === '3060201004' || str_contains($nama, 'telepon seluler') || str_contains($nama, 'handphone') || str_contains($nama, 'hp')) {
            return 'MOBILE_PHONE';
        }
        if ($kode === '3020102003' || str_contains($nama, 'mobil')) {
            return 'CAR';
        }
        if ($kode === '3020104001' || $kode === '3020104002' || str_contains($nama, 'sepeda motor') || str_contains($nama, 'scooter') || str_contains($nama, 'motor')) {
            return 'MOTORCYCLE';
        }

        return match ($jenis) {
            'MESIN PERALATAN KHUSUS TIK' => 'TIK',
            'MESIN PERALATAN NON TIK' => 'NON_TIK',
            'ALAT ANGKUTAN BERMOTOR' => 'CAR',
            'ALAT BESAR' => 'ALAT_BESAR',
            'ASET TETAP LAINNYA' => 'ASET_LAINNYA',
            'TANAH' => 'TANAH',
            'BANGUNAN DAN GEDUNG' => 'GEDUNG',
            'RUMAH NEGARA' => 'RUMAH_NEGARA',
            default => 'NON_TIK',
        };
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_code', 'code');
    }

    public function loanItems()
    {
        return $this->hasMany(LoanItem::class);
    }

    public function getFormattedValueAttribute(): string
    {
        return 'Rp ' . number_format($this->acquisition_value, 0, ',', '.');
    }

    public function getBrandModelCombinedAttribute(): string
    {
        return trim(($this->brand ?? '') . ' ' . ($this->model ?? ''));
    }

    public function activeLoanItem()
    {
        return $this->hasOne(LoanItem::class)
            ->whereHas('loanRequest', function ($q) {
                $q->whereIn('status', [
                    'SUBMITTED',
                    'VERIFIED',
                    'UNDER_APPROVAL',
                    'NEEDS_REVISION',
                    'APPROVED',
                    'BAST_ISSUED',
                    'ACTIVE',
                    'RETURN_REQUESTED',
                    'RETURN_CHECKED',
                ]);
            })
            ->latest('created_at');
    }

    protected ?LoanRequest $memoizedPendingLoan = null;
    protected bool $pendingLoanLoaded = false;
    protected ?LoanRequest $memoizedApprovedLoan = null;
    protected bool $approvedLoanLoaded = false;
    protected ?array $memoizedOperationalStatus = null;

    public function resetOperationalStatusCache(): void
    {
        $this->memoizedPendingLoan = null;
        $this->pendingLoanLoaded = false;
        $this->memoizedApprovedLoan = null;
        $this->approvedLoanLoaded = false;
        $this->memoizedOperationalStatus = null;
    }

    public function getPendingLoanAttribute(): ?LoanRequest
    {
        if ($this->pendingLoanLoaded) {
            return $this->memoizedPendingLoan;
        }

        $this->memoizedPendingLoan = LoanRequest::whereHas('items', function ($q) {
            $q->where('asset_id', $this->id);
        })->whereIn('status', [
            'DRAFT',
            'SUBMITTED',
            'UNDER_APPROVAL',
            'NEEDS_REVISION',
        ])->latest('created_at')->first();
        $this->pendingLoanLoaded = true;

        return $this->memoizedPendingLoan;
    }

    public function getApprovedLoanAttribute(): ?LoanRequest
    {
        if ($this->approvedLoanLoaded) {
            return $this->memoizedApprovedLoan;
        }

        $this->memoizedApprovedLoan = LoanRequest::whereHas('items', function ($q) {
            $q->where('asset_id', $this->id);
        })->whereIn('status', [
            'APPROVED',
            'BAST_ISSUED',
            'ACTIVE',
            'RETURN_REQUESTED',
            'RETURN_CHECKED',
        ])->latest('created_at')->first();
        $this->approvedLoanLoaded = true;

        return $this->memoizedApprovedLoan;
    }

    public function getActiveLoanAttribute(): ?LoanRequest
    {
        return $this->approved_loan ?: $this->pending_loan;
    }

    /**
     * Operational Status Info:
     * - 'PENDING_APPROVAL': "Dalam proses pengajuan" (Surat Peminjaman belum disetujui)
     * - 'ON_LOAN': "Sedang Dipinjam" (Surat Peminjaman telah disetujui & aktif)
     * - 'MAINTENANCE': "Perlu Perbaikan"
     * - 'AVAILABLE': "Tersedia"
     */
    public function getOperationalStatusAttribute(): array
    {
        if ($this->memoizedOperationalStatus !== null) {
            return $this->memoizedOperationalStatus;
        }

        if ($this->condition && in_array(strtoupper($this->condition), ['RUSAK', 'TIDAK_LENGKAP_HILANG', 'NEEDS_FOLLOW_UP']) || $this->status === 'NEEDS_FOLLOW_UP') {
            return $this->memoizedOperationalStatus = [
                'code' => 'MAINTENANCE',
                'label' => 'Perlu Perbaikan',
                'badge_class' => 'bg-rose-100 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/80',
                'is_selectable' => false,
                'loan' => null,
            ];
        }

        $approvedLoan = $this->approved_loan;
        if ($approvedLoan || $this->status === 'ON_LOAN') {
            return $this->memoizedOperationalStatus = [
                'code' => 'ON_LOAN',
                'label' => 'Sedang Dipinjam',
                'badge_class' => 'bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800/80',
                'is_selectable' => false,
                'loan' => $approvedLoan,
            ];
        }

        $pendingLoan = $this->pending_loan;
        if ($pendingLoan) {
            return $this->memoizedOperationalStatus = [
                'code' => 'PENDING_APPROVAL',
                'label' => 'Dalam proses pengajuan',
                'badge_class' => 'bg-blue-100 dark:bg-blue-950/70 text-[#0B63B5] dark:text-blue-300 border-blue-200 dark:border-blue-800/80',
                'is_selectable' => false,
                'loan' => $pendingLoan,
            ];
        }

        return $this->memoizedOperationalStatus = [
            'code' => 'AVAILABLE',
            'label' => 'Tersedia',
            'badge_class' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200/80 dark:border-emerald-800/80',
            'is_selectable' => true,
            'loan' => null,
        ];
    }

    public function getIsCurrentlyLoanedAttribute(): bool
    {
        return in_array($this->operational_status['code'], ['ON_LOAN', 'PENDING_APPROVAL']);
    }

    public function isAvailable(): bool
    {
        return $this->operational_status['is_selectable'];
    }
}

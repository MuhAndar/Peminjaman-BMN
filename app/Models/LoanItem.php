<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'loan_request_id',
        'asset_id',
        'purpose',
        'condition_out',
        'condition_in',
        'follow_up_note',
        'snap_item_name',
        'snap_brand_model',
        'snap_nup',
        'snap_year',
        'snap_value',
        'is_blocking',
    ];

    protected $casts = [
        'snap_nup' => 'integer',
        'snap_year' => 'integer',
        'snap_value' => 'decimal:0',
        'is_blocking' => 'boolean',
    ];

    public function loanRequest()
    {
        return $this->belongsTo(LoanRequest::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function getFormattedSnapValueAttribute(): string
    {
        return 'Rp ' . number_format($this->snap_value ?? 0, 0, ',', '.');
    }
}

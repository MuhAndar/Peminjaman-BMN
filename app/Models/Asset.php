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
        'item_name',
        'brand',
        'model',
        'bmn_code',
        'nup',
        'plate_number',
        'acquisition_year',
        'acquisition_value',
        'condition',
        'status',
        'notes',
    ];

    protected $casts = [
        'acquisition_year' => 'integer',
        'acquisition_value' => 'decimal:0',
        'nup' => 'integer',
    ];

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

    public function isAvailable(): bool
    {
        return $this->status === 'AVAILABLE' && $this->condition === 'BAIK';
    }
}

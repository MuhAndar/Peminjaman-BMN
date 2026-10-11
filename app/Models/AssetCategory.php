<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'label',
        'default_loan_type',
        'requires_schedule_check',
    ];

    protected $casts = [
        'requires_schedule_check' => 'boolean',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'category_code', 'code');
    }
}

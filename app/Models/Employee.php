<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nip',
        'full_name',
        'rank_grade',
        'position',
        'home_address',
        'employment_status',
        'email',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function loanRequests()
    {
        return $this->hasMany(LoanRequest::class, 'borrower_id');
    }

    public function getFormattedNipAttribute(): string
    {
        if (empty($this->nip)) {
            return '-';
        }
        $digits = preg_replace('/\D/', '', $this->nip);
        if (strlen($digits) === 18) {
            // Format 8-6-1-3: 19820608 200901 2 001
            return substr($digits, 0, 8) . ' ' . substr($digits, 8, 6) . ' ' . substr($digits, 14, 1) . ' ' . substr($digits, 15, 3);
        }
        return $this->nip;
    }
}

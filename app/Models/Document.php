<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'loan_request_id',
        'doc_type',
        'doc_number',
        'doc_date',
        'status',
        'generated_file_key',
        'signed_file_key',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'doc_date' => 'date',
        'issued_at' => 'datetime',
    ];

    public function loanRequest()
    {
        return $this->belongsTo(LoanRequest::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}

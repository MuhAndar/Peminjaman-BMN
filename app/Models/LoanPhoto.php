<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPhoto extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'loan_request_id',
        'stage',
        'file_path',
        'original_name',
        'caption',
        'uploaded_by',
    ];

    public function loanRequest()
    {
        return $this->belongsTo(LoanRequest::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

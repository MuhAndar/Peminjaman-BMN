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
}

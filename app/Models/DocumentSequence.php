<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'document_sequences';

    protected $fillable = [
        'doc_type',
        'year',
        'last_value',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'audit_logs';

    protected $fillable = [
        'actor_id',
        'action',
        'entity',
        'entity_id',
        'before',
        'after',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function loanRequest()
    {
        return $this->belongsTo(LoanRequest::class, 'entity_id');
    }

    public static function record(string $action, string $entity, ?string $entityId = null, ?array $before = null, ?array $after = null): self
    {
        return static::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'before' => $before,
            'after' => $after,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}

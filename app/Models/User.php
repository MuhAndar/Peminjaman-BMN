<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isPeminjam(): bool
    {
        return $this->role === 'PEMINJAM';
    }

    public function isPengelola(): bool
    {
        return $this->role === 'PENGELOLA_BMN' || $this->role === 'ADMIN';
    }

    public function isKetuaTim(): bool
    {
        return $this->role === 'KETUA_TIM_ASET' || $this->role === 'ADMIN';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles) || $this->role === 'ADMIN';
        }
        return $this->role === $roles || $this->role === 'ADMIN';
    }
}

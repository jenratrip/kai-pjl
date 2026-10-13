<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nip',
    'name',
    'email',
    'phone',
    'photo',
    'role_id',
    'password',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function catatanDinas(): HasMany
    {
        return $this->hasMany(CatatanDinas::class);
    }

    /**
     * Seluruh penugasan dinas milik user.
     */
    public function dinas(): HasMany
    {
        return $this->hasMany(Dinas::class, 'user_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }
}

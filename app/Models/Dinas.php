<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dinas extends Model
{
    protected $table = 'dinas';

    protected $fillable = [
        'user_id',
        'jpl_id',
        'shift_id',
        'tanggal_dinas',
        'jam_mulai',
        'jam_selesai',
        'status',
        'is_pengganti',
        'user_digantikan_id',
    ];

    protected $casts = [
        'tanggal_dinas' => 'date',
        'jam_mulai' => 'datetime',
        'jam_selesai' => 'datetime',
        'is_pengganti' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jpl(): BelongsTo
    {
        return $this->belongsTo(Jpl::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function userDigantikan(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_digantikan_id'
        );
    }

    public function catatanDinas(): HasMany
    {
        return $this->hasMany(CatatanDinas::class);
    }
}
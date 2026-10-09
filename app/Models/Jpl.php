<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jpl extends Model
{
    protected $table = 'jpl';

    protected $fillable = [
        'kode_jpl',
        'nama_jpl',
        'resort_id',
        'lokasi',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function resort(): BelongsTo
    {
        return $this->belongsTo(Resort::class);
    }

    public function dinas(): HasMany
    {
        return $this->hasMany(Dinas::class);
    }

    public function jadwalKa(): HasMany
    {
        return $this->hasMany(JadwalKa::class);
    }

    public function inventaris(): HasMany
    {
        return $this->hasMany(Inventaris::class);
    }
}
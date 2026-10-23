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
        'antara',
        'km_hm',
        'lokasi',
        'kota_kabupaten',
        'provinsi',
        'jenis_jalur_ka',
        'konst_jalan',
        'titik_koordinat',
        'lebar_jln',
        'status_penjagaan',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function resort(): BelongsTo
    {
        return $this->belongsTo(Resort::class, 'resort_id');
    }

    public function dinas(): HasMany
    {
        return $this->hasMany(Dinas::class, 'jpl_id');
    }

    public function jadwalKa(): HasMany
    {
        return $this->hasMany(JadwalKa::class, 'jpl_id');
    }

    public function inventaris(): HasMany
    {
        return $this->hasMany(Inventaris::class, 'jpl_id');
    }
}

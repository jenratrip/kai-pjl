<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealisasiKa extends Model
{
    protected $table = 'realisasi_ka';

    protected $fillable = [
        'dinas_id',
        'jadwal_ka_id',
        'kereta_api_id',
        'status_lewat',
        'jam_aktual',
        'status_s21',
        'status_genta',
        'status_pintu_perlintasan',
        'status_s35',
        'is_manual',
        'catatan',
        'diedit_terakhir_pada',
        'hulu',
        'hilir',
        'relasi',
    ];

    protected $casts = [
        'is_manual' => 'boolean',
        'diedit_terakhir_pada' => 'datetime',
    ];

    public function dinas(): BelongsTo
    {
        return $this->belongsTo(Dinas::class, 'dinas_id');
    }

    public function jadwalKa(): BelongsTo
    {
        return $this->belongsTo(JadwalKa::class, 'jadwal_ka_id');
    }

    public function keretaApi(): BelongsTo
    {
        return $this->belongsTo(KeretaApi::class, 'kereta_api_id');
    }
}
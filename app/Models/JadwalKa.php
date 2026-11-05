<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalKa extends Model
{
    protected $table = 'jadwal_ka';

    protected $fillable = [
        'kereta_api_id',
        'dari',
        'ke',
        'jpl_id',
        'jam_lewat',
        'hari_berlaku',
        'tanggal_berlaku',
        'keterangan',
        'is_active',
        'batch_import',
        'jadwal_ka_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'hari_berlaku' => 'array',
            'tanggal_berlaku' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function keretaApi(): BelongsTo
    {
        return $this->belongsTo(KeretaApi::class, 'kereta_api_id');
    }

    public function jpl(): BelongsTo
    {
        return $this->belongsTo(Jpl::class, 'jpl_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(JadwalKaBatch::class, 'jadwal_ka_batch_id');
    }

    public function realisasiKa(): HasMany
    {
        return $this->hasMany(RealisasiKa::class, 'jadwal_ka_id');
    }
}

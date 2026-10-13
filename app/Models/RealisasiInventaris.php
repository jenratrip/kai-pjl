<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealisasiInventaris extends Model
{
    protected $table = 'realisasi_inventaris';

    protected $fillable = [
        'dinas_id',
        'inventaris_id',
        'status',
        'kondisi',
        'catatan',
        'diperiksa_pada',
        'diperiksa_oleh',
        'jumlah_aktual',
    ];

    protected function casts(): array
    {
        return [
            'diperiksa_pada' => 'datetime',
            'jumlah_aktual' => 'integer',
        ];
    }

    public function dinas(): BelongsTo
    {
        return $this->belongsTo(
            Dinas::class,
            'dinas_id'
        );
    }

    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(
            Inventaris::class,
            'inventaris_id'
        );
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'diperiksa_oleh'
        );
    }
}

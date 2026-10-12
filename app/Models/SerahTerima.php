<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerahTerima extends Model
{
    protected $table = 'serah_terima';

    protected $fillable = [
        'dinas_id',
        'dinas_penerima_id',
        'waktu_serah_terima',
        'ringkasan',
        'tanda_tangan_penyerah',
        'tanda_tangan_penerima',
        'status',
    ];

    protected $casts = [
        'waktu_serah_terima' => 'datetime',
        'ringkasan' => 'array',
    ];

    /**
     * Dinas yang menyerahkan.
     */
    public function dinasPenyerah(): BelongsTo
    {
        return $this->belongsTo(
            Dinas::class,
            'dinas_id'
        );
    }

    /**
     * Dinas yang menerima.
     */
    public function dinasPenerima(): BelongsTo
    {
        return $this->belongsTo(
            Dinas::class,
            'dinas_penerima_id'
        );
    }
}
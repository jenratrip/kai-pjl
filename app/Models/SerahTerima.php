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
        'penerima_user_id',
        'waktu_serah_terima',
        'ringkasan',
        'tanda_tangan_penyerah',
        'tanda_tangan_penerima',
        'status',

        // Review Admin, terpisah dari status tanda tangan.
        'status_review',
        'catatan_revisi',
        'ditinjau_oleh',
        'ditinjau_pada',
    ];

    protected function casts(): array
    {
        return [
            'waktu_serah_terima' => 'datetime',
            'ringkasan' => 'array',
            'ditinjau_pada' => 'datetime',
        ];
    }

    public function dinasPenyerah(): BelongsTo
    {
        return $this->belongsTo(Dinas::class, 'dinas_id');
    }

    public function dinas(): BelongsTo
    {
        return $this->belongsTo(Dinas::class, 'dinas_id');
    }

    public function dinasPenerima(): BelongsTo
    {
        return $this->belongsTo(Dinas::class, 'dinas_penerima_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penerima_user_id');
    }

    public function ditinjauOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }
}

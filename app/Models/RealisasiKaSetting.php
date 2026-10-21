<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealisasiKaSetting extends Model
{
    protected $table = 'realisasi_ka_settings';

    protected $fillable = [
        'toleransi_keterlambatan_menit',
        'peringatan_keterlambatan_tinggi_menit',
        'jarak_minimum_antar_ka_menit',
        'batas_penutupan_palang_menit',
        'warning_keterlambatan_aktif',
        'warning_keterlambatan_tinggi_aktif',
        'warning_antar_ka_aktif',
        'warning_penutupan_palang_aktif',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'toleransi_keterlambatan_menit' => 'integer',
            'peringatan_keterlambatan_tinggi_menit' => 'integer',
            'jarak_minimum_antar_ka_menit' => 'integer',
            'batas_penutupan_palang_menit' => 'integer',
            'warning_keterlambatan_aktif' => 'boolean',
            'warning_keterlambatan_tinggi_aktif' => 'boolean',
            'warning_antar_ka_aktif' => 'boolean',
            'warning_penutupan_palang_aktif' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

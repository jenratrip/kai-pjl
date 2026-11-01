<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealisasiKaSetting extends Model
{
    protected $table = 'realisasi_ka_settings';

    /**
     * Kolom yang dapat diisi melalui Eloquent.
     */
    protected $fillable = [

        // ==============================================
        // 1. PERINGATAN KETERLAMBATAN KA
        // ==============================================

        'toleransi_keterlambatan_menit',
        'peringatan_keterlambatan_tinggi_menit',
        'warning_keterlambatan_aktif',
        'warning_keterlambatan_tinggi_aktif',

        // ==============================================
        // 2. JARAK ANTAR KA
        // ==============================================

        'jarak_minimum_antar_ka_menit',
        'warning_antar_ka_aktif',

        // ==============================================
        // 3. DURASI PENUTUPAN PALANG
        // ==============================================

        'batas_penutupan_palang_menit',
        'warning_penutupan_palang_aktif',

        // ==============================================
        // 4. KA LEBIH AWAL DARI JADWAL
        // ==============================================

        'batas_lebih_awal_menit',
        'warning_lebih_awal_aktif',

        // ==============================================
        // 5. AWAS 2 ARAH
        // ==============================================

        'batas_awas_2_arah_menit',
        'warning_awas_2_arah_aktif',

        // ==============================================
        // 6. INFORMASI ADMIN
        // ==============================================

        'updated_by',
    ];

    /**
     * Konversi tipe data atribut.
     */
    protected function casts(): array
    {
        return [

            // Ambang keterlambatan
            'toleransi_keterlambatan_menit' => 'integer',
            'peringatan_keterlambatan_tinggi_menit' => 'integer',

            // Jarak antar KA
            'jarak_minimum_antar_ka_menit' => 'integer',

            // Durasi penutupan palang
            'batas_penutupan_palang_menit' => 'integer',

            // KA lebih awal dari jadwal
            'batas_lebih_awal_menit' => 'integer',

            // AWAS 2 arah
            'batas_awas_2_arah_menit' => 'integer',

            // Sakelar peringatan
            'warning_keterlambatan_aktif' => 'boolean',
            'warning_keterlambatan_tinggi_aktif' => 'boolean',
            'warning_antar_ka_aktif' => 'boolean',
            'warning_penutupan_palang_aktif' => 'boolean',
            'warning_lebih_awal_aktif' => 'boolean',
            'warning_awas_2_arah_aktif' => 'boolean',
        ];
    }

    /**
     * Admin yang terakhir memperbarui pengaturan.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dinas extends Model
{
    use HasFactory;

    protected $table = 'dinas';

    protected $fillable = [
        'user_id',
        'jpl_id',
        'shift_id',
        'tanggal_dinas',
        'jam_mulai',
        'jam_selesai',
        'jadwal_ka_batch_id',
        'jadwal_ka_snapshot',
        'status',
        'is_pengganti',
        'user_digantikan_id',
    ];

    /**
     * Snapshot: format_version, snapshot_at, source ('batch' or
     * 'legacy'), jadwal_ka_batch_id, and a jadwal list containing
     * jadwal_ka_id, kereta_api_id, nomor_ka, nama_ka, jam_lewat, dari, ke,
     * and jpl_id. Null means not captured; an empty jadwal list means a
     * captured snapshot with no matching schedules.
     */
    protected $casts = [
        'tanggal_dinas' => 'date',
        'jam_mulai' => 'datetime',
        'jam_selesai' => 'datetime',
        'jadwal_ka_snapshot' => 'array',
        'is_pengganti' => 'boolean',
    ];

    /**
     * Petugas yang menjalankan dinas.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * JPL tempat petugas menjalankan dinas.
     */
    public function jpl(): BelongsTo
    {
        return $this->belongsTo(Jpl::class, 'jpl_id');
    }

    /**
     * Shift dinas.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Versi jadwal KA yang menjadi sumber Dinas ini, jika menggunakan batch.
     */
    public function jadwalKaBatch(): BelongsTo
    {
        return $this->belongsTo(JadwalKaBatch::class, 'jadwal_ka_batch_id');
    }

    /**
     * Petugas yang digantikan jika dinas pengganti.
     */
    public function userDigantikan(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_digantikan_id'
        );
    }

    /**
     * Catatan selama dinas.
     */
    public function catatanDinas(): HasMany
    {
        return $this->hasMany(
            CatatanDinas::class,
            'dinas_id'
        );
    }

    /**
     * Data serah terima yang berkaitan dengan dinas ini.
     */
    public function serahTerima(): HasMany
    {
        return $this->hasMany(
            SerahTerima::class,
            'dinas_id'
        );
    }

    /**
     * Hasil pemeriksaan inventaris selama dinas.
     */
    public function realisasiInventaris(): HasMany
    {
        return $this->hasMany(
            RealisasiInventaris::class,
            'dinas_id'
        );
    }

    public function realisasiKa(): HasMany
    {
        return $this->hasMany(RealisasiKa::class, 'dinas_id');
    }

    /**
     * Permintaan tukar jadwal di mana dinas ini adalah jadwal pemohon.
     */
    public function permintaanTukarAsalPemohon(): HasMany
    {
        return $this->hasMany(
            PermintaanTukarJadwal::class,
            'dinas_pemohon_id'
        );
    }

    /**
     * Permintaan tukar jadwal di mana dinas ini adalah jadwal tujuan.
     */
    public function permintaanTukarAsalTujuan(): HasMany
    {
        return $this->hasMany(
            PermintaanTukarJadwal::class,
            'dinas_tujuan_id'
        );
    }
}

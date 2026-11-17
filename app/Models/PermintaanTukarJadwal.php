<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanTukarJadwal extends Model
{
    protected $table = 'permintaan_tukar_jadwal';

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_GAGAL = 'gagal';

    /**
     * Status yang menandakan permintaan sudah selesai diproses.
     */
    public const FINAL_STATUSES = [
        self::STATUS_DITOLAK,
        self::STATUS_DIBATALKAN,
        self::STATUS_BERHASIL,
        self::STATUS_GAGAL,
    ];

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    |
    | Hanya data berikut yang boleh diisi melalui mass assignment.
    |
    | Status, timestamp proses, alasan penerima, dan alasan kegagalan
    | harus ditetapkan secara eksplisit oleh aplikasi.
    |
    */

    protected $fillable = [
        'dinas_pemohon_id',
        'dinas_tujuan_id',
        'pemohon_id',
        'penerima_id',
        'alasan_pemohon',
        'pemohon_name',
        'pemohon_nip',
        'penerima_name',
        'penerima_nip',
        'dinas_pemohon_snapshot',
        'dinas_tujuan_snapshot',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'diajukan_pada' => 'datetime',
            'dijawab_pada' => 'datetime',
            'selesai_pada' => 'datetime',

            'dinas_pemohon_snapshot' => 'array',
            'dinas_tujuan_snapshot' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Jadwal yang ingin ditukar oleh pemohon.
     */
    public function dinasPemohon(): BelongsTo
    {
        return $this->belongsTo(
            Dinas::class,
            'dinas_pemohon_id'
        );
    }

    /**
     * Jadwal tujuan milik penerima yang ingin ditukarkan.
     */
    public function dinasTujuan(): BelongsTo
    {
        return $this->belongsTo(
            Dinas::class,
            'dinas_tujuan_id'
        );
    }

    /**
     * Petugas yang mengajukan permintaan tukar jadwal.
     */
    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'pemohon_id'
        );
    }

    /**
     * Petugas yang menerima dan merespons permintaan tukar jadwal.
     */
    public function penerima(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'penerima_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Filter permintaan berdasarkan pemohon.
     */
    public function scopeMilikPemohon(
        Builder $query,
        int $userId
    ): Builder {
        return $query->where('pemohon_id', $userId);
    }

    /**
     * Filter permintaan berdasarkan penerima.
     */
    public function scopeMilikPenerima(
        Builder $query,
        int $userId
    ): Builder {
        return $query->where('penerima_id', $userId);
    }

    /**
     * Filter permintaan yang menunggu respons.
     */
    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_MENUNGGU);
    }

    /**
     * Filter permintaan yang ditolak.
     */
    public function scopeDitolak(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DITOLAK);
    }

    /**
     * Filter permintaan yang dibatalkan.
     */
    public function scopeDibatalkan(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIBATALKAN);
    }

    /**
     * Filter permintaan yang berhasil.
     */
    public function scopeBerhasil(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BERHASIL);
    }

    /**
     * Filter permintaan yang gagal.
     */
    public function scopeGagal(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_GAGAL);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Periksa apakah permintaan masih menunggu respons penerima.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }

    /**
     * Periksa apakah permintaan ditolak.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_DITOLAK;
    }

    /**
     * Periksa apakah permintaan dibatalkan oleh pemohon.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_DIBATALKAN;
    }

    /**
     * Periksa apakah pertukaran jadwal berhasil.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_BERHASIL;
    }

    /**
     * Periksa apakah pertukaran jadwal gagal.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_GAGAL;
    }

    /**
     * Periksa apakah permintaan sudah mencapai status akhir.
     */
    public function isFinalized(): bool
    {
        return in_array(
            $this->status,
            self::FINAL_STATUSES,
            true
        );
    }

    /**
     * Periksa apakah permintaan masih dapat dibatalkan pemohon.
     */
    public function canBeCancelled(): bool
    {
        return $this->isPending();
    }
}

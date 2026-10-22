<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerahTerima extends Model
{
    protected $table = 'serah_terima';

    /**
     * Status yang dianggap sudah disetujui, sehingga data terkunci.
     * Sesuai enum di migration: draft, menunggu_penerima, selesai.
     */
    public const STATUS_TERKUNCI = ['selesai'];

    protected $fillable = [
        'dinas_id',
        'dinas_penerima_id',
        'penerima_user_id',
        'waktu_serah_terima',
        'ringkasan',
        'tanda_tangan_penyerah',
        'tanda_tangan_penerima',
        'status',
    ];

    /**
     * Casting atribut model.
     */
    protected function casts(): array
    {
        return [
            'waktu_serah_terima' => 'datetime',
            'ringkasan' => 'array',
        ];
    }

    /**
     * Apakah data ini sudah disetujui dan tidak boleh diedit lagi.
     */
    public function isTerkunci(): bool
    {
        return in_array($this->status, self::STATUS_TERKUNCI, true);
    }

    /**
     * Batasi data pada serah terima dari dinas milik user tertentu.
     */
    public function scopeMilikUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas(
            'dinas',
            fn (Builder $dinas) => $dinas->where('user_id', $userId)
        );
    }

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
     * Alias relasi dinas penyerah.
     * Dipertahankan untuk kompatibilitas controller.
     */
    public function dinas(): BelongsTo
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

    /**
     * User admin/superadmin penerima serah terima (opsional).
     */
    public function penerima(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'penerima_user_id'
        );
    }
}
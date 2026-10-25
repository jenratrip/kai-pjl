<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerahTerima extends Model
{
    /**
     * Nama tabel database.
     */
    protected $table = 'serah_terima';

    /**
     * Status review yang membuat data terkunci.
     *
     * Status tanda tangan dan status review memiliki fungsi berbeda:
     * - status: draft, menunggu_penerima, selesai
     * - status_review: menunggu_review, disetujui, perlu_revisi
     *
     * Data hanya terkunci setelah disetujui admin/superadmin.
     */
    public const STATUS_TERKUNCI = [
        'disetujui',
    ];

    /**
     * Kolom yang dapat diisi melalui mass assignment.
     */
    protected $fillable = [
        'dinas_id',
        'dinas_penerima_id',
        'penerima_user_id',
        'waktu_serah_terima',
        'ringkasan',
        'tanda_tangan_penyerah',
        'tanda_tangan_penerima',
        'status',

        // Review oleh admin/superadmin.
        'status_review',
        'catatan_revisi',
        'ditinjau_oleh',
        'ditinjau_pada',
    ];

    /**
     * Konversi tipe data atribut model.
     */
    protected function casts(): array
    {
        return [
            'waktu_serah_terima' => 'datetime',
            'ringkasan' => 'array',
            'ditinjau_pada' => 'datetime',
        ];
    }

    /**
     * Memeriksa apakah serah terima sudah disetujui
     * dan tidak boleh diubah lagi oleh petugas.
     */
    public function isTerkunci(): bool
    {
        return in_array(
            $this->status_review,
            self::STATUS_TERKUNCI,
            true
        );
    }

    /**
     * Scope untuk mengambil serah terima yang berasal
     * dari dinas milik user tertentu.
     */
    public function scopeMilikUser(
        Builder $query,
        int $userId
    ): Builder {
        return $query->whereHas(
            'dinas',
            fn (Builder $dinas) => $dinas->where(
                'user_id',
                $userId
            )
        );
    }

    /**
     * Relasi ke dinas yang menyerahkan.
     */
    public function dinasPenyerah(): BelongsTo
    {
        return $this->belongsTo(
            Dinas::class,
            'dinas_id'
        );
    }

    /**
     * Relasi utama ke dinas.
     *
     * Dipertahankan untuk kompatibilitas dengan query
     * yang menggunakan relasi dinas.
     */
    public function dinas(): BelongsTo
    {
        return $this->belongsTo(
            Dinas::class,
            'dinas_id'
        );
    }

    /**
     * Relasi ke dinas penerima, jika tersedia.
     */
    public function dinasPenerima(): BelongsTo
    {
        return $this->belongsTo(
            Dinas::class,
            'dinas_penerima_id'
        );
    }

    /**
     * Relasi ke akun PJL yang ditunjuk sebagai penerima.
     */
    public function penerima(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'penerima_user_id'
        );
    }

    /**
     * Relasi ke admin/superadmin yang meninjau serah terima.
     */
    public function ditinjauOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ditinjau_oleh'
        );
    }
}

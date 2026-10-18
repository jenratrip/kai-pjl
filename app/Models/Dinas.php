<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dinas extends Model
{
    protected $table = 'dinas';

    protected $fillable = [
        'user_id',
        'jpl_id',
        'shift_id',
        'tanggal_dinas',
        'jam_mulai',
        'jam_selesai',
        'status',
        'is_pengganti',
        'user_digantikan_id',
    ];

    protected $casts = [
        'tanggal_dinas' => 'date',
        'jam_mulai' => 'datetime',
        'jam_selesai' => 'datetime',
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
}

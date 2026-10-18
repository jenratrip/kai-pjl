<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventaris extends Model
{
    protected $table = 'inventaris';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori',
        'lokasi',
        'jpl_id',
        'jumlah',
        'keterangan',
        'is_active',
        'kondisi_terakhir',
        'jumlah_aktual_terakhir',
        'status_tinjauan',
        'catatan_masalah',
        'dilaporkan_pada',
        'dilaporkan_oleh',
        'catatan_tinjauan',
        'ditinjau_oleh',
        'ditinjau_pada',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'jumlah_aktual_terakhir' => 'integer',
            'is_active' => 'boolean',
            'dilaporkan_pada' => 'datetime',
            'ditinjau_pada' => 'datetime',
        ];
    }

    public function jpl(): BelongsTo
    {
        return $this->belongsTo(Jpl::class, 'jpl_id');
    }

    public function realisasiInventaris(): HasMany
    {
        return $this->hasMany(
            RealisasiInventaris::class,
            'inventaris_id'
        );
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'dilaporkan_oleh'
        );
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ditinjau_oleh'
        );
    }
}

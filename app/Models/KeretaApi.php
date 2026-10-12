<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeretaApi extends Model
{
    protected $table = 'kereta_api';

    protected $fillable = [
        'nomor_ka',
        'nama_ka',
        'jenis',
    ];

    public function jadwalKa(): HasMany
    {
        return $this->hasMany(JadwalKa::class, 'kereta_api_id');
    }

    public function realisasiKa(): HasMany
    {
        return $this->hasMany(RealisasiKa::class, 'kereta_api_id');
    }
}
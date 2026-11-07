<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalKaBatch extends Model
{
    protected $fillable = [
        'uuid',
        'status',
        'tanggal_mulai_berlaku',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai_berlaku' => 'date',
        ];
    }

    public function jadwalKa(): HasMany
    {
        return $this->hasMany(JadwalKa::class, 'jadwal_ka_batch_id');
    }

    public function jpls(): BelongsToMany
    {
        return $this->belongsToMany(
            Jpl::class,
            'jadwal_ka_batch_jpl',
            'jadwal_ka_batch_id',
            'jpl_id'
        )->withPivot('tanggal_akhir_berlaku');
    }
}

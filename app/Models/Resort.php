<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resort extends Model
{
    protected $table = 'resorts';

    protected $fillable = [
        'wilayah_id',
        'nama_resort',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function jpl(): HasMany
    {
        return $this->hasMany(Jpl::class);
    }
}
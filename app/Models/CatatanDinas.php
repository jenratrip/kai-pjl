<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatatanDinas extends Model
{
    protected $table = 'catatan_dinas';

    protected $fillable = [
        'dinas_id',
        'user_id',
        'judul',
        'kategori',
        'isi_catatan',
        'status',
        'tindak_lanjut',
        'waktu_catatan',
    ];

    protected $casts = [
        'waktu_catatan' => 'datetime',
    ];

    public function dinas(): BelongsTo
    {
        return $this->belongsTo(Dinas::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CatatanDinasAttachment::class);
    }
}
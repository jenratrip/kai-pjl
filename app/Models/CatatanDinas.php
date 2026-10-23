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
        'status_review',
        'catatan_revisi',
        'ditinjau_oleh',
        'ditinjau_pada',
    ];

    protected $casts = [
        'waktu_catatan' => 'datetime',
        'ditinjau_pada' => 'datetime',
    ];

    public function dinas(): BelongsTo
    {
        return $this->belongsTo(Dinas::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ditinjauOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CatatanDinasAttachment::class);
    }
}

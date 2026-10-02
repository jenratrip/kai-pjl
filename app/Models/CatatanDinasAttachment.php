<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatatanDinasAttachment extends Model
{
    protected $table = 'catatan_dinas_attachments';

    protected $fillable = [
        'catatan_dinas_id',
        'nama_file',
        'path',
        'mime_type',
        'ukuran',
    ];

    public function catatanDinas(): BelongsTo
    {
        return $this->belongsTo(
            CatatanDinas::class,
            'catatan_dinas_id'
        );
    }
}
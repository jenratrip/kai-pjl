<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachingMessage extends Model
{
    protected $fillable = [
        'source_system',
        'message_uuid',
        'inspection_id',
        'recipient_user_id',
        'matched_identifier_type',
        'subject',
        'body',
        'payload',
        'idempotency_key',
        'request_hash',
        'received_at',
        'expires_at',
        'read_at',
    ];

    protected $hidden = [
        'request_hash',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'read_at' => 'immutable_datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}

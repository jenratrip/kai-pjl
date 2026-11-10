<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachingMessageNonce extends Model
{
    protected $fillable = [
        'source_system',
        'nonce',
        'request_timestamp',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'request_timestamp' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}

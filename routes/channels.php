<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}.coaching-messages', function (User $user, string $userId): bool {
    return $user->hasRole('pjl')
        && $user->is_active
        && (string) $user->getKey() === $userId;
});

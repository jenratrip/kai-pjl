<?php

use App\Http\Controllers\Api\CoachingMessageController;
use App\Http\Middleware\VerifyCoachingMessageRequest;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('v1.')
    ->group(function (): void {
        Route::post('/coaching-messages/receive', [
            CoachingMessageController::class,
            'receive',
        ])
            ->middleware([
                'throttle:60,1',
                VerifyCoachingMessageRequest::class,
            ])
            ->name('coaching-messages.receive');
    });

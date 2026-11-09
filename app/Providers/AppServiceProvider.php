<?php

namespace App\Providers;

use App\Models\CoachingMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer([
            'components.user.sidebar',
            'components.user-operasional.sidebar',
        ], function (ViewInstance $view): void {
            if (array_key_exists('unreadCount', $view->getData())) {
                return;
            }

            $user = Auth::user();
            $unreadCount = null;

            if (
                $user !== null
                && $user->hasRole('pjl')
                && $user->is_active
                && Schema::hasTable('coaching_messages')
            ) {
                $unreadCount = CoachingMessage::query()
                    ->where('recipient_user_id', $user->getKey())
                    ->active()
                    ->whereNull('read_at')
                    ->count();
            }

            $view->with('unreadCount', $unreadCount);
        });
    }
}

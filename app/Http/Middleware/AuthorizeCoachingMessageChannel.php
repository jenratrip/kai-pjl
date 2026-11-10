<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeCoachingMessageChannel
{
    public function handle(Request $request, Closure $next): Response
    {
        $channelName = $request->input('channel_name');

        if (! is_string($channelName)) {
            return $next($request);
        }

        $isCoachingMessageNamespace = str_starts_with($channelName, 'private-users.')
            || str_starts_with($channelName, 'users.');

        if (! $isCoachingMessageNamespace) {
            return $next($request);
        }

        if (! preg_match('/\A(?:private-)?users\.(\d+)\.coaching-messages\z/', $channelName, $matches)) {
            abort(403);
        }

        $user = $request->user();

        abort_unless(
            $user instanceof User
                && $user->hasRole('pjl')
                && $user->is_active
                && (string) $user->getKey() === $matches[1],
            403,
        );

        return $next($request);
    }
}

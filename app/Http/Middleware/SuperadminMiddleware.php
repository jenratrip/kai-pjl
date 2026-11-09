<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperadminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun tidak aktif. Silakan hubungi administrator.',
                ], 403);
            }

            return redirect()
                ->route('login')
                ->withErrors([
                    'nip' => 'Akun tidak aktif. Silakan hubungi administrator.',
                ]);
        }

        if ($user->role?->name !== 'superadmin') {
            abort(403, 'Anda tidak memiliki akses ke halaman Superadmin.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
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

        $userRole = $user->role?->name;

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        if (
            $userRole === 'superadmin'
            && in_array('admin', $roles, true)
            && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
        ) {
            abort(403, 'Superadmin hanya memiliki akses baca pada fitur operasional.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('nip', $credentials['nip'])->first();

        if (!$user || !$user->is_active) {
            return back()
                ->withErrors([
                    'nip' => 'NIP atau password tidak valid.',
                ])
                ->onlyInput('nip');
        }

        if (!Auth::attempt([
            'nip' => $credentials['nip'],
            'password' => $credentials['password'],
        ])) {
            return back()
                ->withErrors([
                    'nip' => 'NIP atau password tidak valid.',
                ])
                ->onlyInput('nip');
        }

        $request->session()->regenerate();

        $this->activityLogService->log(
            'login',
            'auth',
            'User berhasil login',
            $user
        );

        return redirect()->intended(
            $user->isSuperadmin()
                ? route('superadmin.dashboard')
                : '/'
        );
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $this->activityLogService->log(
                'logout',
                'auth',
                'User berhasil logout',
                $user
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
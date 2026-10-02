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

    /*
    |--------------------------------------------------------------------------
    | Login Page
    |--------------------------------------------------------------------------
    */

    public function showLoginForm()
    {
        return view('auth.login');
    }


    /*
    |--------------------------------------------------------------------------
    | Login Process
    |--------------------------------------------------------------------------
    */

    public function login(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validasi
        |--------------------------------------------------------------------------
        */

        $credentials = $request->validate([
            'nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Cari User berdasarkan NIP
        |--------------------------------------------------------------------------
        */

        $user = User::where('nip', $credentials['nip'])->first();


        /*
        |--------------------------------------------------------------------------
        | Cek User dan Status
        |--------------------------------------------------------------------------
        */

        if (!$user || !$user->is_active) {
            return back()
                ->withErrors([
                    'nip' => 'NIP atau password tidak valid.',
                ])
                ->onlyInput('nip');
        }


        /*
        |--------------------------------------------------------------------------
        | Proses Authentication
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Regenerate Session
        |--------------------------------------------------------------------------
        */

        $request->session()->regenerate();


        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $this->activityLogService->log(
            'login',
            'auth',
            'User berhasil login',
            $user
        );


        /*
        |--------------------------------------------------------------------------
        | Redirect berdasarkan Role
        |--------------------------------------------------------------------------
        */

        // Superadmin
        if ($user->isSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }


        // Admin
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }


        // PJL
        if ($user->hasRole('pjl')) {
            return redirect()->route('pjl.dashboard');
        }


        /*
        |--------------------------------------------------------------------------
        | Role Tidak Dikenali
        |--------------------------------------------------------------------------
        */

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'nip' => 'Role pengguna tidak dikenali.',
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $user = $request->user();


        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        if ($user) {
            $this->activityLogService->log(
                'logout',
                'auth',
                'User berhasil logout',
                $user
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Logout
        |--------------------------------------------------------------------------
        */

        Auth::logout();


        /*
        |--------------------------------------------------------------------------
        | Invalidate Session
        |--------------------------------------------------------------------------
        */

        $request->session()->invalidate();
        $request->session()->regenerateToken();


        /*
        |--------------------------------------------------------------------------
        | Kembali ke Login
        |--------------------------------------------------------------------------
        */

        return redirect()->route('login');
    }
}
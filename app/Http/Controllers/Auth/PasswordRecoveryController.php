<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountPasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PasswordRecoveryController extends Controller
{
    private const REQUEST_MESSAGE = 'Jika NIPP/NIPKWT dan email cocok, instruksi pemulihan ditujukan ke email akun. Halaman ini tidak mengonfirmasi pengiriman; bila tidak menerima email, hubungi Admin.';

    public function create(): View
    {
        return view('auth.password-request');
    }

    public function store(Request $request, AccountPasswordResetService $passwordResetService): RedirectResponse
    {
        $credentials = $request->validate([
            'nip' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        try {
            $passwordResetService->sendResetLink($credentials['nip'], $credentials['email']);
        } catch (Throwable) {
            return back()->with('status', self::REQUEST_MESSAGE);
        }

        return back()->with('status', self::REQUEST_MESSAGE);
    }

    public function edit(int $user, string $token, AccountPasswordResetService $passwordResetService): View|RedirectResponse
    {
        if (! $passwordResetService->hasValidToken($user, $token)) {
            return redirect()->route('password.request')->with('status', 'Tautan reset tidak valid atau sudah kedaluwarsa.');
        }

        return view('auth.password-reset', ['userId' => $user, 'token' => $token]);
    }

    public function update(Request $request, int $user, string $token, AccountPasswordResetService $passwordResetService): RedirectResponse
    {
        $credentials = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! $passwordResetService->resetPassword($user, $token, $credentials['password'])) {
            return redirect()->route('password.request')->with('status', 'Tautan reset tidak valid atau sudah kedaluwarsa.');
        }

        return redirect()->route('login')->with('status', 'Kata sandi berhasil diperbarui. Silakan masuk menggunakan kata sandi baru.');
    }
}

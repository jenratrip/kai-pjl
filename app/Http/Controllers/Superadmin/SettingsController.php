<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use RuntimeException;

class SettingsController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $user->loadMissing('role');

        return view('superadmin.settings.index', [
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'] ?? null;
        $user->phone = $validated['phone'] ?? null;

        $profileChanged = $user->isDirty(['name', 'email', 'phone']);

        if ($profileChanged && ! $user->save()) {
            throw new RuntimeException('Profil Superadmin tidak dapat disimpan.');
        }

        if ($profileChanged) {
            $this->activityLogService->log(
                'update',
                'superadmin_profile',
                'Superadmin memperbarui profil akun sendiri.',
                $user
            );
        }

        return redirect()
            ->to(route('superadmin.settings.index').'#profil-superadmin')
            ->with(
                'success',
                $profileChanged
                    ? 'Profil Superadmin berhasil diperbarui.'
                    : 'Tidak ada perubahan pada profil Superadmin.'
            );
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user->password = Hash::make($validated['password']);

        if (! $user->save()) {
            throw new RuntimeException('Password Superadmin tidak dapat diperbarui.');
        }

        $this->activityLogService->log(
            'password_changed',
            'account_security',
            'Superadmin mengubah password akun sendiri.',
            $user
        );

        return redirect()
            ->to(route('superadmin.settings.index').'#keamanan-akun')
            ->with('password_success', 'Password berhasil diperbarui.');
    }
}

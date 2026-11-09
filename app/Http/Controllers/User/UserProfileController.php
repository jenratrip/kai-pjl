<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 404);

        $dinasId = $request->session()->get('dinas_id');
        $dinasAktif = is_numeric($dinasId) && (int) $dinasId > 0
            ? Dinas::query()
                ->with(['jpl.resort'])
                ->whereKey((int) $dinasId)
                ->where('user_id', $user->id)
                ->where('status', 'berlangsung')
                ->first()
            : null;

        return view('user.profile', [
            'user' => $user,
            'dinasAktif' => $dinasAktif,
        ]);
    }

    public function photo(): BinaryFileResponse
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 404);

        $path = $user->photo;
        abort_unless($this->isProfilePhotoPath($path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path);
        abort_unless(
            in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true),
            404
        );

        $response = response()->file($disk->path($path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $user = auth()->user();
        abort_unless($user instanceof User, 404);

        $newPath = $validated['photo']->store(
            'profile-photos/'.$user->id,
            'local'
        );

        if (! is_string($newPath)) {
            return back()->withErrors([
                'photo' => 'Foto profil gagal disimpan. Silakan coba kembali.',
            ]);
        }

        $oldPath = null;

        try {
            DB::transaction(function () use ($user, $newPath, &$oldPath): void {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $oldPath = $lockedUser->photo;
                $lockedUser->photo = $newPath;
                $lockedUser->save();
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);

            throw $exception;
        }

        $this->deleteStoredPhoto($oldPath);

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function destroyPhoto(): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 404);

        $oldPath = DB::transaction(function () use ($user): ?string {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $path = $lockedUser->photo;
            $lockedUser->photo = null;
            $lockedUser->save();

            return $path;
        });

        $this->deleteStoredPhoto($oldPath);

        return back()->with('success', 'Foto profil dihapus. Avatar inisial nama akan ditampilkan.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = auth()->user();
        abort_unless($user instanceof User, 404);

        $user->password = $validated['password'];
        $user->save();

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    private function deleteStoredPhoto(?string $path): void
    {
        if ($this->isProfilePhotoPath($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    private function isProfilePhotoPath(mixed $path): bool
    {
        return is_string($path)
            && str_starts_with($path, 'profile-photos/')
            && ! str_contains($path, '..');
    }
}

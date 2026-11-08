<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Admin boleh mengelola akun PJL dan Admin,
     * tetapi tidak boleh mengelola akun Superadmin.
     */
    private function findManagedUser(int $id): User
    {
        return User::with('role')
            ->whereHas('role', function ($query) {
                $query->whereIn('name', ['pjl', 'admin']);
            })
            ->findOrFail($id);
    }

    /**
     * Menampilkan daftar user PJL dan Admin.
     */
    public function index(Request $request)
    {
        $query = User::with('role')
            ->whereHas('role', function ($roleQuery) {
                $roleQuery->whereIn('name', ['pjl', 'admin']);
            });

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        $totalUsers = User::whereHas('role', fn ($q) => $q->whereIn('name', ['pjl', 'admin'])
        )->count();

        $totalActive = User::whereHas('role', fn ($q) => $q->whereIn('name', ['pjl', 'admin'])
        )->where('is_active', true)->count();

        $totalInactive = $totalUsers - $totalActive;

        return view('admin.users.index', compact(
            'users',
            'totalUsers',
            'totalActive',
            'totalInactive'
        ));
    }

    /**
     * Menampilkan detail user.
     */
    public function show(int $id)
    {
        $user = $this->findManagedUser($id);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Menampilkan formulir edit user.
     */
    public function edit(int $id)
    {
        $user = $this->findManagedUser($id);

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Memperbarui data dan role user.
     */
    public function update(Request $request, int $id)
    {
        $user = $this->findManagedUser($id);

        $validated = $request->validate([
            'nip' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'nip')->ignore($user->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['pjl', 'admin'])],
        ]);

        $role = Role::where('name', $validated['role'])->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role' => 'Role yang dipilih tidak tersedia.',
            ]);
        }

        $oldValues = [
            'nip' => $user->nip,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->name,
        ];

        DB::transaction(function () use (
            $user, $validated, $role, $oldValues
        ) {
            $user->update([
                'nip' => $validated['nip'],
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'role_id' => $role->id,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'update',
                'module' => 'manajemen_user',
                'description' => 'Mengubah data dan role user: '.$user->name,
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'nip' => $user->nip,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $role->name,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Data dan role user berhasil diperbarui.');
    }

    /**
     * Mengaktifkan atau menonaktifkan akun PJL/Admin.
     */
    public function toggleStatus(int $id)
    {
        $user = $this->findManagedUser($id);

        $oldStatus = (bool) $user->is_active;
        $newStatus = ! $oldStatus;
        $roleName = strtoupper($user->role->name);

        DB::transaction(function () use (
            $user, $oldStatus, $newStatus, $roleName
        ) {
            $user->update(['is_active' => $newStatus]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'update',
                'module' => 'manajemen_user',
                'description' => (
                    $newStatus ? 'Mengaktifkan akun ' : 'Menonaktifkan akun '
                ).$roleName.': '.$user->name,
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'old_values' => ['is_active' => $oldStatus],
                'new_values' => ['is_active' => $newStatus],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                $newStatus
                    ? 'Akun berhasil diaktifkan.'
                    : 'Akun berhasil dinonaktifkan.'
            );
    }
}

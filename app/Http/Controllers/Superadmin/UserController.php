<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Menampilkan daftar user.
     *
     * Request biasa:
     * Menampilkan halaman lengkap.
     *
     * Request AJAX:
     * Mengembalikan HTML tabel dan pagination dalam bentuk JSON.
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Role
        |--------------------------------------------------------------------------
        */

        if ($request->filled('role')) {
            $role = $request->input('role');

            $query->whereHas('role', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $status = $request->input('status');

            if ($status === 'active') {
                $query->where('is_active', true);
            }

            if ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $users = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalUsers = User::count();

        $totalAdmins = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->count();

        $totalPjl = User::whereHas('role', function ($query) {
            $query->where('name', 'pjl');
        })->count();

        $totalActive = User::where('is_active', true)->count();

        /*
        |--------------------------------------------------------------------------
        | AJAX Response
        |--------------------------------------------------------------------------
        */

        if ($request->ajax()) {
            $tableHtml = view(
                'superadmin.users.partials.table',
                [
                    'users' => $users,
                ]
            )->render();

            $paginationHtml = view(
                'superadmin.users.partials.pagination',
                [
                    'users' => $users,
                ]
            )->render();

            return response()->json([
                'success' => true,
                'table' => $tableHtml,
                'pagination' => $paginationHtml,
                'count' => $users->total(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Full Page Response
        |--------------------------------------------------------------------------
        */

        return view('superadmin.users.index', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalPjl' => $totalPjl,
            'totalActive' => $totalActive,
        ]);
    }

    /**
     * Form tambah user.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('superadmin.users.create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Menyimpan user baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => [
                'required',
                'string',
                'max:50',
                'unique:users,nip',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'role_id' => [
                'required',
                'exists:roles,id',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $role = Role::query()->findOrFail($validated['role_id']);
            $user = User::create([
                'nip' => $validated['nip'],
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'role_id' => $role->id,
                'is_active' => $validated['is_active'],
                'password' => Hash::make($validated['password']),
            ]);

            $this->activityLogService->log(
                'create',
                'users',
                'Superadmin membuat akun pengguna.',
                $user,
                null,
                [
                    'role' => $role->name,
                    'is_active' => (bool) $user->is_active,
                ]
            );
        });

        return redirect()
            ->route('superadmin.users.index')
            ->with(
                'success',
                'Pengguna berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan detail user.
     */
    public function show(User $user)
    {
        $user->load('role');

        return view('superadmin.users.show', [
            'user' => $user,
        ]);
    }

    /**
     * Form edit user.
     */
    public function edit(User $user)
    {
        $user->load('role');

        $roles = Role::orderBy('name')->get();

        return view('superadmin.users.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update user.
     */
    public function update(Request $request, User $user)
    {
        $isOwnAccount = (string) auth()->id() === (string) $user->getKey();
        $protectedAccountChangeAttempted = false;

        if ($isOwnAccount) {
            $submittedRoleId = $request->input('role_id');
            $submittedIsActive = $request->input('is_active');
            $normalizedIsActive = is_scalar($submittedIsActive)
                ? filter_var($submittedIsActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null;

            $protectedAccountChangeAttempted =
                ($submittedRoleId !== null && (
                    ! is_scalar($submittedRoleId) ||
                    (string) $submittedRoleId !== (string) $user->role_id
                )) ||
                ($submittedIsActive !== null && (
                    $normalizedIsActive === null ||
                    $normalizedIsActive !== $user->is_active
                ));

            $request->merge([
                'role_id' => $user->role_id,
                'is_active' => $user->is_active,
            ]);
        }

        $validated = $request->validate([
            'nip' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'nip')->ignore($user->id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'role_id' => [
                'required',
                'exists:roles,id',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Simpan nilai lama untuk Activity Log
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($user, $validated, $isOwnAccount): void {
            $lockedUser = User::query()
                ->with('role')
                ->lockForUpdate()
                ->findOrFail($user->id);
            $oldRole = $lockedUser->role?->name;
            $lockedUser->nip = $validated['nip'];
            $lockedUser->name = $validated['name'];
            $lockedUser->email = $validated['email'] ?? null;
            $lockedUser->phone = $validated['phone'] ?? null;

            if (! $isOwnAccount) {
                $lockedUser->role_id = $validated['role_id'];
                $lockedUser->is_active = $validated['is_active'];
            }

            $passwordChanged = ! empty($validated['password'])
                && ! Hash::check($validated['password'], $lockedUser->password);

            if ($passwordChanged) {
                $lockedUser->password = Hash::make($validated['password']);
            }

            $changedFields = array_keys($lockedUser->getDirty());
            $lockedUser->save();

            if ($changedFields !== []) {
                $oldValues = [];
                $newValues = [
                    'changed_fields' => array_map(
                        static fn (string $field): string => match ($field) {
                            'role_id' => 'role',
                            'password' => 'password_changed',
                            default => $field,
                        },
                        $changedFields
                    ),
                ];

                if (in_array('role_id', $changedFields, true)) {
                    $oldValues['role'] = $oldRole;
                    $newValues['role'] = Role::query()
                        ->findOrFail($lockedUser->role_id)
                        ->name;
                }

                if (in_array('is_active', $changedFields, true)) {
                    $oldValues['is_active'] = ! (bool) $lockedUser->is_active;
                    $newValues['is_active'] = (bool) $lockedUser->is_active;
                }

                $this->activityLogService->log(
                    'update',
                    'users',
                    'Superadmin memperbarui akun pengguna.',
                    $lockedUser,
                    $oldValues === [] ? null : $oldValues,
                    $newValues
                );
            }
        });

        $redirect = redirect()
            ->route('superadmin.users.index')
            ->with(
                'success',
                'Data pengguna berhasil diperbarui.'
            );

        if ($protectedAccountChangeAttempted) {
            $redirect->with(
                'error',
                'Role dan status aktif akun sendiri tidak dapat diubah. Perubahan field lain yang valid tetap diproses.'
            );
        }

        return $redirect;
    }

    /**
     * Mengubah status aktif/nonaktif user.
     *
     * Digunakan oleh AJAX.
     */
    public function toggleStatus(
        Request $request,
        User $user
    ) {
        /*
        |--------------------------------------------------------------------------
        | Jangan izinkan superadmin menonaktifkan akun sendiri
        |--------------------------------------------------------------------------
        */

        if (auth()->id() === $user->id) {

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak dapat menonaktifkan akun sendiri.',
                ], 422);
            }

            return back()->with(
                'error',
                'Anda tidak dapat menonaktifkan akun sendiri.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan status lama
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Toggle Status
        |--------------------------------------------------------------------------
        */

        $newStatus = DB::transaction(function () use ($user): bool {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);
            $oldStatus = (bool) $lockedUser->is_active;
            $newStatus = ! (bool) $lockedUser->is_active;
            $lockedUser->update(['is_active' => $newStatus]);

            $this->activityLogService->log(
                $newStatus ? 'activate' : 'deactivate',
                'users',
                $newStatus ? 'Superadmin mengaktifkan akun pengguna.' : 'Superadmin menonaktifkan akun pengguna.',
                $lockedUser,
                ['is_active' => $oldStatus],
                ['is_active' => $newStatus]
            );

            return $newStatus;
        });

        /*
        |--------------------------------------------------------------------------
        | AJAX Response
        |--------------------------------------------------------------------------
        */

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Status pengguna berhasil diperbarui.',
                'is_active' => $newStatus,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Response
        |--------------------------------------------------------------------------
        */

        return back()->with(
            'success',
            'Pengguna berhasil '.
                (
                    $newStatus
                        ? 'diaktifkan.'
                        : 'dinonaktifkan.'
                )
        );
    }
}

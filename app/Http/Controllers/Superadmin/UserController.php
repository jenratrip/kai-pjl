<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
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
                $q->where('nip', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
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
                'unique:users,email',
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

        $user = User::create([
            'nip' => $validated['nip'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role_id' => $validated['role_id'],
            'is_active' => $validated['is_active'],
            'password' => Hash::make($validated['password']),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $this->activityLogService->log(
            'create',
            'users',
            'Superadmin membuat pengguna baru: ' . $user->name,
            $user,
            null,
            [
                'nip' => $user->nip,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role_id' => $user->role_id,
                'is_active' => $user->is_active,
            ]
        );

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
                Rule::unique('users', 'email')->ignore($user->id),
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

        $oldValues = [
            'nip' => $user->nip,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role_id' => $user->role_id,
            'is_active' => $user->is_active,
        ];

        /*
        |--------------------------------------------------------------------------
        | Update Data
        |--------------------------------------------------------------------------
        */

        $user->nip = $validated['nip'];
        $user->name = $validated['name'];
        $user->email = $validated['email'] ?? null;
        $user->phone = $validated['phone'] ?? null;
        $user->role_id = $validated['role_id'];
        $user->is_active = $validated['is_active'];

        /*
        |--------------------------------------------------------------------------
        | Update Password Jika Diisi
        |--------------------------------------------------------------------------
        */

        if (! empty($validated['password'])) {
            $user->password = Hash::make(
                $validated['password']
            );
        }

        $user->save();

        /*
        |--------------------------------------------------------------------------
        | Nilai Baru Untuk Activity Log
        |--------------------------------------------------------------------------
        */

        $newValues = [
            'nip' => $user->nip,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role_id' => $user->role_id,
            'is_active' => $user->is_active,
        ];

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        if (
            $oldValues !== $newValues ||
            ! empty($validated['password'])
        ) {
            $this->activityLogService->log(
                'update',
                'users',
                'Superadmin memperbarui pengguna: ' . $user->name,
                $user,
                $oldValues,
                $newValues
            );
        }

        return redirect()
            ->route('superadmin.users.index')
            ->with(
                'success',
                'Data pengguna berhasil diperbarui.'
            );
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

        $oldStatus = $user->is_active;

        /*
        |--------------------------------------------------------------------------
        | Toggle Status
        |--------------------------------------------------------------------------
        */

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Tentukan Action Log
        |--------------------------------------------------------------------------
        */

        $action = $user->is_active
            ? 'activate'
            : 'deactivate';

        $statusText = $user->is_active
            ? 'mengaktifkan'
            : 'menonaktifkan';

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $this->activityLogService->log(
            $action,
            'users',
            'Superadmin ' .
                $statusText .
                ' pengguna: ' .
                $user->name,
            $user,
            [
                'is_active' => $oldStatus,
            ],
            [
                'is_active' => $user->is_active,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | AJAX Response
        |--------------------------------------------------------------------------
        */

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Status pengguna berhasil diperbarui.',
                'is_active' => $user->is_active,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Response
        |--------------------------------------------------------------------------
        */

        return back()->with(
            'success',
            'Pengguna berhasil ' .
                (
                    $user->is_active
                        ? 'diaktifkan.'
                        : 'dinonaktifkan.'
                )
        );
    }
}
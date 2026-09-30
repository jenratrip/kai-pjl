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
     * Menampilkan daftar seluruh pengguna.
     */
    public function index()
    {
        $users = User::with('role')
            ->latest()
            ->paginate(10);

        $totalUsers = User::count();

        $totalAdmins = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->count();

        $totalPjl = User::whereHas('role', function ($query) {
            $query->where('name', 'pjl');
        })->count();

        $totalActive = User::where('is_active', true)->count();

        return view('superadmin.users.index', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalPjl' => $totalPjl,
            'totalActive' => $totalActive,
        ]);
    }

    /**
     * Menampilkan form tambah pengguna.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('superadmin.users.create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Menyimpan pengguna baru.
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
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }
}
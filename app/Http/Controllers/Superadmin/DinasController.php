<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Jpl;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DinasController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'jpl_id' => ['nullable', 'integer', 'exists:jpl,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'tanggal_dinas' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $query = Dinas::query()->with([
            'user:id,name,nip',
            'jpl:id,kode_jpl,nama_jpl',
            'shift:id,nama_shift',
        ]);

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['jpl_id'])) {
            $query->where('jpl_id', $filters['jpl_id']);
        }

        if (! empty($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        if (! empty($filters['tanggal_dinas'])) {
            $query->whereDate('tanggal_dinas', $filters['tanggal_dinas']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($query) use ($search): void {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                })->orWhereHas('jpl', function ($jplQuery) use ($search): void {
                    $jplQuery->where('kode_jpl', 'like', "%{$search}%")
                        ->orWhere('nama_jpl', 'like', "%{$search}%");
                })->orWhereHas('shift', function ($shiftQuery) use ($search): void {
                    $shiftQuery->where('nama_shift', 'like', "%{$search}%");
                });
            });
        }

        $dinas = $query
            ->orderByDesc('tanggal_dinas')
            ->orderByDesc('jam_mulai')
            ->paginate(10)
            ->withQueryString();

        $petugas = User::whereHas('role', function ($query): void {
            $query->where('name', 'pjl');
        })->orderBy('name')->get(['id', 'name', 'nip']);

        $jpls = Jpl::orderBy('kode_jpl')->get(['id', 'kode_jpl', 'nama_jpl']);
        $shifts = Shift::orderBy('id')->get(['id', 'nama_shift']);

        return view('superadmin.dinas.index', [
            'dinas' => $dinas,
            'petugas' => $petugas,
            'jpls' => $jpls,
            'shifts' => $shifts,
            'filters' => $filters,
        ]);
    }
}

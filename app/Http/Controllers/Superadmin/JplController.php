<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Jpl;
use App\Models\Resort;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JplController extends Controller
{
    public function index(Request $request)
    {
        $query = Jpl::with('resort.wilayah');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('kode_jpl', 'like', "%{$search}%")
                    ->orWhere('nama_jpl', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('resort_id')) {
            $query->where('resort_id', $request->resort_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $jpls = $query->latest()
            ->paginate(10)
            ->withQueryString();

        $resorts = Resort::with('wilayah')
            ->where('is_active', true)
            ->orderBy('nama_resort')
            ->get();

        $totalJpl = Jpl::count();
        $totalResort = Resort::count();

        return view('superadmin.jpl.index', compact(
            'jpls',
            'resorts',
            'totalJpl',
            'totalResort'
        ));
    }

    public function edit(Jpl $jpl)
    {
        $jpl->load('resort.wilayah');

        $resorts = Resort::with('wilayah')
            ->where('is_active', true)
            ->orderBy('nama_resort')
            ->get();

        return view('superadmin.jpl.edit', compact(
            'jpl',
            'resorts'
        ));
    }

    public function update(
        Request $request,
        Jpl $jpl,
        ActivityLogService $activityLog
    ) {
        $validated = $request->validate([
            'kode_jpl' => [
                'nullable',
                'string',
                'max:255',
            ],
            'nama_jpl' => [
                'required',
                'string',
                'max:255',
            ],
            'resort_id' => [
                'required',
                'integer',
                'exists:resorts,id',
            ],
            'antara' => ['nullable', 'string', 'max:255'],
            'km_hm' => ['nullable', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'kota_kabupaten' => ['nullable', 'string', 'max:255'],
            'provinsi' => ['nullable', 'string', 'max:255'],
            'jenis_jalur_ka' => ['nullable', 'string', 'max:255'],
            'titik_koordinat' => ['nullable', 'string', 'max:255'],
            'status_penjagaan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'nama_jpl.required' => 'Nama JPL wajib diisi.',
            'resort_id.required' => 'Resort wajib dipilih.',
            'reason.required' => 'Alasan koreksi wajib diisi.',
            'reason.min' => 'Alasan koreksi minimal 10 karakter.',
        ]);

        $reason = trim($validated['reason']);
        unset($validated['reason']);

        $allowedFields = [
            'kode_jpl',
            'nama_jpl',
            'resort_id',
            'antara',
            'km_hm',
            'lokasi',
            'kota_kabupaten',
            'provinsi',
            'jenis_jalur_ka',
            'titik_koordinat',
            'status_penjagaan',
            'keterangan',
            'is_active',
        ];

        $oldValues = [];
        $newValues = [];

        foreach ($allowedFields as $field) {
            $oldValue = $jpl->getAttribute($field);
            $newValue = $validated[$field] ?? null;

            if ($field === 'is_active') {
                $oldValue = (bool) $oldValue;
                $newValue = (bool) $newValue;
            }

            if ($oldValue != $newValue) {
                $oldValues[$field] = $oldValue;
                $newValues[$field] = $newValue;
            }
        }

        if (empty($newValues)) {
            return redirect()
                ->route('superadmin.jpl.edit', $jpl)
                ->withInput()
                ->with('error', 'Tidak ada perubahan data yang disimpan.');
        }

        DB::transaction(function () use (
            $jpl,
            $validated,
            $oldValues,
            $newValues,
            $reason,
            $activityLog
        ) {
            $jpl->update($validated);

            $activityLog->log(
                action: 'update',
                module: 'jpl',
                description: 'Koreksi data JPL. Alasan: ' . $reason,
                subject: $jpl,
                oldValues: $oldValues,
                newValues: $newValues
            );
        });

        return redirect()
            ->route('superadmin.jpl.index')
            ->with('success', 'Data JPL berhasil dikoreksi dan riwayatnya dicatat.');
    }
}
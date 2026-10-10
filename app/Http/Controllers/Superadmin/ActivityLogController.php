<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        // Pencarian nama pengguna atau deskripsi aktivitas
        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%");
                    });
            });
        }

        // Filter modul
        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        // Filter jenis aktivitas
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter tanggal mulai
        if ($request->filled('start_date')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->input('start_date')
            );
        }

        // Filter tanggal akhir
        if ($request->filled('end_date')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->input('end_date')
            );
        }

        $activities = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Pilihan filter berdasarkan data yang tersedia
        $modules = ActivityLog::query()
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $actions = ActivityLog::query()
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('superadmin.activities.index', [
            'activities' => $activities,
            'modules' => $modules,
            'actions' => $actions,
        ]);
    }

    public function show(ActivityLog $activity)
    {
        $activity->load('user');

        return view('superadmin.activities.show', [
            'activity' => $activity,
        ]);
    }
}
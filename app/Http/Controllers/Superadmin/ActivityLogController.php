<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ActivityLogController extends Controller
{
    /**
     * Values that must not be exposed from historical activity payloads.
     *
     * @var list<string>
     */
    private const SENSITIVE_VALUE_KEYS = [
        'nip',
        'name',
        'email',
        'phone',
        'password',
        'password_hash',
        'remember_token',
        'token',
        'api_key',
        'secret',
        'signature',
        'tanda_tangan',
    ];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'module' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if (
            ! empty($validated['start_date'])
            && ! empty($validated['end_date'])
            && $validated['start_date'] > $validated['end_date']
        ) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            ]);
        }

        $query = ActivityLog::with('user');

        // Pencarian nama pengguna atau deskripsi aktivitas
        if ($request->filled('search')) {
            $search = trim($validated['search']);

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
            $query->where('module', $validated['module']);
        }

        // Filter jenis aktivitas
        if ($request->filled('action')) {
            $query->where('action', $validated['action']);
        }

        // Filter tanggal mulai
        if ($request->filled('start_date')) {
            $query->whereDate(
                'created_at',
                '>=',
                $validated['start_date']
            );
        }

        // Filter tanggal akhir
        if ($request->filled('end_date')) {
            $query->whereDate(
                'created_at',
                '<=',
                $validated['end_date']
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
        $activity->old_values = $this->redactSensitiveValues($activity->old_values);
        $activity->new_values = $this->redactSensitiveValues($activity->new_values);

        return view('superadmin.activities.show', [
            'activity' => $activity,
        ]);
    }

    /**
     * Redact sensitive values from legacy logs for display without persisting changes.
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private function redactSensitiveValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_VALUE_KEYS, true)) {
                $values[$key] = '[disamarkan]';

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redactSensitiveValues($value);
            }
        }

        return $values;
    }
}

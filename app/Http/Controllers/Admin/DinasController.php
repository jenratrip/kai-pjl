<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Jpl;
use App\Models\Shift;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DinasJadwalSnapshotService;
use App\Services\DinasRosterWorkbookParser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class DinasController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $this->sinkronkanStatus(app(ActivityLogService::class));

        $query = Dinas::with([
            'user',
            'userDigantikan',
            'jpl',
            'shift',
        ]);

        $this->terapkanFilter($query, $request);

        $dinas = $query
            ->orderByDesc('tanggal_dinas')
            ->orderByDesc('jam_mulai')
            ->paginate(10)
            ->withQueryString();

        $petugas = User::whereHas(
            'role',
            fn ($q) => $q->where('name', 'pjl')
        )
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $jpl = Jpl::where('is_active', true)
            ->orderBy('kode_jpl')
            ->get();

        $shifts = Shift::orderBy('id')->get();

        return view('admin.dinas.index', compact(
            'dinas',
            'petugas',
            'jpl',
            'shifts'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | REAL-TIME UPDATES
    |--------------------------------------------------------------------------
    |
    | Dipanggil oleh JavaScript halaman dinas secara berkala.
    | Filter aktif dan halaman pagination tetap diperhitungkan.
    |
    */

    public function updates(Request $request)
    {
        $this->sinkronkanStatus(app(ActivityLogService::class));

        $query = Dinas::with([
            'user',
            'userDigantikan',
            'jpl',
            'shift',
        ]);

        $this->terapkanFilter($query, $request);

        $dinas = $query
            ->orderByDesc('tanggal_dinas')
            ->orderByDesc('jam_mulai')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'data' => $dinas,
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | APPLY FILTER
    |--------------------------------------------------------------------------
    */

    private function terapkanFilter($query, Request $request): void
    {
        if ($request->filled('month') && filter_var($request->input('month'), FILTER_VALIDATE_INT) !== false) {
            $month = (int) $request->input('month');
            if ($month >= 1 && $month <= 12) {
                $query->whereMonth('tanggal_dinas', $month);
            }
        }

        if ($request->filled('year') && filter_var($request->input('year'), FILTER_VALIDATE_INT) !== false) {
            $year = (int) $request->input('year');
            if ($year >= 2000 && $year <= 2100) {
                $query->whereYear('tanggal_dinas', $year);
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('jpl_id')) {
            $query->where('jpl_id', $request->jpl_id);
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        if ($request->filled('tanggal_dinas')) {
            $query->whereDate(
                'tanggal_dinas',
                $request->tanggal_dinas
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SYNCHRONIZE SCHEDULE STATUS
    |--------------------------------------------------------------------------
    */

    private function sinkronkanStatus(ActivityLogService $activityLogService): void
    {
        $now = now();

        DB::transaction(function () use ($activityLogService, $now): void {
            $expiredDinas = Dinas::query()
                ->whereIn('status', ['dijadwalkan', 'berlangsung'])
                ->whereNotNull('jam_selesai')
                ->where('jam_selesai', '<=', $now)
                ->lockForUpdate()
                ->get();

            foreach ($expiredDinas as $dinas) {
                $oldStatus = $dinas->status;
                $dinas->update([
                    'status' => 'selesai',
                    'updated_at' => $now,
                ]);

                $activityLogService->log(
                    'status_sync',
                    'dinas',
                    'Status Dinas diselesaikan otomatis setelah waktu dinas berakhir.',
                    $dinas,
                    ['status' => $oldStatus],
                    ['status' => 'selesai'],
                );
            }

            $activeDinas = Dinas::query()
                ->where('status', 'dijadwalkan')
                ->whereNotNull('jam_mulai')
                ->whereNotNull('jam_selesai')
                ->where('jam_mulai', '<=', $now)
                ->where('jam_selesai', '>', $now)
                ->lockForUpdate()
                ->get();

            foreach ($activeDinas as $dinas) {
                $dinas->update([
                    'status' => 'berlangsung',
                    'updated_at' => $now,
                ]);

                $activityLogService->log(
                    'status_sync',
                    'dinas',
                    'Status Dinas diaktifkan otomatis saat waktu dinas dimulai.',
                    $dinas,
                    ['status' => 'dijadwalkan'],
                    ['status' => 'berlangsung'],
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        DinasJadwalSnapshotService $snapshotService,
        ActivityLogService $activityLogService
    ) {
        $validated = $this->validateSchedule($request);

        $petugas = $this->findActivePjl(
            (int) $validated['user_id']
        );

        $jpl = Jpl::where('is_active', true)
            ->findOrFail($validated['jpl_id']);

        $shift = Shift::findOrFail(
            $validated['shift_id']
        );

        [$mulai, $selesai] = $this->calculateSchedule(
            $validated['tanggal_dinas'],
            $shift
        );

        DB::transaction(function () use (
            $petugas,
            $jpl,
            $shift,
            $validated,
            $request,
            $mulai,
            $selesai,
            $snapshotService,
            $activityLogService
        ) {
            $lockedJpl = Jpl::query()
                ->whereKey($jpl->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedJpl->is_active) {
                throw ValidationException::withMessages([
                    'jpl_id' => 'JPL sudah tidak aktif. Jadwal dinas tidak dapat ditambahkan.',
                ]);
            }

            $lockedPetugas = User::query()
                ->whereKey($petugas->id)
                ->where('is_active', true)
                ->whereHas('role', fn ($query) => $query->where('name', 'pjl'))
                ->lockForUpdate()
                ->first();

            if (! $lockedPetugas) {
                throw ValidationException::withMessages([
                    'user_id' => 'Petugas sudah tidak aktif atau bukan PJL. Jadwal dinas tidak dapat ditambahkan.',
                ]);
            }

            $this->ensureNoConflict(
                $lockedPetugas->id,
                $mulai,
                $selesai
            );

            $status = $this->tentukanStatus(
                $mulai,
                $selesai
            );

            $dinas = Dinas::create([
                'user_id' => $lockedPetugas->id,
                'jpl_id' => $lockedJpl->id,
                'shift_id' => $shift->id,
                'tanggal_dinas' => $validated['tanggal_dinas'],
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,
                'status' => $status,

                'is_pengganti' => $request->boolean(
                    'is_pengganti'
                ),

                'user_digantikan_id' => $request->boolean(
                    'is_pengganti'
                )
                    ? $validated['user_digantikan_id']
                    : null,
            ]);

            $snapshotService->resolveAndStore((int) $dinas->id);

            $activityLogService->log(
                'create',
                'dinas',
                'Jadwal Dinas berhasil dibuat.',
                $dinas,
                null,
                $this->activityValues($dinas),
            );
        });

        return redirect()
            ->route('admin.dinas.index')
            ->with(
                'success',
                'Jadwal dinas berhasil ditambahkan.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(Dinas $dina)
    {
        $dina->load([
            'user',
            'userDigantikan',
            'jpl',
            'shift',
        ]);

        $petugas = User::whereHas(
            'role',
            fn ($q) => $q->where('name', 'pjl')
        )
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $jpl = Jpl::where('is_active', true)
            ->orderBy('kode_jpl')
            ->get();

        $shifts = Shift::orderBy('id')->get();

        return view('admin.dinas.edit', compact(
            'dina',
            'petugas',
            'jpl',
            'shifts'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Dinas $dina,
        ActivityLogService $activityLogService
    ) {
        $this->ensureCanModify($dina);

        $currentContext = Dinas::query()
            ->select(['id', 'user_id', 'jpl_id'])
            ->findOrFail($dina->id);

        $validated = $this->validateSchedule($request);

        $petugas = $this->findActivePjl(
            (int) $validated['user_id']
        );

        $jpl = Jpl::where('is_active', true)
            ->findOrFail($validated['jpl_id']);

        $shift = Shift::findOrFail(
            $validated['shift_id']
        );

        [$mulai, $selesai] = $this->calculateSchedule(
            $validated['tanggal_dinas'],
            $shift
        );

        DB::transaction(function () use (
            $dina,
            $currentContext,
            $petugas,
            $jpl,
            $shift,
            $validated,
            $request,
            $mulai,
            $selesai,
            $activityLogService
        ) {
            $targetJplId = (int) $jpl->id;
            $jplIdsToLock = collect([
                (int) $currentContext->jpl_id,
                $targetJplId,
            ])
                ->unique()
                ->sort()
                ->values();

            $lockedJpls = Jpl::query()
                ->whereIn('id', $jplIdsToLock)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lockedJpls->count() !== $jplIdsToLock->count()) {
                throw ValidationException::withMessages([
                    'jpl_id' => 'JPL Dinas tidak lagi tersedia. Muat ulang halaman sebelum mencoba kembali.',
                ]);
            }

            $lockedTargetJpl = $lockedJpls->get($targetJplId);

            if (! $lockedTargetJpl->is_active) {
                throw ValidationException::withMessages([
                    'jpl_id' => 'JPL sudah tidak aktif. Jadwal dinas tidak dapat diperbarui.',
                ]);
            }

            $targetUserId = (int) $petugas->id;
            $userIdsToLock = collect([
                (int) $currentContext->user_id,
                $targetUserId,
            ])
                ->unique()
                ->sort()
                ->values();

            $lockedUsers = User::query()
                ->whereIn('id', $userIdsToLock)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lockedUsers->count() !== $userIdsToLock->count()) {
                throw ValidationException::withMessages([
                    'user_id' => 'Petugas Dinas tidak lagi tersedia. Muat ulang halaman sebelum mencoba kembali.',
                ]);
            }

            $lockedPetugas = $lockedUsers->get($targetUserId);

            if (
                ! $lockedPetugas->is_active
                || ! $lockedPetugas->role()->where('name', 'pjl')->exists()
            ) {
                throw ValidationException::withMessages([
                    'user_id' => 'Petugas sudah tidak aktif atau bukan PJL. Jadwal dinas tidak dapat diperbarui.',
                ]);
            }

            $lockedDinas = Dinas::whereKey($dina->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $lockedDinas->jpl_id !== (int) $currentContext->jpl_id
                || (int) $lockedDinas->user_id !== (int) $currentContext->user_id
            ) {
                throw ValidationException::withMessages([
                    'dinas' => 'Konteks Dinas berubah saat diproses. Muat ulang halaman sebelum mencoba kembali.',
                ]);
            }

            $this->ensureCanModify($lockedDinas);

            $oldValues = $this->activityValues($lockedDinas);

            $userDigantikanId = $request->boolean('is_pengganti')
                ? ($validated['user_digantikan_id'] ?? null)
                : null;

            if (
                $lockedDinas->jadwal_ka_snapshot !== null
                && (
                    (int) $validated['user_id'] !== (int) $lockedDinas->user_id
                    || (int) $validated['jpl_id'] !== (int) $lockedDinas->jpl_id
                    || (int) $validated['shift_id'] !== (int) $lockedDinas->shift_id
                    || $validated['tanggal_dinas'] !== $lockedDinas->tanggal_dinas->toDateString()
                    || $request->boolean('is_pengganti') !== (bool) $lockedDinas->is_pengganti
                    || ($userDigantikanId === null
                        ? null
                        : (int) $userDigantikanId) !== ($lockedDinas->user_digantikan_id === null
                        ? null
                        : (int) $lockedDinas->user_digantikan_id)
                )
            ) {
                throw ValidationException::withMessages([
                    'dinas' => 'Petugas, status pengganti, petugas yang digantikan, JPL, shift, dan tanggal tidak dapat diubah karena snapshot Jadwal KA sudah terkunci.',
                ]);
            }

            $this->ensureNoConflict(
                $lockedPetugas->id,
                $mulai,
                $selesai,
                $lockedDinas->id
            );

            $lockedDinas->update([
                'user_id' => $lockedPetugas->id,
                'jpl_id' => $lockedTargetJpl->id,
                'shift_id' => $shift->id,
                'tanggal_dinas' => $validated['tanggal_dinas'],
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,

                // Status tidak diubah langsung oleh proses edit.
                'is_pengganti' => $request->boolean(
                    'is_pengganti'
                ),

                'user_digantikan_id' => $request->boolean(
                    'is_pengganti'
                )
                    ? $validated['user_digantikan_id']
                    : null,
            ]);

            $newValues = $this->activityValues($lockedDinas);

            if ($oldValues !== $newValues) {
                $activityLogService->log(
                    'update',
                    'dinas',
                    'Jadwal Dinas berhasil diperbarui.',
                    $lockedDinas,
                    $oldValues,
                    $newValues,
                );
            }
        });

        return redirect()
            ->route('admin.dinas.index')
            ->with(
                'success',
                'Jadwal dinas berhasil diperbarui.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(Dinas $dina, ActivityLogService $activityLogService)
    {
        DB::transaction(function () use ($dina, $activityLogService): void {
            $lockedDinas = Dinas::query()
                ->whereKey($dina->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDinas->jadwal_ka_snapshot !== null) {
                throw ValidationException::withMessages([
                    'dinas' => 'Dinas tidak dapat dihapus karena snapshot Jadwal KA sudah terkunci.',
                ]);
            }

            $this->ensureCanModify($lockedDinas);
            $oldValues = $this->activityValues($lockedDinas);
            $lockedDinas->delete();

            $activityLogService->log(
                'delete',
                'dinas',
                'Jadwal Dinas berhasil dihapus.',
                $lockedDinas,
                $oldValues,
            );
        });

        return redirect()
            ->route('admin.dinas.index')
            ->with(
                'success',
                'Jadwal dinas berhasil dihapus.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT CSV
    |--------------------------------------------------------------------------
    */

    public function export(Request $request)
    {
        $query = Dinas::with([
            'user',
            'userDigantikan',
            'jpl',
            'shift',
        ]);

        $this->terapkanFilter($query, $request);
        $rows = $query
            ->orderBy('tanggal_dinas')
            ->orderBy('jam_mulai')
            ->get();

        $filename = 'jadwal-dinas-'.
            now()->format('Ymd-His').
            '.csv';

        return response()->streamDownload(
            function () use ($rows) {
                $out = fopen('php://output', 'w');

                fwrite($out, "\xEF\xBB\xBF");

                fputcsv($out, $this->csvHeaders(), ';');

                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->user?->nip ?? '',
                        $row->jpl?->kode_jpl ?? '',
                        $row->shift_id,
                        $row->tanggal_dinas
                            ? Carbon::parse(
                                $row->tanggal_dinas
                            )->format('Y-m-d')
                            : '',
                        $row->jam_mulai
                            ? Carbon::parse(
                                $row->jam_mulai
                            )->format('H:i:s')
                            : '',
                        $row->jam_selesai
                            ? Carbon::parse(
                                $row->jam_selesai
                            )->format('H:i:s')
                            : '',
                        $row->is_pengganti ? '1' : '0',
                        $row->userDigantikan?->nip ?? '',
                    ], ';');
                }

                fclose($out);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TEMPLATE CSV
    |--------------------------------------------------------------------------
    */

    public function template()
    {
        return response()->streamDownload(
            function () {
                $out = fopen('php://output', 'w');

                fwrite($out, "\xEF\xBB\xBF");

                fputcsv($out, $this->csvHeaders(), ';');

                fclose($out);
            },
            'template-jadwal-dinas.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT CSV
    |--------------------------------------------------------------------------
    */

    public function previewImport(
        Request $request,
        DinasJadwalSnapshotService $snapshotService,
        ActivityLogService $activityLogService,
        DinasRosterImportController $rosterImportController,
        DinasRosterWorkbookParser $rosterParser
    ): JsonResponse {
        if (strtolower((string) $request->file('file')?->getClientOriginalExtension()) === 'xlsx') {
            return $rosterImportController->preview($request, $rosterParser);
        }

        $request->attributes->set('_dinas_import_preview_only', true);

        return $this->import(
            $request,
            $snapshotService,
            $activityLogService,
            $rosterImportController,
            $rosterParser,
        );
    }

    public function import(
        Request $request,
        DinasJadwalSnapshotService $snapshotService,
        ActivityLogService $activityLogService,
        DinasRosterImportController $rosterImportController,
        DinasRosterWorkbookParser $rosterParser
    ) {
        $previewOnly = $request->attributes->get('_dinas_import_preview_only') === true;

        if (strtolower((string) $request->file('file')?->getClientOriginalExtension()) === 'xlsx') {
            if ($previewOnly) {
                return $rosterImportController->preview($request, $rosterParser);
            }

            return $rosterImportController->confirm(
                $request,
                $rosterParser,
                $snapshotService,
                $activityLogService,
            );
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:5120',
            ],
        ]);

        $handle = fopen(
            $request->file('file')->getRealPath(),
            'r'
        );

        if ($handle === false) {
            if ($previewOnly) {
                return response()->json([
                    'message' => 'File CSV tidak dapat dibaca.',
                    'valid' => false,
                    'errors' => ['File CSV tidak dapat dibaca.'],
                ], 422);
            }

            return back()->with(
                'error',
                'File CSV tidak dapat dibaca.'
            );
        }

        try {
            $header = fgetcsv($handle, 0, ';');

            if ($header === false) {
                if ($previewOnly) {
                    return response()->json([
                        'message' => 'File CSV kosong.',
                        'valid' => false,
                        'errors' => ['File CSV kosong.'],
                    ], 422);
                }

                return back()->with(
                    'error',
                    'File CSV kosong.'
                );
            }

            $header[0] = (string) $header[0];

            foreach (["\xEF\xBB\xBF", "\u{FEFF}", 'ï»¿'] as $bom) {
                if (str_starts_with($header[0], $bom)) {
                    $header[0] = substr(
                        $header[0],
                        strlen($bom)
                    );

                    break;
                }
            }

            $header = array_map(
                fn ($value) => strtolower(trim((string) $value)),
                $header
            );

            if ($header !== $this->csvHeaders()) {
                if ($previewOnly) {
                    $message = 'Header CSV tidak sesuai template. Unduh template terbaru dan jangan mengubah nama atau urutan 8 kolom.';

                    return response()->json([
                        'message' => $message,
                        'valid' => false,
                        'errors' => [$message],
                    ], 422);
                }

                return back()->with(
                    'error',
                    'Header CSV tidak sesuai template. '.
                    'Unduh template terbaru dan jangan '.
                    'mengubah nama atau urutan 8 kolom.'
                );
            }

            $validRows = [];
            $errors = [];
            $line = 1;
            $totalRows = 0;

            while (
                ($data = fgetcsv($handle, 0, ';')) !== false
            ) {
                $line++;

                if (
                    count(array_filter(
                        $data,
                        fn ($value) => trim((string) $value) !== ''
                    )) === 0
                ) {
                    continue;
                }

                $totalRows++;

                if (count($data) !== count($this->csvHeaders())) {
                    $errors[] =
                        "Baris {$line}: jumlah kolom tidak sesuai.";

                    continue;
                }

                $data = array_map(
                    fn ($value) => trim((string) $value),
                    $data
                );

                [
                    $nip,
                    $kodeJpl,
                    $shiftId,
                    $tanggalRaw,
                    $jamMulaiCsv,
                    $jamSelesaiCsv,
                    $isPengganti,
                    $nipDigantikan,
                ] = $data;

                $tanggal = $this->normalizeDate($tanggalRaw);

                if ($tanggal === null) {
                    $errors[] =
                        "Baris {$line}: tanggal harus ".
                        'YYYY-MM-DD atau DD/MM/YYYY.';

                    continue;
                }

                $validator = Validator::make([
                    'nip' => $nip,
                    'kode_jpl' => $kodeJpl,
                    'shift_id' => $shiftId,
                    'tanggal_dinas' => $tanggal,
                    'jam_mulai' => $jamMulaiCsv,
                    'jam_selesai' => $jamSelesaiCsv,
                    'is_pengganti' => $isPengganti,
                    'nip_digantikan' => $nipDigantikan,
                ], [
                    'nip' => ['required', 'string'],
                    'kode_jpl' => ['required', 'string'],
                    'shift_id' => [
                        'required',
                        'integer',
                        'exists:shifts,id',
                    ],
                    'tanggal_dinas' => [
                        'required',
                        'date_format:Y-m-d',
                    ],
                    'jam_mulai' => [
                        'nullable',
                        'date_format:H:i:s',
                    ],
                    'jam_selesai' => [
                        'nullable',
                        'date_format:H:i:s',
                    ],
                    'is_pengganti' => [
                        'required',
                        'in:0,1',
                    ],
                    'nip_digantikan' => [
                        'nullable',
                        'required_if:is_pengganti,1',
                        'string',
                    ],
                ]);

                if ($validator->fails()) {
                    $errors[] =
                        "Baris {$line}: ".
                        implode(
                            ' ',
                            $validator->errors()->all()
                        );

                    continue;
                }

                $petugas = User::where('nip', $nip)
                    ->where('is_active', true)
                    ->whereHas(
                        'role',
                        fn ($q) => $q->where('name', 'pjl')
                    )
                    ->first();

                $jpl = Jpl::where('kode_jpl', $kodeJpl)
                    ->where('is_active', true)
                    ->first();

                $shift = Shift::find($shiftId);

                if (! $petugas || ! $jpl || ! $shift) {
                    $errors[] =
                        "Baris {$line}: NIP petugas, kode ".
                        'JPL, atau shift tidak ditemukan '.
                        'atau tidak aktif.';

                    continue;
                }

                $digantikan = null;

                if ($isPengganti === '1') {
                    $digantikan = User::where(
                        'nip',
                        $nipDigantikan
                    )
                        ->where('is_active', true)
                        ->whereHas(
                            'role',
                            fn ($q) => $q->where('name', 'pjl')
                        )
                        ->first();

                    if (
                        ! $digantikan ||
                        $digantikan->id === $petugas->id
                    ) {
                        $errors[] =
                            "Baris {$line}: NIP petugas ".
                            'yang digantikan tidak valid.';

                        continue;
                    }
                } elseif ($nipDigantikan !== '') {
                    $errors[] =
                        "Baris {$line}: nip_digantikan ".
                        'harus kosong jika is_pengganti bernilai 0.';

                    continue;
                }

                [$mulai, $selesai] = $this->calculateSchedule(
                    $tanggal,
                    $shift
                );

                if (
                    (
                        $jamMulaiCsv !== '' &&
                        $jamMulaiCsv !== $mulai->format('H:i:s')
                    ) ||
                    (
                        $jamSelesaiCsv !== '' &&
                        $jamSelesaiCsv !== $selesai->format('H:i:s')
                    )
                ) {
                    $errors[] =
                        "Baris {$line}: jam tidak sesuai ".
                        'dengan konfigurasi shift.';

                    continue;
                }

                $bentrokDatabase = Dinas::where(
                    'user_id',
                    $petugas->id
                )
                    ->where('jam_mulai', '<', $selesai)
                    ->where('jam_selesai', '>', $mulai)
                    ->exists();

                $bentrokFile = false;

                foreach ($validRows as $previous) {
                    if (
                        $previous['user_id'] === $petugas->id &&
                        $previous['jam_mulai']->lt($selesai) &&
                        $previous['jam_selesai']->gt($mulai)
                    ) {
                        $bentrokFile = true;

                        break;
                    }
                }

                if ($bentrokDatabase || $bentrokFile) {
                    $errors[] =
                        "Baris {$line}: jadwal petugas ".
                        'bentrok dengan jadwal lain.';

                    continue;
                }

                $validRows[] = [
                    'line' => $line,
                    'user_id' => $petugas->id,
                    'jpl_id' => $jpl->id,
                    'shift_id' => $shift->id,
                    'tanggal_dinas' => $tanggal,
                    'jam_mulai' => $mulai,
                    'jam_selesai' => $selesai,
                    'jam_mulai_input' => $jamMulaiCsv,
                    'jam_selesai_input' => $jamSelesaiCsv,
                    'is_pengganti' => $isPengganti === '1',
                    'user_digantikan_id' => $digantikan?->id,
                ];
            }

            if ($errors !== []) {
                if ($previewOnly) {
                    return $this->csvImportPreviewResponse($errors, $totalRows, count($validRows));
                }

                return back()->with('import_errors', $errors);
            }

            if ($validRows === []) {
                if ($previewOnly) {
                    return response()->json([
                        'message' => 'Tidak ada baris data yang dapat diimpor.',
                        'valid' => false,
                        'total_count' => $totalRows,
                        'valid_count' => 0,
                        'failed_count' => $totalRows,
                        'errors' => ['Tidak ada baris data yang dapat diimpor.'],
                    ], 422);
                }

                return back()->with(
                    'error',
                    'Tidak ada baris data yang dapat diimpor.'
                );
            }

            if ($previewOnly) {
                return response()->json([
                    'valid' => true,
                    'total_count' => $totalRows,
                    'valid_count' => count($validRows),
                    'failed_count' => 0,
                    'errors' => [],
                    'row_errors' => [],
                ]);
            }

            DB::transaction(function () use ($validRows, $activityLogService): void {
                $jplIds = collect($validRows)
                    ->pluck('jpl_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values();

                $lockedJpls = Jpl::query()
                    ->whereIn('id', $jplIds->all())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id', 'is_active'])
                    ->keyBy('id');

                if ($lockedJpls->count() !== $jplIds->count()) {
                    throw ValidationException::withMessages([
                        'import' => 'Satu atau lebih JPL sudah tidak tersedia. Unggah ulang file setelah memperbaiki datanya.',
                    ]);
                }

                foreach ($lockedJpls as $lockedJpl) {
                    if (! $lockedJpl->is_active) {
                        throw ValidationException::withMessages([
                            'import' => 'JPL pada file sudah tidak aktif. Unggah ulang file setelah memperbaiki datanya.',
                        ]);
                    }
                }

                $userIds = collect($validRows)
                    ->flatMap(fn ($row) => [
                        $row['user_id'],
                        $row['user_digantikan_id'],
                    ])
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values();

                $lockedUsers = User::query()
                    ->whereIn('id', $userIds)
                    ->where('is_active', true)
                    ->whereHas('role', fn ($query) => $query->where('name', 'pjl'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id'])
                    ->keyBy('id');

                if ($lockedUsers->count() !== $userIds->count()) {
                    throw ValidationException::withMessages([
                        'import' => 'Petugas pada file sudah tidak aktif atau bukan PJL. Unggah ulang file setelah memperbaiki datanya.',
                    ]);
                }

                $snapshotService = app(DinasJadwalSnapshotService::class);

                foreach ($validRows as $row) {
                    $shift = Shift::query()
                        ->whereKey($row['shift_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $shift) {
                        throw ValidationException::withMessages([
                            'import' => "Baris {$row['line']}: shift sudah tidak tersedia. Unggah ulang file setelah memperbaiki datanya.",
                        ]);
                    }

                    [$mulai, $selesai] = $this->calculateSchedule(
                        $row['tanggal_dinas'],
                        $shift
                    );

                    if (
                        ($row['jam_mulai_input'] !== ''
                            && $row['jam_mulai_input'] !== $mulai->format('H:i:s'))
                        || ($row['jam_selesai_input'] !== ''
                            && $row['jam_selesai_input'] !== $selesai->format('H:i:s'))
                    ) {
                        throw ValidationException::withMessages([
                            'import' => "Baris {$row['line']}: jam tidak lagi sesuai dengan konfigurasi shift. Unggah ulang file.",
                        ]);
                    }

                    try {
                        $this->ensureNoConflict(
                            (int) $row['user_id'],
                            $mulai,
                            $selesai
                        );
                    } catch (ValidationException $exception) {
                        $message = collect($exception->errors())
                            ->flatten()
                            ->first();

                        throw ValidationException::withMessages([
                            'import' => "Baris {$row['line']}: {$message}",
                        ]);
                    }

                    $dinas = Dinas::create([
                        'user_id' => $row['user_id'],
                        'jpl_id' => $row['jpl_id'],
                        'shift_id' => $row['shift_id'],
                        'tanggal_dinas' => $row['tanggal_dinas'],
                        'jam_mulai' => $mulai,
                        'jam_selesai' => $selesai,
                        'status' => $this->tentukanStatus($mulai, $selesai),
                        'is_pengganti' => $row['is_pengganti'],
                        'user_digantikan_id' => $row['user_digantikan_id'],
                    ]);

                    $snapshotService->resolveAndStore((int) $dinas->id);
                }

                $activityLogService->log(
                    'import',
                    'dinas',
                    'Impor jadwal Dinas berhasil diproses.',
                    null,
                    null,
                    [
                        'imported_count' => count($validRows),
                        'failed_count' => 0,
                    ],
                );
            }, 3);

            return redirect()
                ->route('admin.dinas.index')
                ->with(
                    'success',
                    count($validRows).
                    ' jadwal dinas berhasil diimpor.'
                );
        } catch (ValidationException $exception) {
            if ($previewOnly) {
                return response()->json([
                    'valid' => false,
                    'errors' => collect($exception->errors())->flatten()->values(),
                ], 422);
            }

            return back()->with(
                'import_errors',
                collect($exception->errors())->flatten()->all()
            );
        } catch (Throwable $e) {
            report($e);

            if ($previewOnly) {
                return response()->json([
                    'message' => 'Validasi file gagal diproses. Periksa format file dan coba kembali.',
                    'valid' => false,
                    'errors' => ['Validasi file gagal diproses. Periksa format file dan coba kembali.'],
                ], 422);
            }

            return back()->with(
                'error',
                'Impor gagal diproses. Periksa format file '.
                'dan log aplikasi.'
            );
        } finally {
            fclose($handle);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DETERMINE INITIAL STATUS
    |--------------------------------------------------------------------------
    */

    private function tentukanStatus(
        Carbon $mulai,
        Carbon $selesai
    ): string {
        $now = now();

        if ($selesai->lessThanOrEqualTo($now)) {
            return 'selesai';
        }

        if ($mulai->lessThanOrEqualTo($now)) {
            return 'berlangsung';
        }

        return 'dijadwalkan';
    }

    /*
    |--------------------------------------------------------------------------
    | CSV HEADERS
    |--------------------------------------------------------------------------
    */

    private function csvHeaders(): array
    {
        return [
            'nip',
            'kode_jpl',
            'id_shift',
            'tanggal_dinas',
            'jam_mulai',
            'jam_selesai',
            'is_pengganti',
            'nip_digantikan',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE DATE
    |--------------------------------------------------------------------------
    */

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat(
                    '!'.$format,
                    $value
                );

                if (
                    $date &&
                    $date->format($format) === $value
                ) {
                    return $date->format('Y-m-d');
                }
            } catch (Throwable) {
                // Coba format tanggal berikutnya.
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND ACTIVE PJL
    |--------------------------------------------------------------------------
    */

    private function findActivePjl(int $userId): User
    {
        return User::whereHas(
            'role',
            fn ($q) => $q->where('name', 'pjl')
        )
            ->where('is_active', true)
            ->findOrFail($userId);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE SCHEDULE
    |--------------------------------------------------------------------------
    */

    private function validateSchedule(Request $request): array
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'jpl_id' => [
                'required',
                'integer',
                'exists:jpl,id',
            ],
            'shift_id' => [
                'required',
                'integer',
                'exists:shifts,id',
            ],
            'tanggal_dinas' => [
                'required',
                'date_format:Y-m-d',
            ],
            'is_pengganti' => [
                'nullable',
                'boolean',
            ],
            'user_digantikan_id' => [
                'nullable',
                'required_if:is_pengganti,1',
                'integer',
                'different:user_id',
                'exists:users,id',
            ],
        ]);

        if ($request->boolean('is_pengganti')) {
            $pengganti = User::whereHas(
                'role',
                fn ($q) => $q->where('name', 'pjl')
            )
                ->where('is_active', true)
                ->whereKey(
                    $validated['user_digantikan_id'] ?? null
                )
                ->exists();

            if (! $pengganti) {
                throw ValidationException::withMessages([
                    'user_digantikan_id' => 'Petugas yang digantikan harus '.
                        'merupakan PJL aktif.',
                ]);
            }
        }

        return $validated;
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATE SCHEDULE FROM SHIFT
    |--------------------------------------------------------------------------
    */

    private function calculateSchedule(
        string $tanggal,
        Shift $shift
    ): array {
        $mulai = Carbon::parse(
            $tanggal.' '.$shift->jam_mulai
        );

        $selesai = Carbon::parse(
            $tanggal.' '.$shift->jam_selesai
        );

        // Mendukung shift lintas tengah malam.
        if ($selesai->lessThanOrEqualTo($mulai)) {
            $selesai->addDay();
        }

        return [$mulai, $selesai];
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK SCHEDULE CONFLICT
    |--------------------------------------------------------------------------
    */

    private function ensureNoConflict(
        int $userId,
        Carbon $mulai,
        Carbon $selesai,
        ?int $excludeId = null
    ): void {
        $query = Dinas::where('user_id', $userId)
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'Petugas tersebut sudah memiliki '.
                    'jadwal dinas pada rentang waktu ini.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PROTECT OPERATIONAL HISTORY
    |--------------------------------------------------------------------------
    */

    private function ensureCanModify(Dinas $dina): void
    {
        if (
            $dina->catatanDinas()->exists() ||
            $dina->serahTerima()->exists() ||
            $dina->realisasiInventaris()->exists() ||
            $dina->realisasiKa()->exists()
        ) {
            throw ValidationException::withMessages([
                'dinas' => 'Jadwal tidak dapat diubah atau dihapus '.
                    'karena sudah memiliki riwayat operasional.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function activityValues(Dinas $dinas): array
    {
        return [
            'user_id' => $dinas->user_id,
            'jpl_id' => $dinas->jpl_id,
            'shift_id' => $dinas->shift_id,
            'tanggal_dinas' => $dinas->tanggal_dinas?->toDateString(),
            'jam_mulai' => $dinas->jam_mulai?->toDateTimeString(),
            'jam_selesai' => $dinas->jam_selesai?->toDateTimeString(),
            'status' => $dinas->status,
            'is_pengganti' => $dinas->is_pengganti,
            'user_digantikan_id' => $dinas->user_digantikan_id,
        ];
    }

    /**
     * @param  list<string>  $errors
     */
    private function csvImportPreviewResponse(array $errors, int $totalRows, int $validRows): JsonResponse
    {
        $rowErrors = [];

        foreach ($errors as $error) {
            if (preg_match('/^Baris (\d+):\s*(.*)$/u', $error, $matches) === 1) {
                $rowErrors[(int) $matches[1]] = $matches[2];
            }
        }

        return response()->json([
            'valid' => false,
            'total_count' => $totalRows,
            'valid_count' => $validRows,
            'failed_count' => max(0, $totalRows - $validRows),
            'errors' => array_values($errors),
            'row_errors' => $rowErrors,
        ], 422);
    }
}

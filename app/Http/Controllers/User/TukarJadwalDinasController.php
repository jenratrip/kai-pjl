<?php

namespace App\Http\Controllers\User;

use App\Exceptions\SwapExecutionException;
use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\PermintaanTukarJadwal;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\TukarJadwalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class TukarJadwalDinasController extends Controller
{
    public function __construct(
        protected TukarJadwalService $tukarJadwalService,
        protected ActivityLogService $activityLogService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    /**
     * Halaman utama permintaan tukar jadwal.
     */
    public function index(): View
    {
        $userId = auth()->id();

        $dinasAktif = $this->getDinasAktif();

        $dibuatOlehSaya = PermintaanTukarJadwal::with([
            'dinasPemohon.user',
            'dinasPemohon.jpl.resort',
            'dinasPemohon.shift',
            'dinasTujuan.user',
            'dinasTujuan.jpl.resort',
            'dinasTujuan.shift',
            'penerima',
        ])
            ->where('pemohon_id', $userId)
            ->latest('id')
            ->paginate(15, ['*'], 'dibuat_page');

        $dikirimKePadaSaya = PermintaanTukarJadwal::with([
            'dinasPemohon.user',
            'dinasPemohon.jpl.resort',
            'dinasPemohon.shift',
            'dinasTujuan.user',
            'dinasTujuan.jpl.resort',
            'dinasTujuan.shift',
            'pemohon',
        ])
            ->where('penerima_id', $userId)
            ->latest('id')
            ->paginate(15, ['*'], 'masuk_page');

        return view('user.jadwal-dinas.tukar-jadwal', compact(
            'dibuatOlehSaya',
            'dikirimKePadaSaya',
            'dinasAktif'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    /**
     * Mengambil partial modal detail permintaan.
     *
     * Admin dan superadmin hanya dapat memantau.
     * Pemohon dan penerima dapat melihat permintaan yang melibatkan mereka.
     */
    public function show(PermintaanTukarJadwal $permintaan): View
    {
        $this->authorizePemantauanAtauTerlibat($permintaan);

        $dinasAktif = $this->getDinasAktif();

        $permintaan->load([
            'dinasPemohon.user',
            'dinasPemohon.jpl.resort',
            'dinasPemohon.shift',
            'dinasTujuan.user',
            'dinasTujuan.jpl.resort',
            'dinasTujuan.shift',
            'pemohon',
            'penerima',
        ]);

        $canRespond = (int) auth()->id() === (int) $permintaan->penerima_id
            && $permintaan->isPending();

        $canCancel = (int) auth()->id() === (int) $permintaan->pemohon_id
            && $permintaan->canBeCancelled();

        return view('user.jadwal-dinas.partials.modal-detail', compact(
            'permintaan',
            'canRespond',
            'canCancel',
            'dinasAktif'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    /**
     * Mengambil partial modal pengajuan tukar jadwal.
     */
    public function create(): View
    {
        $dinasAktif = $this->getDinasAktif();

        $jadwalMilikSaya = Dinas::with([
            'user',
            'jpl.resort',
            'shift',
        ])
            ->where('user_id', auth()->id())
            ->where('status', 'dijadwalkan')
            ->orderBy('tanggal_dinas')
            ->orderBy('jam_mulai')
            ->get()
            ->filter(function (Dinas $dinas) {
                return $this->tukarJadwalService
                    ->isOwnScheduleEligibleForSwap(
                        $dinas,
                        (int) auth()->id()
                    );
            })
            ->values();

        return view('user.jadwal-dinas.partials.modal-create', compact(
            'jadwalMilikSaya',
            'dinasAktif'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH RECIPIENT
    |--------------------------------------------------------------------------
    */

    /**
     * Pencarian calon penerima berdasarkan nama atau NIP.
     */
    public function cariPenerima(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('q', ''));

        if (mb_strlen($keyword) < 2) {
            return response()->json([
                'data' => [],
            ]);
        }

        $petugas = User::query()
            ->whereHas('role', function ($query) {
                $query->where('name', 'pjl');
            })
            ->where('is_active', true)
            ->where('id', '!=', auth()->id())
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('nip', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'nip']);

        return response()->json([
            'data' => $petugas->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip,
            ])->values(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RECIPIENT SCHEDULES
    |--------------------------------------------------------------------------
    */

    /**
     * Mengambil jadwal milik calon penerima yang layak ditukar.
     */
    public function jadwalPenerima(
        Dinas $dinasPemohon,
        User $penerima
    ): JsonResponse {
        $userPemohon = auth()->user();

        abort_unless(
            (int) $dinasPemohon->user_id === (int) $userPemohon->id,
            403,
            'Jadwal pemohon bukan milik Anda.'
        );

        abort_unless(
            $this->tukarJadwalService->isOwnScheduleEligibleForSwap(
                $dinasPemohon,
                (int) $userPemohon->id
            ),
            422,
            'Jadwal Anda sudah tidak memenuhi syarat untuk ditukar.'
        );

        abort_unless(
            (int) $penerima->id !== (int) $userPemohon->id,
            422,
            'Tidak dapat memilih diri sendiri sebagai penerima.'
        );

        $penerima->loadMissing('role');

        abort_unless(
            $penerima->role?->name === 'pjl',
            422,
            'Penerima harus memiliki role PJL.'
        );

        abort_unless(
            (bool) $penerima->is_active,
            422,
            'Akun penerima tidak aktif.'
        );

        $jadwalPenerima = Dinas::query()
            ->with([
                'user',
                'jpl.resort',
                'shift',
            ])
            ->where('user_id', $penerima->id)
            ->where('status', 'dijadwalkan')
            ->orderBy('tanggal_dinas')
            ->orderBy('jam_mulai')
            ->get();

        $jadwalLayak = $jadwalPenerima
            ->filter(function (Dinas $dinasTujuan) use (
                $dinasPemohon,
                $userPemohon,
                $penerima
            ) {
                $validation = $this->tukarJadwalService
                    ->validateSwapRequest(
                        $dinasPemohon,
                        $dinasTujuan,
                        (int) $userPemohon->id,
                        (int) $penerima->id
                    );

                return (bool) ($validation['valid'] ?? false);
            })
            ->map(function (Dinas $dinas) {
                return [
                    'id' => $dinas->id,
                    'tanggal_dinas' => $dinas->tanggal_dinas?->toDateString(),
                    'jam_mulai' => $dinas->jam_mulai,
                    'jam_selesai' => $dinas->jam_selesai,
                    'status' => $dinas->status,
                    'jpl' => [
                        'id' => $dinas->jpl?->id,
                        'kode_jpl' => $dinas->jpl?->kode_jpl,
                        'nama_jpl' => $dinas->jpl?->nama_jpl,
                    ],
                    'resort' => [
                        'id' => $dinas->jpl?->resort?->id,
                        'nama_resort' => $dinas->jpl?->resort?->nama_resort,
                    ],
                    'shift' => [
                        'id' => $dinas->shift?->id,
                        'nama_shift' => $dinas->shift?->nama_shift,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'data' => $jadwalLayak,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    /**
     * Membuat permintaan tukar jadwal.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'dinas_pemohon_id' => [
                'required',
                'integer',
                'exists:dinas,id',
            ],
            'dinas_tujuan_id' => [
                'required',
                'integer',
                'exists:dinas,id',
                'different:dinas_pemohon_id',
            ],
            'penerima_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'alasan_pemohon' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ], [
            'dinas_pemohon_id.required' => 'Jadwal milik Anda wajib dipilih.',
            'dinas_pemohon_id.exists' => 'Jadwal tidak ditemukan.',
            'dinas_tujuan_id.required' => 'Jadwal tujuan wajib dipilih.',
            'dinas_tujuan_id.exists' => 'Jadwal tujuan tidak ditemukan.',
            'dinas_tujuan_id.different' => 'Jadwal tujuan harus berbeda dengan jadwal Anda.',
            'penerima_id.required' => 'Penerima wajib dipilih.',
            'penerima_id.exists' => 'Penerima tidak ditemukan.',
            'alasan_pemohon.required' => 'Alasan tukar jadwal wajib diisi.',
            'alasan_pemohon.min' => 'Alasan minimal 10 karakter.',
            'alasan_pemohon.max' => 'Alasan maksimal 1000 karakter.',
        ]);

        try {
            $permintaan = DB::transaction(function () use ($validated) {
                $userPemohon = auth()->user();

                $idPemohon = (int) $validated['dinas_pemohon_id'];
                $idTujuan = (int) $validated['dinas_tujuan_id'];

                /*
                 * Lock kedua jadwal berdasarkan urutan ID
                 * untuk mengurangi risiko deadlock.
                 */
                $ids = [$idPemohon, $idTujuan];
                sort($ids);

                $dinas1 = Dinas::query()
                    ->lockForUpdate()
                    ->find($ids[0]);

                $dinas2 = Dinas::query()
                    ->lockForUpdate()
                    ->find($ids[1]);

                abort_unless(
                    $dinas1 !== null && $dinas2 !== null,
                    404,
                    'Salah satu atau kedua jadwal tidak ditemukan.'
                );

                $dinasPemohon = (int) $dinas1->id === $idPemohon
                    ? $dinas1
                    : $dinas2;

                $dinasTujuan = (int) $dinas1->id === $idTujuan
                    ? $dinas1
                    : $dinas2;

                abort_unless(
                    (int) $dinasPemohon->user_id === (int) $userPemohon->id,
                    403,
                    'Jadwal pemohon bukan milik Anda.'
                );

                $penerima = User::query()
                    ->with('role')
                    ->find($validated['penerima_id']);

                abort_unless(
                    $penerima !== null,
                    404,
                    'Penerima tidak ditemukan.'
                );

                abort_unless(
                    $penerima->role?->name === 'pjl',
                    422,
                    'Penerima harus memiliki role PJL.'
                );

                abort_unless(
                    (bool) $penerima->is_active,
                    422,
                    'Penerima tidak aktif.'
                );

                abort_unless(
                    (int) $penerima->id !== (int) $userPemohon->id,
                    422,
                    'Tidak dapat mengajukan tukar jadwal dengan diri sendiri.'
                );

                abort_unless(
                    (int) $dinasTujuan->user_id === (int) $penerima->id,
                    422,
                    'Jadwal tujuan bukan milik penerima.'
                );

                $validationResult = $this->tukarJadwalService
                    ->validateSwapRequest(
                        $dinasPemohon,
                        $dinasTujuan,
                        (int) $userPemohon->id,
                        (int) $penerima->id
                    );

                abort_unless(
                    $validationResult['valid'] ?? false,
                    422,
                    $validationResult['message']
                        ?? 'Permintaan tukar jadwal tidak valid.'
                );

                /*
                 * Muat relasi untuk kebutuhan snapshot historis.
                 */
                $dinasPemohon->loadMissing([
                    'jpl.resort',
                    'shift',
                ]);

                $dinasTujuan->loadMissing([
                    'jpl.resort',
                    'shift',
                ]);

                $dinasPemohonJpl = $dinasPemohon->jpl;
                $dinasPemohonResort = $dinasPemohonJpl?->resort;
                $dinasPemohonShift = $dinasPemohon->shift;

                $dinasTujuanJpl = $dinasTujuan->jpl;
                $dinasTujuanResort = $dinasTujuanJpl?->resort;
                $dinasTujuanShift = $dinasTujuan->shift;

                $snapshotPemohon = [
                    'id' => $dinasPemohon->id,
                    'tanggal_dinas' => $dinasPemohon->tanggal_dinas?->toDateString(),
                    'jam_mulai' => $dinasPemohon->jam_mulai?->toIso8601String(),
                    'jam_selesai' => $dinasPemohon->jam_selesai?->toIso8601String(),
                    'jpl' => [
                        'id' => $dinasPemohonJpl?->id,
                        'kode_jpl' => $dinasPemohonJpl?->kode_jpl,
                        'nama_jpl' => $dinasPemohonJpl?->nama_jpl,
                    ],
                    'resort' => [
                        'id' => $dinasPemohonResort?->id,
                        'nama_resort' => $dinasPemohonResort?->nama_resort,
                    ],
                    'shift' => [
                        'id' => $dinasPemohonShift?->id,
                        'nama_shift' => $dinasPemohonShift?->nama_shift,
                    ],
                ];

                $snapshotTujuan = [
                    'id' => $dinasTujuan->id,
                    'tanggal_dinas' => $dinasTujuan->tanggal_dinas?->toDateString(),
                    'jam_mulai' => $dinasTujuan->jam_mulai?->toIso8601String(),
                    'jam_selesai' => $dinasTujuan->jam_selesai?->toIso8601String(),
                    'jpl' => [
                        'id' => $dinasTujuanJpl?->id,
                        'kode_jpl' => $dinasTujuanJpl?->kode_jpl,
                        'nama_jpl' => $dinasTujuanJpl?->nama_jpl,
                    ],
                    'resort' => [
                        'id' => $dinasTujuanResort?->id,
                        'nama_resort' => $dinasTujuanResort?->nama_resort,
                    ],
                    'shift' => [
                        'id' => $dinasTujuanShift?->id,
                        'nama_shift' => $dinasTujuanShift?->nama_shift,
                    ],
                ];

                $permintaan = PermintaanTukarJadwal::create([
                    'dinas_pemohon_id' => $dinasPemohon->id,
                    'dinas_tujuan_id' => $dinasTujuan->id,

                    'pemohon_id' => $userPemohon->id,
                    'pemohon_name' => $userPemohon->name,
                    'pemohon_nip' => $userPemohon->nip,

                    'penerima_id' => $penerima->id,
                    'penerima_name' => $penerima->name,
                    'penerima_nip' => $penerima->nip,

                    'alasan_pemohon' => trim($validated['alasan_pemohon']),

                    'dinas_pemohon_snapshot' => $snapshotPemohon,
                    'dinas_tujuan_snapshot' => $snapshotTujuan,
                ]);

                $permintaan->status = PermintaanTukarJadwal::STATUS_MENUNGGU;
                $permintaan->diajukan_pada = now();
                $permintaan->save();

                $this->activityLogService->log(
                    'create',
                    'tukar_jadwal',
                    'Mengajukan permintaan tukar jadwal.',
                    $permintaan,
                    null,
                    [
                        'pemohon_id' => $permintaan->pemohon_id,
                        'penerima_id' => $permintaan->penerima_id,
                        'dinas_pemohon_id' => $permintaan->dinas_pemohon_id,
                        'dinas_tujuan_id' => $permintaan->dinas_tujuan_id,
                        'status' => $permintaan->status,
                    ]
                );

                return $permintaan;
            });

            $pesan = 'Permintaan tukar jadwal berhasil dibuat. Menunggu persetujuan penerima.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $pesan,
                    'permintaan_id' => $permintaan->id,
                ]);
            }

            return redirect()
                ->route('user.tukar-jadwal-dinas.show', $permintaan->id)
                ->with('success', $pesan);

        } catch (ValidationException $e) {
            throw $e;

        } catch (HttpExceptionInterface $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }

            return back()
                ->withErrors([
                    'permintaan' => $e->getMessage(),
                ])
                ->withInput();

        } catch (Throwable $e) {
            Log::error('Gagal membuat permintaan tukar jadwal.', [
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            $pesan = 'Terjadi kesalahan saat membuat permintaan tukar jadwal. Silakan coba kembali.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $pesan,
                ], 500);
            }

            return back()
                ->withErrors([
                    'permintaan' => $pesan,
                ])
                ->withInput();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RESPOND
    |--------------------------------------------------------------------------
    */

    /**
     * Persetujuan atau penolakan permintaan.
     */
    public function respond(
        Request $request,
        PermintaanTukarJadwal $permintaan
    ): RedirectResponse|JsonResponse {
        abort_unless(
            (int) auth()->id() === (int) $permintaan->penerima_id,
            403,
            'Hanya penerima yang dapat merespons permintaan ini.'
        );

        abort_unless(
            $permintaan->isPending(),
            422,
            'Permintaan tidak lagi dalam status menunggu.'
        );

        $validated = $request->validate([
            'action' => [
                'required',
                'in:approve,reject',
            ],
            'alasan_penerima' => [
                'required_if:action,reject',
                'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
        ], [
            'action.required' => 'Aksi wajib dipilih.',
            'action.in' => 'Aksi tidak valid.',
            'alasan_penerima.required_if' => 'Alasan penolakan wajib diisi.',
            'alasan_penerima.min' => 'Alasan minimal 5 karakter.',
            'alasan_penerima.max' => 'Alasan maksimal 1000 karakter.',
        ]);

        try {
            DB::transaction(function () use ($permintaan, $validated) {
                $permintaanFresh = PermintaanTukarJadwal::query()
                    ->lockForUpdate()
                    ->findOrFail($permintaan->id);

                abort_unless(
                    $permintaanFresh->isPending(),
                    422,
                    'Permintaan tidak lagi dalam status menunggu.'
                );

                abort_unless(
                    (int) auth()->id() === (int) $permintaanFresh->penerima_id,
                    403,
                    'Anda bukan penerima permintaan ini.'
                );

                if ($validated['action'] === 'approve') {
                    /*
                     * Validasi awal sebelum eksekusi.
                     * Validasi akan diulang oleh service ketika
                     * kedua jadwal sudah dikunci.
                     */
                    $approvalValidation = $this->tukarJadwalService
                        ->validateSwapApproval($permintaanFresh);

                    if (! ($approvalValidation['valid'] ?? false)) {
                        throw new SwapExecutionException(
                            $approvalValidation['message']
                                ?? 'Validasi persetujuan tukar jadwal tidak terpenuhi.'
                        );
                    }

                    $swapResult = $this->tukarJadwalService
                        ->executeSwap($permintaanFresh);

                    if (! ($swapResult['success'] ?? false)) {
                        /*
                         * Hanya kegagalan bisnis yang dicatat
                         * sebagai status permintaan GAGAL.
                         *
                         * Kesalahan sistem harus menyebabkan rollback
                         * dan ditangani sebagai error 500.
                         */
                        if ($swapResult['is_swap_failure'] ?? false) {
                            throw new SwapExecutionException(
                                $swapResult['error']
                                    ?? $swapResult['message']
                                    ?? 'Pertukaran jadwal tidak dapat dilakukan.'
                            );
                        }

                        throw new \RuntimeException(
                            $swapResult['error']
                                ?? $swapResult['message']
                                ?? 'Terjadi kesalahan sistem saat menukar jadwal.'
                        );
                    }

                    $permintaanFresh->refresh();

                    if (! $permintaanFresh->isSuccessful()) {
                        throw new \LogicException(
                            'Service berhasil mengeksekusi swap tetapi status permintaan belum berhasil diperbarui.'
                        );
                    }

                    $this->activityLogService->log(
                        'approve',
                        'tukar_jadwal',
                        'Penerima menyetujui dan pertukaran jadwal berhasil dilakukan.',
                        $permintaanFresh,
                        [
                            'status' => PermintaanTukarJadwal::STATUS_MENUNGGU,
                        ],
                        [
                            'status' => $permintaanFresh->status,
                            'dijawab_pada' => $permintaanFresh->dijawab_pada,
                        ]
                    );

                } else {
                    $permintaanFresh->status = PermintaanTukarJadwal::STATUS_DITOLAK;
                    $permintaanFresh->alasan_penerima = trim(
                        $validated['alasan_penerima']
                    );
                    $permintaanFresh->dijawab_pada = now();
                    $permintaanFresh->selesai_pada = now();
                    $permintaanFresh->save();

                    $this->activityLogService->log(
                        'reject',
                        'tukar_jadwal',
                        'Penerima menolak permintaan tukar jadwal.',
                        $permintaanFresh,
                        [
                            'status' => PermintaanTukarJadwal::STATUS_MENUNGGU,
                        ],
                        [
                            'status' => $permintaanFresh->status,
                            'alasan_penerima' => $permintaanFresh->alasan_penerima,
                        ]
                    );
                }
            });

            $permintaan->refresh();

            $successMsg = $validated['action'] === 'approve'
                ? 'Permintaan tukar jadwal berhasil disetujui. Jadwal telah ditukar.'
                : 'Permintaan tukar jadwal berhasil ditolak.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $successMsg,
                    'status' => $permintaan->status,
                ]);
            }

            return redirect()
                ->route('user.tukar-jadwal-dinas.show', $permintaan->id)
                ->with('success', $successMsg);

        } catch (SwapExecutionException $e) {
            /*
             * Transaksi approval sudah rollback sebelum masuk ke sini.
             * Catat kegagalan bisnis dalam transaksi terpisah.
             */
            $failureResult = $this->catatKegagalanApproval(
                $permintaan->id,
                $e->getMessage()
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $failureResult['message'],
                    'status' => $failureResult['status'],
                ], 422);
            }

            return back()->withErrors([
                'permintaan' => $failureResult['message'],
            ]);

        } catch (HttpExceptionInterface $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }

            return back()->withErrors([
                'permintaan' => $e->getMessage(),
            ]);

        } catch (Throwable $e) {
            Log::error('Gagal memproses respons tukar jadwal.', [
                'permintaan_id' => $permintaan->id,
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            $pesan = 'Terjadi kesalahan sistem saat memproses permintaan. Silakan coba kembali.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $pesan,
                ], 500);
            }

            return back()->withErrors([
                'permintaan' => $pesan,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RECORD APPROVAL FAILURE
    |--------------------------------------------------------------------------
    */

    /**
     * Mencatat kegagalan approval setelah transaksi swap rollback.
     *
     * @return array{updated: bool, status: string, message: string}
     */
    private function catatKegagalanApproval(
        int $permintaanId,
        string $alasan
    ): array {
        try {
            return DB::transaction(function () use ($permintaanId, $alasan) {
                $permintaan = PermintaanTukarJadwal::query()
                    ->lockForUpdate()
                    ->find($permintaanId);

                if (! $permintaan) {
                    return [
                        'updated' => false,
                        'status' => 'tidak_ditemukan',
                        'message' => 'Permintaan tidak ditemukan.',
                    ];
                }

                if (! $permintaan->isPending()) {
                    return [
                        'updated' => false,
                        'status' => $permintaan->status,
                        'message' => 'Permintaan sudah diproses sebelumnya. Status saat ini: '
                            . $permintaan->status . '.',
                    ];
                }

                $permintaan->status = PermintaanTukarJadwal::STATUS_GAGAL;
                $permintaan->alasan_gagal = $alasan;
                $permintaan->dijawab_pada = now();
                $permintaan->selesai_pada = now();
                $permintaan->save();

                $this->activityLogService->log(
                    'approve_failed',
                    'tukar_jadwal',
                    'Persetujuan gagal karena validasi atau eksekusi pertukaran jadwal tidak terpenuhi.',
                    $permintaan,
                    [
                        'status' => PermintaanTukarJadwal::STATUS_MENUNGGU,
                    ],
                    [
                        'status' => PermintaanTukarJadwal::STATUS_GAGAL,
                        'alasan_gagal' => $permintaan->alasan_gagal,
                    ]
                );

                return [
                    'updated' => true,
                    'status' => PermintaanTukarJadwal::STATUS_GAGAL,
                    'message' => 'Pertukaran jadwal gagal: ' . $alasan,
                ];
            });

        } catch (Throwable $e) {
            Log::error('Gagal mencatat kegagalan approval tukar jadwal.', [
                'permintaan_id' => $permintaanId,
                'exception' => $e,
            ]);

            return [
                'updated' => false,
                'status' => 'error',
                'message' => 'Pertukaran jadwal gagal, tetapi pencatatan status gagal mengalami kendala. Hubungi administrator.',
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

    /**
     * Pembatalan permintaan oleh pemohon.
     */
    public function cancel(
        Request $request,
        PermintaanTukarJadwal $permintaan
    ): RedirectResponse|JsonResponse {
        abort_unless(
            (int) auth()->id() === (int) $permintaan->pemohon_id,
            403,
            'Hanya pemohon yang dapat membatalkan permintaan ini.'
        );

        abort_unless(
            $permintaan->canBeCancelled(),
            422,
            'Hanya permintaan dengan status menunggu yang dapat dibatalkan.'
        );

        try {
            DB::transaction(function () use ($permintaan) {
                $permintaanFresh = PermintaanTukarJadwal::query()
                    ->lockForUpdate()
                    ->findOrFail($permintaan->id);

                abort_unless(
                    $permintaanFresh->isPending(),
                    422,
                    'Permintaan tidak lagi dalam status menunggu.'
                );

                abort_unless(
                    (int) auth()->id() === (int) $permintaanFresh->pemohon_id,
                    403,
                    'Anda bukan pemohon permintaan ini.'
                );

                $permintaanFresh->status = PermintaanTukarJadwal::STATUS_DIBATALKAN;
                $permintaanFresh->dijawab_pada = now();
                $permintaanFresh->selesai_pada = now();
                $permintaanFresh->save();

                $this->activityLogService->log(
                    'cancel',
                    'tukar_jadwal',
                    'Pemohon membatalkan permintaan tukar jadwal.',
                    $permintaanFresh,
                    [
                        'status' => PermintaanTukarJadwal::STATUS_MENUNGGU,
                    ],
                    [
                        'status' => $permintaanFresh->status,
                        'dibatalkan_pada' => $permintaanFresh->selesai_pada,
                    ]
                );
            });

        } catch (HttpExceptionInterface $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }

            return back()->withErrors([
                'permintaan' => $e->getMessage(),
            ]);

        } catch (Throwable $e) {
            Log::error('Gagal membatalkan permintaan tukar jadwal.', [
                'permintaan_id' => $permintaan->id,
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            $pesan = 'Terjadi kesalahan saat membatalkan permintaan. Silakan coba kembali.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $pesan,
                ], 500);
            }

            return back()->withErrors([
                'permintaan' => $pesan,
            ]);
        }

        $pesan = 'Permintaan tukar jadwal berhasil dibatalkan.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $pesan,
                'status' => PermintaanTukarJadwal::STATUS_DIBATALKAN,
            ]);
        }

        return redirect()
            ->route('user.tukar-jadwal-dinas')
            ->with('success', $pesan);
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY
    |--------------------------------------------------------------------------
    */

    /**
     * Riwayat permintaan tukar jadwal.
     */
    public function history(Request $request): View
    {
        $userId = auth()->id();

        $dinasAktif = $this->getDinasAktif();

        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:menunggu,ditolak,dibatalkan,berhasil,gagal',
            ],
            'type' => [
                'nullable',
                'in:dibuat,diterima',
            ],
        ]);

        $baseQuery = PermintaanTukarJadwal::with([
            'dinasPemohon.user',
            'dinasPemohon.jpl.resort',
            'dinasPemohon.shift',
            'dinasTujuan.user',
            'dinasTujuan.jpl.resort',
            'dinasTujuan.shift',
            'pemohon',
            'penerima',
        ]);

        $dibuatQuery = clone $baseQuery;
        $diterimaQuery = clone $baseQuery;

        if (
            ! isset($validated['type'])
            || $validated['type'] === 'dibuat'
        ) {
            $dibuatQuery->where('pemohon_id', $userId);

            if (! empty($validated['status'])) {
                $dibuatQuery->where('status', $validated['status']);
            }

            $dibuat = $dibuatQuery
                ->latest('id')
                ->paginate(20, ['*'], 'dibuat_page');
        } else {
            $dibuat = collect();
        }

        if (
            ! isset($validated['type'])
            || $validated['type'] === 'diterima'
        ) {
            $diterimaQuery->where('penerima_id', $userId);

            if (! empty($validated['status'])) {
                $diterimaQuery->where('status', $validated['status']);
            }

            $diterima = $diterimaQuery
                ->latest('id')
                ->paginate(20, ['*'], 'diterima_page');
        } else {
            $diterima = collect();
        }

        return view('user.jadwal-dinas.history', compact(
            'dibuat',
            'diterima',
            'dinasAktif'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVE DUTY
    |--------------------------------------------------------------------------
    */

    /**
     * Mengambil data dinas aktif berdasarkan session.
     */
    private function getDinasAktif(): ?Dinas
    {
        if (! auth()->check()) {
            return null;
        }

        $dinasId = request()->session()->get('dinas_id');

        if (! is_numeric($dinasId) || (int) $dinasId <= 0) {
            return null;
        }

        return Dinas::with([
            'jpl.resort',
            'shift',
            'user',
            'userDigantikan',
        ])
            ->whereKey((int) $dinasId)
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHORIZATION
    |--------------------------------------------------------------------------
    */

    /**
     * Otorisasi akses detail permintaan.
     *
     * Admin dan superadmin hanya mendapatkan akses pemantauan.
     */
    private function authorizePemantauanAtauTerlibat(
        PermintaanTukarJadwal $permintaan
    ): void {
        $user = auth()->user();

        $isTerlibat = (int) $user->id === (int) $permintaan->pemohon_id
            || (int) $user->id === (int) $permintaan->penerima_id;

        $isAdminOrSuperadmin = in_array(
            $user->role?->name,
            ['admin', 'superadmin'],
            true
        );

        abort_unless(
            $isTerlibat || $isAdminOrSuperadmin,
            403,
            'Anda tidak memiliki akses ke permintaan ini.'
        );
    }
}

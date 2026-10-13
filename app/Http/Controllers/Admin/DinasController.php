<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Jpl;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
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
        $query = Dinas::with([
            'user',
            'userDigantikan',
            'jpl',
            'shift',
        ]);

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
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $this->validateSchedule($request);

        $petugas = $this->findActivePjl(
            $validated['user_id']
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

        $this->ensureNoConflict(
            $petugas->id,
            $mulai,
            $selesai
        );

        Dinas::create([
            'user_id' => $petugas->id,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => $validated['tanggal_dinas'],
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'status' => 'berlangsung',
            'is_pengganti' => $request->boolean(
                'is_pengganti'
            ),
            'user_digantikan_id' => $request->boolean(
                'is_pengganti'
            )
                ? $validated['user_digantikan_id']
                : null,
        ]);

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

    public function update(Request $request, Dinas $dina)
    {
        $this->ensureCanModify($dina);

        $validated = $this->validateSchedule($request);

        $petugas = $this->findActivePjl(
            $validated['user_id']
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

        $this->ensureNoConflict(
            $petugas->id,
            $mulai,
            $selesai,
            $dina->id
        );

        $dina->update([
            'user_id' => $petugas->id,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => $validated['tanggal_dinas'],
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'is_pengganti' => $request->boolean(
                'is_pengganti'
            ),
            'user_digantikan_id' => $request->boolean(
                'is_pengganti'
            )
                ? $validated['user_digantikan_id']
                : null,
        ]);

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

    public function destroy(Dinas $dina)
    {
        $this->ensureCanModify($dina);

        $dina->delete();

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

    public function export()
    {
        $rows = Dinas::with([
            'user',
            'userDigantikan',
            'jpl',
            'shift',
        ])
            ->orderBy('tanggal_dinas')
            ->orderBy('jam_mulai')
            ->get();

        $filename = 'jadwal-dinas-' .
            now()->format('Ymd-His') .
            '.csv';

        return response()->streamDownload(
            function () use ($rows) {
                $out = fopen('php://output', 'w');

                // BOM UTF-8 untuk kompatibilitas Excel.
                fwrite($out, "\xEF\xBB\xBF");

                fputcsv(
                    $out,
                    $this->csvHeaders(),
                    ';'
                );

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

                fputcsv(
                    $out,
                    $this->csvHeaders(),
                    ';'
                );

                // Baris contoh untuk memudahkan pengisian.
                fputcsv($out, [
                    '',
                    '',
                    '',
                    now()->format('Y-m-d'),
                    '',
                    '',
                    '0',
                    '',
                ], ';');

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

    public function import(Request $request)
    {
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
            return back()->with(
                'error',
                'File CSV tidak dapat dibaca.'
            );
        }

        try {
            $header = fgetcsv($handle, 0, ';');

            if ($header === false) {
                return back()->with(
                    'error',
                    'File CSV kosong.'
                );
            }

            // Hilangkan BOM UTF-8 (byte asli maupun yang salah decode).
            $header[0] = (string) $header[0];

            foreach (["\xEF\xBB\xBF", "\u{FEFF}", "ï»¿"] as $bom) {
                if (str_starts_with($header[0], $bom)) {
                    $header[0] = substr($header[0], strlen($bom));
                    break;
                }
            }

            $header = array_map(
                fn ($value) => strtolower(trim((string) $value)),
                $header
            );

            if ($header !== $this->csvHeaders()) {
                return back()->with(
                    'error',
                    'Header CSV tidak sesuai template. ' .
                    'Unduh template terbaru dan jangan ' .
                    'mengubah nama atau urutan 8 kolom.'
                );
            }

            $validRows = [];
            $errors = [];
            $line = 1;

            while (
                ($data = fgetcsv($handle, 0, ';')) !== false
            ) {
                $line++;

                // Lewati baris kosong.
                if (
                    count(array_filter(
                        $data,
                        fn ($value) =>
                            trim((string) $value) !== ''
                    )) === 0
                ) {
                    continue;
                }

                if (
                    count($data) !== count(
                        $this->csvHeaders()
                    )
                ) {
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

                $tanggal = $this->normalizeDate(
                    $tanggalRaw
                );

                if ($tanggal === null) {
                    $errors[] =
                        "Baris {$line}: tanggal harus " .
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
                    'nip' => [
                        'required',
                        'string',
                    ],
                    'kode_jpl' => [
                        'required',
                        'string',
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
                        "Baris {$line}: " .
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
                        fn ($q) =>
                            $q->where('name', 'pjl')
                    )
                    ->first();

                $jpl = Jpl::where(
                    'kode_jpl',
                    $kodeJpl
                )
                    ->where('is_active', true)
                    ->first();

                $shift = Shift::find($shiftId);

                if (!$petugas || !$jpl || !$shift) {
                    $errors[] =
                        "Baris {$line}: NIP petugas, kode " .
                        'JPL, atau shift tidak ditemukan ' .
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
                            fn ($q) =>
                                $q->where('name', 'pjl')
                        )
                        ->first();

                    if (
                        !$digantikan ||
                        $digantikan->id === $petugas->id
                    ) {
                        $errors[] =
                            "Baris {$line}: NIP petugas " .
                            'yang digantikan tidak valid.';
                        continue;
                    }
                } elseif ($nipDigantikan !== '') {
                    $errors[] =
                        "Baris {$line}: nip_digantikan " .
                        'harus kosong jika is_pengganti bernilai 0.';
                    continue;
                }

                [$mulai, $selesai] =
                    $this->calculateSchedule(
                        $tanggal,
                        $shift
                    );

                // Jam pada CSV harus sesuai dengan shift.
                if (
                    (
                        $jamMulaiCsv !== '' &&
                        $jamMulaiCsv !==
                            $mulai->format('H:i:s')
                    ) ||
                    (
                        $jamSelesaiCsv !== '' &&
                        $jamSelesaiCsv !==
                            $selesai->format('H:i:s')
                    )
                ) {
                    $errors[] =
                        "Baris {$line}: jam tidak sesuai " .
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
                        "Baris {$line}: jadwal petugas " .
                        'bentrok dengan jadwal lain.';
                    continue;
                }

                $validRows[] = [
                    'user_id' => $petugas->id,
                    'jpl_id' => $jpl->id,
                    'shift_id' => $shift->id,
                    'tanggal_dinas' => $tanggal,
                    'jam_mulai' => $mulai,
                    'jam_selesai' => $selesai,
                    'status' => 'berlangsung',
                    'is_pengganti' => $isPengganti === '1',
                    'user_digantikan_id' => $digantikan?->id,
                ];
            }

            // Impor bersifat all-or-nothing.
            if ($errors !== []) {
                return back()->with(
                    'import_errors',
                    $errors
                );
            }

            if ($validRows === []) {
                return back()->with(
                    'error',
                    'Tidak ada baris data yang dapat diimpor.'
                );
            }

            DB::transaction(function () use ($validRows) {
                foreach ($validRows as $row) {
                    Dinas::create($row);
                }
            });

            return redirect()
                ->route('admin.dinas.index')
                ->with(
                    'success',
                    count($validRows) .
                    ' jadwal dinas berhasil diimpor.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Impor gagal diproses. Periksa format file ' .
                'dan log aplikasi.'
            );
        } finally {
            fclose($handle);
        }
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

    private function normalizeDate(
        string $value
    ): ?string {
        $value = trim($value);

        $formats = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat(
                    '!' . $format,
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

    private function findActivePjl(
        int $userId
    ): User {
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

    private function validateSchedule(
        Request $request
    ): array {
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

            if (!$pengganti) {
                throw ValidationException::withMessages([
                    'user_digantikan_id' =>
                        'Petugas yang digantikan harus ' .
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
            $tanggal . ' ' . $shift->jam_mulai
        );

        $selesai = Carbon::parse(
            $tanggal . ' ' . $shift->jam_selesai
        );

        // Mendukung shift yang melewati tengah malam.
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
                'user_id' =>
                    'Petugas tersebut sudah memiliki ' .
                    'jadwal dinas pada rentang waktu ini.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PROTECT OPERATIONAL HISTORY
    |--------------------------------------------------------------------------
    */

    private function ensureCanModify(
        Dinas $dina
    ): void {
        if (
            $dina->catatanDinas()->exists() ||
            $dina->serahTerima()->exists() ||
            $dina->realisasiInventaris()->exists()
        ) {
            throw ValidationException::withMessages([
                'dinas' =>
                    'Jadwal tidak dapat diubah atau dihapus ' .
                    'karena sudah memiliki riwayat operasional.',
            ]);
        }
    }
}
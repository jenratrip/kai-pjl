<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CatatanDinas;
use App\Models\Dinas;
use App\Models\Inventaris;
use App\Models\JadwalKa;
use App\Models\RealisasiInventaris;
use App\Models\RealisasiKa;
use App\Models\SerahTerima;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SerahTerimaDinasController extends Controller
{
    /**
     * Mendapatkan dinas aktif milik user yang login.
     */
    private function dinasAktif(): ?Dinas
    {
        return Dinas::with([
            'user',
            'jpl.resort.wilayah',
            'shift',
        ])
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();
    }

    /**
     * Apakah dinas ini sudah punya serah terima yang disetujui admin.
     */
    private function dinasTerkunci(Dinas $dinas): bool
    {
        return SerahTerima::query()
            ->where('dinas_id', $dinas->id)
            ->whereIn('status_review', SerahTerima::STATUS_TERKUNCI)
            ->exists();
    }

    /**
     * Mengembalikan respons penolakan jika dinas sudah terkunci,
     * atau null jika masih boleh diubah.
     */
    private function tolakJikaTerkunci(Request $request, Dinas $dinas)
    {
        if (!$this->dinasTerkunci($dinas)) {
            return null;
        }

        $pesan = 'Serah terima dinas ini sudah disetujui admin. '
            . 'Data terkunci dan tidak dapat diubah.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $pesan,
            ], 422);
        }

        return back()->withErrors(['dinas' => $pesan]);
    }

    /**
     * Mencari akun PJL aktif berdasarkan nama atau NIP.
     */
    public function cariPenerima(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        if (mb_strlen($keyword) < 2) {
            return response()->json(['data' => []]);
        }

        $petugas = User::query()
            ->where('role_id', 1)
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

    /**
     * Halaman utama serah terima dinas.
     */
    public function index()
    {
        return $this->tampilkanHalaman($this->dinasAktif());
    }

    /**
     * Riwayat serah terima milik user yang login.
     */
    public function riwayat()
    {
        $riwayat = SerahTerima::with([
            'dinas.jpl',
            'dinas.shift',
            'penerima',
        ])
            ->milikUser(auth()->id())
            ->latest('waktu_serah_terima')
            ->latest('id')
            ->paginate(10);

        return view(
            'user.serahterimacatatan.riwayat',
            compact('riwayat')
        );
    }

    /**
     * Membuka data serah terima untuk diedit.
     * Data yang sudah disetujui dialihkan ke halaman lihat.
     */
    public function edit($serahTerima)
    {
        $data = SerahTerima::with([
            'dinas.user',
            'dinas.jpl.resort.wilayah',
            'dinas.shift',
            'penerima',
        ])
            ->milikUser(auth()->id())
            ->findOrFail($serahTerima);

        if ($data->isTerkunci()) {
            return redirect()->route(
                'user.serah-terima-dinas.show',
                $data
            );
        }

        return $this->tampilkanHalaman(
            $data->dinas,
            $data,
            true
        );
    }

    /**
     * Menampilkan detail serah terima yang sudah disetujui.
     */
    public function show($serahTerima)
    {
        $data = SerahTerima::with([
            'dinas.user',
            'dinas.jpl.resort.wilayah',
            'dinas.shift',
            'penerima',
        ])
            ->milikUser(auth()->id())
            ->findOrFail($serahTerima);

        if (!$data->isTerkunci()) {
            return redirect()->route(
                'user.serah-terima-dinas.edit',
                $data
            );
        }

        return $this->tampilkanHalaman(
            $data->dinas,
            $data,
            true,
            true
        );
    }

    /**
     * Menyiapkan data untuk halaman serah terima.
     */
    private function tampilkanHalaman(
        ?Dinas $dinas,
        ?SerahTerima $serahTerima = null,
        bool $modeEdit = false,
        bool $modeLihat = false
    ) {
        $dataRealisasiKA = collect();

        $ringkasanKA = [
            'batal' => [],
            'berjalan' => [],
            'belum' => [],
        ];

        $catatan = collect();
        $inventaris = collect();
        $realisasi = collect();

        if ($dinas) {
            $dataRealisasiKA = $this->ambilDataKA($dinas);

            $ringkasanKA = $this->buatRingkasanKA(
                $dataRealisasiKA
            );

            $catatan = CatatanDinas::with([
                'attachments',
                'user',
            ])
                ->where('dinas_id', $dinas->id)
                ->latest('waktu_catatan')
                ->get();

            $inventaris = Inventaris::query()
                ->where('is_active', true)
                ->where('jpl_id', $dinas->jpl_id)
                ->orderBy('nama_barang')
                ->get();

            $realisasi = RealisasiInventaris::query()
                ->where('dinas_id', $dinas->id)
                ->get()
                ->keyBy('inventaris_id');

            if (!$serahTerima) {
                $serahTerima = SerahTerima::with('penerima')
                    ->where('dinas_id', $dinas->id)
                    ->latest('id')
                    ->first();
            }
        }

        // Jika sudah disetujui admin, halaman apa pun hanya mode lihat.
        if ($serahTerima?->isTerkunci()) {
            $modeLihat = true;
        }

        return view('user.serahterimacatatan.index', [
            'dinasAktif' => $dinas,
            'dinas' => $dinas,
            'catatan' => $catatan,
            'inventaris' => $inventaris,
            'realisasi' => $realisasi,
            'serahTerima' => $serahTerima,
            'dataRealisasiKA' => $dataRealisasiKA,
            'ringkasanKA' => $ringkasanKA,
            'modeEdit' => $modeEdit,
            'modeLihat' => $modeLihat,
        ]);
    }

    /**
     * Mengelompokkan KA berdasarkan status realisasi.
     */
    private function buatRingkasanKA($dataRealisasiKA): array
    {
        $dataRealisasiKA = collect($dataRealisasiKA);

        return [
            'batal' => $dataRealisasiKA
                ->filter(fn ($ka) =>
                    strtoupper($ka['status'] ?? '') === 'BATAL'
                )
                ->values()
                ->all(),

            'berjalan' => $dataRealisasiKA
                ->filter(fn ($ka) =>
                    strtoupper($ka['status'] ?? '') === 'JALAN'
                )
                ->values()
                ->all(),

            'belum' => $dataRealisasiKA
                ->filter(fn ($ka) =>
                    strtoupper($ka['status'] ?? '') === 'BELUM'
                )
                ->values()
                ->all(),
        ];
    }

    /**
     * Mengubah daftar KA menjadi teks untuk kompatibilitas.
     */
    private function daftarKAKeTeks(array $daftar): string
    {
        if (empty($daftar)) {
            return 'Tidak ada';
        }

        return collect($daftar)
            ->map(function ($ka) {
                $nomor = trim((string) ($ka['nomor'] ?? '-'));
                $nama = trim((string) ($ka['nama'] ?? ''));

                if ($nomor === '' || $nomor === '-') {
                    return $nama !== '' && $nama !== '-'
                        ? $nama
                        : '-';
                }

                if ($nama !== '' && $nama !== '-') {
                    return "{$nomor} - {$nama}";
                }

                return $nomor;
            })
            ->unique()
            ->implode("\n");
    }

    /**
     * Mengambil jadwal KA dan realisasi milik dinas.
     */
    private function ambilDataKA(Dinas $dinas)
    {
        $jadwalQuery = JadwalKa::with('keretaApi')
            ->where('jpl_id', $dinas->jpl_id)
            ->where('is_active', true);

        $this->terapkanFilterJadwalBerlaku(
            $jadwalQuery,
            $dinas
        );

        $jadwalList = $jadwalQuery
            ->orderByRaw(
                'CASE WHEN tanggal_berlaku IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy('jam_lewat')
            ->orderBy('id')
            ->get()
            ->unique(fn ($jadwal) =>
                ($jadwal->kereta_api_id ?? 'tanpa-ka')
                . '|'
                . ($jadwal->jam_lewat ?? 'tanpa-jam')
            )
            ->values();

        $realisasiTerjadwal = RealisasiKa::with('keretaApi')
            ->where('dinas_id', $dinas->id)
            ->where('is_manual', false)
            ->orderByDesc('id')
            ->get()
            ->unique('jadwal_ka_id')
            ->keyBy('jadwal_ka_id');

        $dataKA = [];

        foreach ($jadwalList as $jadwal) {
            $realisasiKA = $realisasiTerjadwal->get($jadwal->id);

            $dataKA[] = [
                'id' => $realisasiKA?->id,
                'nomor' => $jadwal->keretaApi?->nomor_ka ?? '-',
                'nama' => $jadwal->keretaApi?->nama_ka ?? '-',
                'jadwal' => $this->formatJam($jadwal->jam_lewat),
                'aktual' => $this->formatJam(
                    $realisasiKA?->jam_aktual
                ),
                'status' => $this->formatStatus(
                    $realisasiKA?->status_lewat
                ),
                'semboyan21' => $this->checkValue(
                    $realisasiKA?->status_s21
                ),
                'genta' => $this->checkValue(
                    $realisasiKA?->status_genta
                ),
                'pintu' => $this->checkValue(
                    $realisasiKA?->status_pintu_perlintasan
                ),
                'semboyan35' => $this->checkValue(
                    $realisasiKA?->status_s35
                ),
                'keterangan' => $realisasiKA?->catatan ?? '',
                'jadwal_ka_id' => $jadwal->id,
                'is_manual' => false,
            ];
        }

        // KA manual tetap dimasukkan ke snapshot.
        $manualList = RealisasiKa::with('keretaApi')
            ->where('dinas_id', $dinas->id)
            ->where('is_manual', true)
            ->orderBy('id')
            ->get();

        foreach ($manualList as $realisasiKA) {
            $dataKA[] = [
                'id' => $realisasiKA->id,
                'nomor' => $realisasiKA->keretaApi?->nomor_ka ?? '-',
                'nama' => $realisasiKA->keretaApi?->nama_ka ?? '-',
                'jadwal' => '-',
                'aktual' => $this->formatJam(
                    $realisasiKA->jam_aktual
                ),
                'status' => $this->formatStatus(
                    $realisasiKA->status_lewat
                ),
                'semboyan21' => $this->checkValue(
                    $realisasiKA->status_s21
                ),
                'genta' => $this->checkValue(
                    $realisasiKA->status_genta
                ),
                'pintu' => $this->checkValue(
                    $realisasiKA->status_pintu_perlintasan
                ),
                'semboyan35' => $this->checkValue(
                    $realisasiKA->status_s35
                ),
                'keterangan' => $realisasiKA->catatan ?? '',
                'jadwal_ka_id' => null,
                'is_manual' => true,
            ];
        }

        return collect($dataKA)->values();
    }

    /**
     * Memfilter jadwal berdasarkan tanggal atau hari berlakunya.
     */
    private function terapkanFilterJadwalBerlaku(
        $query,
        Dinas $dinas
    ): void {
        if (!$dinas->tanggal_dinas) {
            $query->whereRaw('1 = 0');

            return;
        }

        $tanggalDinas = Carbon::parse($dinas->tanggal_dinas);

        $namaHari = mb_strtolower(
            $this->namaHariIndonesia($tanggalDinas)
        );

        $query->where(function ($q) use (
            $tanggalDinas,
            $namaHari
        ) {
            $q->whereDate(
                'tanggal_berlaku',
                $tanggalDinas->toDateString()
            )->orWhere(function ($q2) use ($namaHari) {
                $q2->whereNull('tanggal_berlaku')
                    ->where(function ($hariQuery) use ($namaHari) {
                        $hariQuery
                            ->whereRaw(
                                'LOWER(TRIM(hari_berlaku)) = ?',
                                [$namaHari]
                            )
                            ->orWhereRaw(
                                "LOWER(TRIM(hari_berlaku)) = 'setiap hari'"
                            );
                    });
            });
        });
    }

    private function namaHariIndonesia(Carbon $tanggal): string
    {
        return [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            7 => 'minggu',
        ][$tanggal->dayOfWeekIso];
    }

    /**
     * Menyimpan catatan dinas.
     */
    public function storeCatatan(Request $request)
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:100'],
            'isi_catatan' => ['required', 'string', 'max:10000'],
            'tindak_lanjut' => ['nullable', 'string', 'max:5000'],
        ]);

        $dinas = $this->dinasAktif();

        if (!$dinas) {
            $pesanError =
                'Tidak ada dinas aktif. Catatan tidak dapat disimpan.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $pesanError,
                ], 422);
            }

            return back()
                ->withErrors(['dinas' => $pesanError])
                ->withInput();
        }

        // Data yang sudah disetujui admin tidak boleh diubah.
        if ($tolak = $this->tolakJikaTerkunci($request, $dinas)) {
            return $tolak;
        }

        $catatan = CatatanDinas::create([
            'dinas_id' => $dinas->id,
            'user_id' => auth()->id(),
            'judul' => $validated['judul'],
            'kategori' => $validated['kategori'],
            'isi_catatan' => $validated['isi_catatan'],
            'status' => 'baru',
            'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
            'waktu_catatan' => now(),
        ]);

        $pesan = 'Catatan dinas berhasil disimpan.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $catatan->id,
                'message' => $pesan,
            ]);
        }

        return back()->with('success', $pesan);
    }

    /**
     * Menyimpan pemeriksaan inventaris.
     */
    public function storeInventaris(Request $request)
    {
        $validated = $request->validate([
            'inventaris_id' => [
                'required',
                'integer',
                'exists:inventaris,id',
            ],
            'kondisi' => [
                'required',
                Rule::in([
                    'baik',
                    'perlu_perbaikan',
                    'rusak',
                    'hilang',
                ]),
            ],
            'jumlah_aktual' => [
                'required',
                'integer',
                'min:0',
            ],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $dinas = $this->dinasAktif();

        if (!$dinas) {
            $pesanError =
                'Tidak ada dinas aktif untuk pemeriksaan inventaris.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $pesanError,
                ], 422);
            }

            return back()
                ->withErrors(['dinas' => $pesanError])
                ->withInput();
        }

        // Data yang sudah disetujui admin tidak boleh diubah.
        if ($tolak = $this->tolakJikaTerkunci($request, $dinas)) {
            return $tolak;
        }

        $inventaris = Inventaris::query()
            ->whereKey($validated['inventaris_id'])
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless(
            (int) $inventaris->jpl_id === (int) $dinas->jpl_id,
            403,
            'Inventaris ini bukan bagian dari penempatan JPL dinas Anda.'
        );

        $jumlahAktual = (int) $validated['jumlah_aktual'];
        $jumlahMaster = (int) $inventaris->jumlah;

        $adaMasalah = $validated['kondisi'] !== 'baik'
            || $jumlahAktual !== $jumlahMaster;

        DB::transaction(function () use (
            $dinas,
            $inventaris,
            $validated,
            $jumlahAktual,
            $adaMasalah
        ) {
            RealisasiInventaris::updateOrCreate(
                [
                    'dinas_id' => $dinas->id,
                    'inventaris_id' => $inventaris->id,
                ],
                [
                    'status' => $adaMasalah ? 'silang' : 'centang',
                    'kondisi' => $validated['kondisi'],
                    'jumlah_aktual' => $jumlahAktual,
                    'catatan' => $validated['catatan'] ?? null,
                    'diperiksa_pada' => now(),
                    'diperiksa_oleh' => auth()->id(),
                ]
            );

            $inventaris->jumlah_aktual_terakhir = $jumlahAktual;
            $inventaris->kondisi_terakhir = $validated['kondisi'];

            if ($adaMasalah) {
                if (
                    !$inventaris->dilaporkan_pada
                    || $inventaris->status_tinjauan === 'normal'
                ) {
                    $inventaris->status_tinjauan = 'perlu_tinjauan';
                    $inventaris->catatan_masalah =
                        $validated['catatan']
                        ?: 'Kondisi atau jumlah aktual berbeda dari data inventaris.';
                    $inventaris->dilaporkan_pada = now();
                    $inventaris->dilaporkan_oleh = auth()->id();
                } else {
                    $inventaris->status_tinjauan =
                        $inventaris->status_tinjauan
                        ?: 'perlu_tinjauan';
                }
            }

            $inventaris->save();
        });

        $pesan = $adaMasalah
            ? 'Pemeriksaan tersimpan. Inventaris memerlukan perhatian dan menunggu tinjauan admin.'
            : 'Pemeriksaan inventaris berhasil disimpan.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'inventaris_id' => $inventaris->id,
                'status' => $adaMasalah ? 'silang' : 'centang',
                'ada_masalah' => $adaMasalah,
                'message' => $pesan,
            ]);
        }

        return redirect()
            ->to(route('user.serah-terima-dinas') . '#inventaris')
            ->with('success', $pesan);
    }

    /**
     * Membuat atau memperbarui serah terima oleh penyerah.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serah_terima_id' => [
                'nullable',
                'integer',
            ],
            'penerima_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->where('role_id', 1)
                        ->where('is_active', true)
                ),
            ],
            'ringkasan' => [
                'required',
                'string',
                'max:500000',
            ],
            'catatan_penting' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'ppka' => [
                'nullable',
                'string',
                'max:255',
            ],
            'tanda_tangan_penyerah' => [
                'nullable',
                'string',
                'max:1500000',
                'required_if:tanda_tangan_penyerah_action,set',
            ],
            'tanda_tangan_penyerah_action' => [
                'nullable',
                Rule::in(['keep', 'set', 'clear']),
            ],
        ]);

        $dariRiwayat = !empty($validated['serah_terima_id']);

        if ($dariRiwayat) {
            $serahTerimaLama = SerahTerima::with('dinas')
                ->milikUser(auth()->id())
                ->findOrFail($validated['serah_terima_id']);

            $dinas = $serahTerimaLama->dinas;
        } else {
            $dinas = $this->dinasAktif();

            if (!$dinas) {
                return back()
                    ->withErrors([
                        'dinas' =>
                            'Tidak ada dinas aktif untuk diserahterimakan.',
                    ])
                    ->withInput();
            }

            $serahTerimaLama = SerahTerima::where(
                'dinas_id',
                $dinas->id
            )
                ->latest('id')
                ->first();
        }

        if (!$dinas) {
            return back()
                ->withErrors([
                    'dinas' => 'Data dinas tidak ditemukan.',
                ])
                ->withInput();
        }

        // Sudah disetujui admin: tolak, apa pun jalur masuknya.
        if (
            $serahTerimaLama?->isTerkunci()
            || $this->dinasTerkunci($dinas)
        ) {
            return back()
                ->withErrors([
                    'dinas' =>
                        'Serah terima ini sudah disetujui admin dan terkunci. Data tidak dapat diubah.',
                ])
                ->withInput();
        }

        $penerima = User::query()
            ->where('role_id', 1)
            ->where('is_active', true)
            ->findOrFail($validated['penerima_user_id']);

        if ((int) $penerima->id === (int) auth()->id()) {
            throw ValidationException::withMessages([
                'penerima_user_id' =>
                    'Penerima harus petugas PJL lain, bukan akun penyerah.',
            ]);
        }

        $ringkasanMasuk = json_decode(
            $validated['ringkasan'],
            true
        );

        if (!is_array($ringkasanMasuk)) {
            throw ValidationException::withMessages([
                'ringkasan' =>
                    'Format ringkasan serah terima tidak valid.',
            ]);
        }

        /*
         * Semua inventaris aktif pada JPL harus diperiksa
         * sebelum serah terima dapat disimpan.
         */
        $inventarisAktif = Inventaris::query()
            ->where('is_active', true)
            ->where('jpl_id', $dinas->jpl_id)
            ->orderBy('nama_barang')
            ->get();

        $idInventarisAktif = $inventarisAktif->pluck('id');

        $realisasiInventaris = RealisasiInventaris::query()
            ->where('dinas_id', $dinas->id)
            ->whereIn('inventaris_id', $idInventarisAktif)
            ->get()
            ->keyBy('inventaris_id');

        $idSudahDiperiksa = $realisasiInventaris->keys();

        $jumlahBelum = $idInventarisAktif
            ->diff($idSudahDiperiksa)
            ->count();

        if ($jumlahBelum > 0) {
            throw ValidationException::withMessages([
                'inventaris' =>
                    "Masih ada {$jumlahBelum} inventaris yang belum diperiksa. Periksa semua inventaris terlebih dahulu.",
            ]);
        }

        $tandaTanganPenyerahLama =
            $serahTerimaLama?->tanda_tangan_penyerah;

        $tandaTanganPenerimaLama =
            $serahTerimaLama?->tanda_tangan_penerima;

        $actionPenyerah =
            $validated['tanda_tangan_penyerah_action']
            ?? (
                !empty($validated['tanda_tangan_penyerah'])
                    ? 'set'
                    : 'keep'
            );

        $akanAdaPenyerah = match ($actionPenyerah) {
            'clear' => false,
            'set' => !empty(
                $validated['tanda_tangan_penyerah']
            ),
            default => !empty($tandaTanganPenyerahLama),
        };

        if (!$akanAdaPenyerah) {
            throw ValidationException::withMessages([
                'tanda_tangan_penyerah' =>
                    'Tanda tangan penyerah wajib diisi sebelum menyimpan serah terima.',
            ]);
        }

        $signaturePenyerah = null;

        try {
            if ($actionPenyerah === 'set') {
                $signaturePenyerah = $this->saveSignature(
                    $validated['tanda_tangan_penyerah'] ?? null,
                    'penyerah'
                );
            }

            /*
             * Ambil data KA terbaru dari database.
             */
            $dataKA = $this->ambilDataKA($dinas)->all();

            $ringkasanKA = $this->buatRingkasanKA($dataKA);

            /*
             * Snapshot inventaris untuk serah terima ini.
             */
            $snapshotInventaris = $inventarisAktif
                ->map(function ($item) use ($realisasiInventaris) {
                    $hasil = $realisasiInventaris->get($item->id);

                    return [
                        'inventaris_id' => $item->id,
                        'nama_barang' => $item->nama_barang,
                        'kategori' => $item->kategori,
                        'kode_barang' => $item->kode_barang,
                        'jumlah_master' => $item->jumlah,
                        'jumlah_aktual' => $hasil?->jumlah_aktual,
                        'kondisi' => $hasil?->kondisi,
                        'status' => $hasil?->status,
                        'catatan' => $hasil?->catatan,
                        'diperiksa_pada' =>
                            $hasil?->diperiksa_pada?->toDateTimeString(),
                        'diperiksa_oleh' => $hasil?->diperiksa_oleh,
                    ];
                })
                ->values()
                ->all();

            /*
             * Mendukung nama kunci catatan dari formulir lama.
             */
            $catatanSerahTerima =
                $ringkasanMasuk['catatan_serah_terima']
                ?? $ringkasanMasuk['catatan']
                ?? $validated['catatan_penting']
                ?? '';

            /*
             * Identitas penyerah dan PPKA berasal dari akun login.
             * Nilai ppka dari browser tidak digunakan sebagai sumber
             * identitas agar tidak kosong atau dapat dimanipulasi.
             */
            $userPenyerah = auth()->user();

            $ringkasan = array_merge(
                $ringkasanMasuk,
                [
                    'ka_batal' => $ringkasanKA['batal'],
                    'ka_berjalan' => $ringkasanKA['berjalan'],
                    'ka_belum_lewat' => $ringkasanKA['belum'],

                    'kereta_api' => $dataKA,
                    'inventaris' => $snapshotInventaris,

                    'catatan_serah_terima' => $catatanSerahTerima,
                    'catatan_penting' =>
                        $validated['catatan_penting']
                        ?? $catatanSerahTerima,

                    // PPKA = akun petugas yang sedang login.
                    'ppka' => $userPenyerah->name ?? '',
                    'nama_ppka' => $userPenyerah->name ?? '',
                    'nip_ppka' => $userPenyerah->nip ?? '',

                    // Identitas penerima.
                    'nama_penerima' => $penerima->name,
                    'nip_penerima' => $penerima->nip ?? '',

                    // Identitas penyerah.
                    'nama_penyerah' => $userPenyerah->name ?? '',
                    'nip_penyerah' => $userPenyerah->nip ?? '',
                ]
            );

            $penyerahFinal = match ($actionPenyerah) {
                'clear' => null,
                'set' => $signaturePenyerah,
                default => $tandaTanganPenyerahLama,
            };

            DB::transaction(function () use (
                $dinas,
                $penerima,
                $ringkasan,
                $penyerahFinal,
                $serahTerimaLama
            ) {
                $dataSimpan = [
                    'dinas_id' => $dinas->id,
                    'dinas_penerima_id' => null,
                    'penerima_user_id' => $penerima->id,
                    'waktu_serah_terima' => now(),
                    'ringkasan' => $ringkasan,
                    'tanda_tangan_penyerah' => $penyerahFinal,
                    'tanda_tangan_penerima' => null,
                    'status' => 'menunggu_penerima',
                    'status_review' => 'menunggu_review',
                    'catatan_revisi' => null,
                    'ditinjau_oleh' => null,
                    'ditinjau_pada' => null,
                ];

                if ($serahTerimaLama) {
                    $serahTerimaLama->update($dataSimpan);
                } else {
                    SerahTerima::create($dataSimpan);
                }
            });
        } catch (\Throwable $e) {
            if ($signaturePenyerah) {
                Storage::disk('public')->delete(
                    $signaturePenyerah
                );
            }

            throw $e;
        }

        /*
         * Bersihkan tanda tangan lama setelah penyimpanan berhasil.
         */
        if (
            $tandaTanganPenyerahLama
            && (
                $actionPenyerah === 'clear'
                || (
                    $actionPenyerah === 'set'
                    && $signaturePenyerah
                    && $signaturePenyerah !== $tandaTanganPenyerahLama
                )
            )
        ) {
            Storage::disk('public')->delete(
                $tandaTanganPenyerahLama
            );
        }

        /*
         * Tanda tangan penerima lama tidak lagi valid karena
         * isi serah terima atau penerimanya dapat berubah.
         */
        if ($tandaTanganPenerimaLama) {
            Storage::disk('public')->delete(
                $tandaTanganPenerimaLama
            );
        }

        return redirect()
            ->route(
                $dariRiwayat
                    ? 'user.serah-terima-dinas.riwayat'
                    : 'user.serah-terima-dinas'
            )
            ->with(
                'success',
                'Serah terima berhasil disimpan. Menunggu konfirmasi tanda tangan dari petugas penerima.'
            );
    }

    /**
     * Konfirmasi serah terima oleh akun PJL penerima yang ditunjuk.
     */
    public function konfirmasi(
        Request $request,
        $serahTerima
    ) {
        abort_unless(
            (int) auth()->user()->role_id === 1,
            403,
            'Hanya petugas PJL yang dapat mengonfirmasi sebagai penerima.'
        );

        $validated = $request->validate([
            'tanda_tangan_penerima' => [
                'required',
                'string',
                'max:1500000',
            ],
        ]);

        $data = SerahTerima::with('dinas')
            ->findOrFail($serahTerima);

        abort_if(
            $data->isTerkunci(),
            422,
            'Serah terima ini sudah disetujui dan terkunci.'
        );

        abort_unless(
            $data->penerima_user_id !== null
                && (int) $data->penerima_user_id
                    === (int) auth()->id(),
            403,
            'Anda bukan petugas penerima yang ditunjuk untuk serah terima ini.'
        );

        abort_unless(
            $data->status === 'menunggu_penerima',
            422,
            'Serah terima ini tidak sedang menunggu konfirmasi penerima.'
        );

        abort_unless(
            empty($data->tanda_tangan_penerima),
            422,
            'Tanda tangan penerima sudah tersedia.'
        );

        $signaturePenerima = $this->saveSignature(
            $validated['tanda_tangan_penerima'],
            'penerima'
        );

        try {
            DB::transaction(function () use (
                $data,
                $signaturePenerima
            ) {
                $terkini = SerahTerima::query()
                    ->lockForUpdate()
                    ->findOrFail($data->id);

                abort_if(
                    $terkini->isTerkunci(),
                    422,
                    'Serah terima ini sudah disetujui dan terkunci.'
                );

                abort_unless(
                    $terkini->penerima_user_id !== null
                        && (int) $terkini->penerima_user_id
                            === (int) auth()->id(),
                    403,
                    'Anda bukan penerima yang ditunjuk.'
                );

                abort_unless(
                    $terkini->status === 'menunggu_penerima'
                        && empty($terkini->tanda_tangan_penerima),
                    422,
                    'Serah terima sudah dikonfirmasi atau tidak lagi menunggu penerima.'
                );

                $terkini->update([
                    'tanda_tangan_penerima' => $signaturePenerima,
                    'status' => 'selesai',
                    'status_review' => 'menunggu_review',
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($signaturePenerima);

            throw $e;
        }

        return back()->with(
            'success',
            'Tanda tangan penerima berhasil disimpan. Serah terima menunggu tinjauan admin.'
        );
    }

    /**
     * Normalisasi status KA untuk tampilan.
     */
    private function formatStatus(?string $status): string
    {
        return match ($status) {
            'centang' => 'JALAN',
            'silang' => 'BATAL',
            default => 'BELUM',
        };
    }

    /**
     * Normalisasi status pemeriksaan perlengkapan KA.
     */
    private function checkValue(?string $value): string
    {
        return match ($value) {
            'centang' => 'yes',
            'silang' => 'no',
            default => '',
        };
    }

    /**
     * Format jam menjadi HH:mm.
     */
    private function formatJam($jam): string
    {
        if (!$jam) {
            return '-';
        }

        return substr((string) $jam, 0, 5);
    }

    /**
     * Memvalidasi dan menyimpan tanda tangan PNG dari data URL.
     */
    private function saveSignature(
        ?string $dataUrl,
        string $jenis
    ): ?string {
        if (empty($dataUrl)) {
            throw ValidationException::withMessages([
                'tanda_tangan_' . $jenis =>
                    'Tanda tangan belum tersedia. Silakan tanda tangan kembali.',
            ]);
        }

        if (!preg_match(
            '/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/',
            $dataUrl,
            $matches
        )) {
            throw ValidationException::withMessages([
                'tanda_tangan_' . $jenis =>
                    'Format tanda tangan harus berupa gambar PNG.',
            ]);
        }

        $image = base64_decode($matches[1], true);

        if (
            $image === false
            || strlen($image) > 1024 * 1024
            || !str_starts_with($image, "\x89PNG\r\n\x1a\n")
        ) {
            throw ValidationException::withMessages([
                'tanda_tangan_' . $jenis =>
                    'Gambar tanda tangan tidak valid atau melebihi 1 MB.',
            ]);
        }

        $path = 'serah-terima/'
            . auth()->id()
            . '/'
            . uniqid($jenis . '_', true)
            . '.png';

        if (!Storage::disk('public')->put($path, $image)) {
            throw new \RuntimeException(
                'Gagal menyimpan tanda tangan ke storage.'
            );
        }

        return $path;
    }
}
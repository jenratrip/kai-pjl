<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CatatanDinas;
use App\Models\Dinas;
use App\Models\Inventaris;
use App\Models\RealisasiInventaris;
use App\Models\RealisasiKa;
use App\Models\SerahTerima;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SerahTerimaDinasController extends Controller
{
    /**
     * Mendapatkan dinas aktif milik user yang login.
     */
    private function dinasAktif(): ?Dinas
    {
        return Dinas::with([
            'user',
            'userDigantikan',
            'jpl.resort',
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
     * Apakah penyerah masih boleh mengubah serah terima.
     */
    private function bolehDiedit(SerahTerima $serahTerima): bool
    {
        if ($serahTerima->isTerkunci()) {
            return false;
        }

        return $serahTerima->status_review === 'perlu_revisi'
            || $serahTerima->status === 'menunggu_penerima';
    }

    /**
     * Mengembalikan respons penolakan jika dinas sudah terkunci,
     * atau null jika masih boleh diubah.
     */
    private function tolakJikaTerkunci(Request $request, Dinas $dinas)
    {
        if (! $this->dinasTerkunci($dinas)) {
            return null;
        }

        $pesan = 'Serah terima dinas ini sudah disetujui admin. '
            .'Data terkunci dan tidak dapat diubah.';

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
            ->whereHas('role', fn ($q) => $q->where('name', 'pjl'))
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
     * Riwayat serah terima keluar dan masuk milik user yang login.
     */
    public function riwayat()
    {
        $dinasAktif = $this->dinasAktif();
        $userId = auth()->id();

        $relasi = [
            'dinas.user',
            'dinas.userDigantikan',
            'dinas.jpl.resort',
            'dinas.shift',
            'penerima',
        ];

        $riwayat = SerahTerima::with($relasi)
            ->milikUser($userId)
            ->latest('waktu_serah_terima')
            ->latest('id')
            ->paginate(10, ['*'], 'riwayat_page');

        $pengajuanMasuk = SerahTerima::with($relasi)
            ->where('penerima_user_id', $userId)
            ->latest('waktu_serah_terima')
            ->latest('id')
            ->paginate(10, ['*'], 'masuk_page');

        return view(
            'user.serahterimacatatan.riwayat',
            compact('riwayat', 'pengajuanMasuk', 'dinasAktif')
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
            'dinas.userDigantikan',
            'dinas.jpl.resort',
            'dinas.shift',
            'penerima',
        ])
            ->milikUser(auth()->id())
            ->findOrFail($serahTerima);

        if (! $this->bolehDiedit($data)) {
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
     * Menampilkan detail serah terima kepada penyerah atau penerima yang ditunjuk.
     */
    public function show($serahTerima)
    {
        $data = SerahTerima::with([
            'dinas.user',
            'dinas.userDigantikan',
            'dinas.jpl.resort',
            'dinas.shift',
            'penerima',
        ])
            ->where(function ($query) {
                $query->milikUser(auth()->id())
                    ->orWhere('penerima_user_id', auth()->id());
            })
            ->findOrFail($serahTerima);

        $isPenyerah = (int) $data->dinas->user_id === (int) auth()->id();
        $isPenerima = (int) $data->penerima_user_id === (int) auth()->id();

        abort_unless($isPenyerah || $isPenerima, 403);

        if ($isPenyerah && ! $isPenerima && $this->bolehDiedit($data)) {
            return redirect()->route(
                'user.serah-terima-dinas.edit',
                $data
            );
        }

        $modeLihat = ($isPenerima && ! $isPenyerah)
            || ! $this->bolehDiedit($data);

        return $this->tampilkanHalaman(
            $data->dinas,
            $data,
            false,
            $modeLihat,
            $isPenerima && ! $isPenyerah
        );
    }

    /**
     * Menampilkan foto bukti hanya kepada penyerah atau penerima.
     */
    public function fotoBukti(SerahTerima $serahTerima): BinaryFileResponse
    {
        $serahTerima->loadMissing('dinas');

        $isPenyerah = (int) $serahTerima->dinas?->user_id === (int) auth()->id();
        $isPenerima = (int) $serahTerima->penerima_user_id === (int) auth()->id();

        abort_unless($isPenyerah || $isPenerima, 404);

        $path = data_get($serahTerima->ringkasan, 'foto_bukti_path');

        abort_unless(
            is_string($path)
                && str_starts_with($path, 'serah-terima/bukti/')
                && ! str_contains($path, '..')
                && Storage::disk('local')->exists($path),
            404
        );

        $mimeType = Storage::disk('local')->mimeType($path);

        abort_unless(
            in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true),
            404
        );

        $response = response()->file(
            Storage::disk('local')->path($path),
            [
                'Content-Type' => $mimeType,
                'X-Content-Type-Options' => 'nosniff',
            ]
        );

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * Menyajikan bukti foto inventaris dari snapshot Serah Terima kepada para pihak.
     */
    public function fotoBuktiInventaris(
        SerahTerima $serahTerima,
        int $inventarisId
    ): BinaryFileResponse {
        $serahTerima->loadMissing('dinas');

        $isPenyerah = (int) $serahTerima->dinas?->user_id === (int) auth()->id();
        $isPenerima = (int) $serahTerima->penerima_user_id === (int) auth()->id();

        abort_unless($isPenyerah || $isPenerima, 404);

        $snapshotItem = collect(data_get($serahTerima->ringkasan, 'inventaris', []))
            ->first(fn ($item) => (int) data_get($item, 'inventaris_id') === $inventarisId);
        $path = data_get($snapshotItem, 'bukti_foto_path');

        abort_unless($this->isInventarisEvidencePath($path), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $mimeType = Storage::disk('local')->mimeType($path);
        abort_unless(
            in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true),
            404
        );

        $response = response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * Menyajikan tanda tangan hanya kepada pihak terkait dan petugas administrasi.
     */
    public function tandaTangan(SerahTerima $serahTerima, string $jenis): BinaryFileResponse
    {
        abort_unless(in_array($jenis, ['penyerah', 'penerima'], true), 404);

        $serahTerima->loadMissing('dinas');
        $user = auth()->user();
        $isPihakTerkait = (int) $serahTerima->dinas?->user_id === (int) $user?->id
            || (int) $serahTerima->penerima_user_id === (int) $user?->id;
        $isAdministrator = $user?->hasRole('admin') || $user?->hasRole('superadmin');

        abort_unless($isPihakTerkait || $isAdministrator, 404);

        $path = $jenis === 'penyerah'
            ? $serahTerima->tanda_tangan_penyerah
            : $serahTerima->tanda_tangan_penerima;

        abort_unless(
            is_string($path) && $this->isSignaturePath($path),
            404
        );

        $disk = str_starts_with($path, 'serah-terima/signatures/')
            ? Storage::disk('local')
            : Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path);
        abort_unless($mimeType === 'image/png', 404);

        $response = response()->file($disk->path($path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * Menyiapkan data untuk halaman serah terima.
     *
     * $dinas:
     * Data dinas yang digunakan untuk konten serah terima.
     *
     * $dinasAktif:
     * Data dinas aktif user login untuk kebutuhan header.
     */
    private function tampilkanHalaman(
        ?Dinas $dinas,
        ?SerahTerima $serahTerima = null,
        bool $modeEdit = false,
        bool $modeLihat = false,
        bool $modePenerima = false
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
        $waktuPenerimaan = null;

        if ($dinas) {
            if (! $serahTerima) {
                $serahTerima = SerahTerima::with('penerima')
                    ->where('dinas_id', $dinas->id)
                    ->latest('id')
                    ->first();
            }

            $dataRealisasiKA = $this->dataKADariRingkasan($serahTerima)
                ?? $this->ambilDataKA($dinas);

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

        }

        // Jika sudah disetujui admin, halaman apa pun hanya mode lihat.
        if ($serahTerima && ! $this->bolehDiedit($serahTerima)) {
            $modeLihat = true;
        }

        if (
            $serahTerima
            && $serahTerima->status === 'selesai'
            && $serahTerima->tanda_tangan_penerima
        ) {
            $waktuPenerimaan = ActivityLog::query()
                ->where('module', 'serah_terima')
                ->where('action', 'confirm_recipient')
                ->where('subject_type', SerahTerima::class)
                ->where('subject_id', $serahTerima->id)
                ->where('user_id', $serahTerima->penerima_user_id)
                ->latest('id')
                ->value('created_at');
        }

        /*
         * Ambil dinas aktif secara terpisah.
         *
         * Jangan menggunakan $dinas sebagai dinasAktif karena
         * pada halaman edit/show, $dinas dapat berupa dinas historis.
         */
        $dinasAktif = $this->dinasAktif();

        return view('user.serahterimacatatan.index', [
            'dinasAktif' => $dinasAktif,
            'dinas' => $dinas,
            'catatan' => $catatan,
            'inventaris' => $inventaris,
            'realisasi' => $realisasi,
            'serahTerima' => $serahTerima,
            'waktuPenerimaan' => $waktuPenerimaan,
            'dataRealisasiKA' => $dataRealisasiKA,
            'ringkasanKA' => $ringkasanKA,
            'modeEdit' => $modeEdit,
            'modeLihat' => $modeLihat,
            'modePenerima' => $modePenerima,
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
                ->filter(fn ($ka) => strtoupper($ka['status'] ?? '') === 'BATAL'
                )
                ->values()
                ->all(),

            'berjalan' => $dataRealisasiKA
                ->filter(fn ($ka) => strtoupper($ka['status'] ?? '') === 'JALAN'
                )
                ->values()
                ->all(),

            'belum' => $dataRealisasiKA
                ->filter(fn ($ka) => strtoupper($ka['status'] ?? '') === 'BELUM'
                )
                ->values()
                ->all(),
        ];
    }

    /**
     * Mengambil data KA yang sudah tersimpan pada dokumen serah terima.
     */
    private function dataKADariRingkasan(?SerahTerima $serahTerima): ?Collection
    {
        if (! $serahTerima) {
            return null;
        }

        $ringkasan = $serahTerima->ringkasan;

        if (! is_array($ringkasan)) {
            return collect();
        }

        if (is_array($ringkasan['kereta_api'] ?? null)) {
            return collect($ringkasan['kereta_api'])
                ->filter(fn ($item) => is_array($item))
                ->values();
        }

        $memilikiRingkasanStatus = array_key_exists('ka_batal', $ringkasan)
            || array_key_exists('ka_berjalan', $ringkasan)
            || array_key_exists('ka_belum_lewat', $ringkasan);

        if (! $memilikiRingkasanStatus) {
            return collect();
        }

        $dataTersimpan = [];

        foreach (['ka_batal', 'ka_berjalan', 'ka_belum_lewat'] as $key) {
            if (is_array($ringkasan[$key] ?? null)) {
                $dataTersimpan = array_merge($dataTersimpan, $ringkasan[$key]);
            }
        }

        return collect($dataTersimpan)
            ->filter(fn ($item) => is_array($item))
            ->values();
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
        $snapshot = $dinas->jadwal_ka_snapshot;
        $jadwalList = is_array($snapshot) && is_array($snapshot['jadwal'] ?? null)
            ? $snapshot['jadwal']
            : [];

        $realisasiTerjadwal = RealisasiKa::with(['keretaApi', 'jadwalKa'])
            ->where('dinas_id', $dinas->id)
            ->where('is_manual', false)
            ->orderByDesc('id')
            ->get()
            ->unique('jadwal_ka_id')
            ->keyBy('jadwal_ka_id');

        $dataKA = [];

        foreach ($jadwalList as $jadwal) {
            if (! is_array($jadwal) || ! isset($jadwal['jadwal_ka_id'])) {
                continue;
            }

            $jadwalId = (int) $jadwal['jadwal_ka_id'];
            $realisasiKA = $realisasiTerjadwal->get($jadwalId);

            $dataKA[] = [
                'id' => $realisasiKA?->id,
                'nomor' => $jadwal['nomor_ka'] ?? '-',
                'nama' => $jadwal['nama_ka'] ?? '-',
                'jadwal' => $this->formatJam($jadwal['jam_lewat'] ?? null),
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
                'jadwal_ka_id' => $jadwalId,
                'is_manual' => false,
            ];
        }

        // Tanpa snapshot, tampilkan hanya Realisasi KA lama yang sudah tersimpan.
        if ($snapshot === null) {
            foreach ($realisasiTerjadwal as $realisasiKA) {
                if ($realisasiKA->jadwal_ka_id === null) {
                    continue;
                }

                $jadwalLama = $realisasiKA->jadwalKa;

                $dataKA[] = [
                    'id' => $realisasiKA->id,
                    'nomor' => $realisasiKA->keretaApi?->nomor_ka ?? '-',
                    'nama' => $realisasiKA->keretaApi?->nama_ka ?? '-',
                    'jadwal' => $this->formatJam($jadwalLama?->jam_lewat),
                    'aktual' => $this->formatJam($realisasiKA->jam_aktual),
                    'status' => $this->formatStatus($realisasiKA->status_lewat),
                    'semboyan21' => $this->checkValue($realisasiKA->status_s21),
                    'genta' => $this->checkValue($realisasiKA->status_genta),
                    'pintu' => $this->checkValue($realisasiKA->status_pintu_perlintasan),
                    'semboyan35' => $this->checkValue($realisasiKA->status_s35),
                    'keterangan' => $realisasiKA->catatan ?? '',
                    'jadwal_ka_id' => $realisasiKA->jadwal_ka_id,
                    'is_manual' => false,
                ];
            }
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

        if (! $dinas) {
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
    public function storeInventaris(Request $request, ActivityLogService $activityLogService)
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
            'bukti_foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $validated['catatan'] = trim((string) ($validated['catatan'] ?? ''));

        if (
            in_array($validated['kondisi'], ['rusak', 'hilang'], true)
            && $validated['catatan'] === ''
        ) {
            throw ValidationException::withMessages([
                'catatan' => 'Keterangan kerusakan atau kehilangan wajib diisi.',
            ]);
        }

        if (
            $request->hasFile('bukti_foto')
            && ! in_array($validated['kondisi'], ['rusak', 'hilang'], true)
        ) {
            throw ValidationException::withMessages([
                'bukti_foto' => 'Foto bukti hanya dapat dilampirkan untuk kondisi rusak atau hilang.',
            ]);
        }

        $dinas = $this->dinasAktif();

        if (! $dinas) {
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

        $fotoUnggahan = $request->file('bukti_foto');
        $fotoBaruPath = null;
        $fotoLamaPath = null;

        try {
            $realisasi = DB::transaction(function () use (
                $dinas,
                $inventaris,
                $validated,
                $jumlahAktual,
                $adaMasalah,
                $activityLogService,
                $fotoUnggahan,
                &$fotoBaruPath,
                &$fotoLamaPath
            ): RealisasiInventaris {
                Dinas::query()->lockForUpdate()->findOrFail($dinas->id);

                $existingRealisasi = RealisasiInventaris::query()
                    ->where('dinas_id', $dinas->id)
                    ->where('inventaris_id', $inventaris->id)
                    ->lockForUpdate()
                    ->first();

                $fotoLamaPath = $existingRealisasi?->bukti_foto_path;
                $fotoLamaTersedia = $this->buktiFotoInventarisTersedia($fotoLamaPath);

                if (
                    $validated['kondisi'] === 'rusak'
                    && ! $fotoLamaTersedia
                    && ! $fotoUnggahan
                ) {
                    throw ValidationException::withMessages([
                        'bukti_foto' => 'Foto bukti kerusakan wajib dilampirkan.',
                    ]);
                }

                if ($fotoUnggahan) {
                    $fotoBaruPath = $fotoUnggahan->store('inventaris/bukti', 'local');

                    if (! is_string($fotoBaruPath)) {
                        throw ValidationException::withMessages([
                            'bukti_foto' => 'Foto bukti gagal disimpan. Silakan coba kembali.',
                        ]);
                    }
                }

                $fotoTersimpanPath = $fotoBaruPath
                    ?? ($fotoLamaTersedia ? $fotoLamaPath : null);

                $oldValues = $existingRealisasi === null
                    ? null
                    : [
                        'dinas_id' => (int) $existingRealisasi->dinas_id,
                        'inventaris_id' => (int) $existingRealisasi->inventaris_id,
                        'status' => $existingRealisasi->status,
                        'kondisi' => $existingRealisasi->kondisi,
                        'jumlah_aktual' => $existingRealisasi->jumlah_aktual,
                        'status_tinjauan' => $inventaris->status_tinjauan,
                    ];

                $realisasi = RealisasiInventaris::updateOrCreate(
                    [
                        'dinas_id' => $dinas->id,
                        'inventaris_id' => $inventaris->id,
                    ],
                    [
                        'status' => $adaMasalah ? 'silang' : 'centang',
                        'kondisi' => $validated['kondisi'],
                        'jumlah_aktual' => $jumlahAktual,
                        'catatan' => $validated['catatan'] !== ''
                            ? $validated['catatan']
                            : null,
                        'bukti_foto_path' => $fotoTersimpanPath,
                        'diperiksa_pada' => now(),
                        'diperiksa_oleh' => auth()->id(),
                    ]
                );

                $inventaris->jumlah_aktual_terakhir = $jumlahAktual;
                $inventaris->kondisi_terakhir = $validated['kondisi'];

                if ($adaMasalah) {
                    if (
                        ! $inventaris->dilaporkan_pada
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

                $activityLogService->log(
                    'inspection',
                    'inventaris',
                    'Pemeriksaan inventaris melalui Serah Terima berhasil disimpan.',
                    $realisasi,
                    $oldValues,
                    [
                        'dinas_id' => (int) $dinas->id,
                        'inventaris_id' => (int) $inventaris->id,
                        'status' => $realisasi->status,
                        'kondisi' => $realisasi->kondisi,
                        'jumlah_aktual' => $realisasi->jumlah_aktual,
                        'status_tinjauan' => $inventaris->status_tinjauan,
                    ]
                );

                return $realisasi;
            });
        } catch (\Throwable $exception) {
            if (is_string($fotoBaruPath)) {
                Storage::disk('local')->delete($fotoBaruPath);
            }

            throw $exception;
        }

        if (
            is_string($fotoLamaPath)
            && $fotoLamaPath !== $realisasi->bukti_foto_path
            && $this->isInventarisEvidencePath($fotoLamaPath)
        ) {
            Storage::disk('local')->delete($fotoLamaPath);
        }

        $pesan = $adaMasalah
            ? 'Pemeriksaan tersimpan. Inventaris memerlukan perhatian dan menunggu tinjauan admin.'
            : 'Pemeriksaan inventaris berhasil disimpan.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'inventaris_id' => $inventaris->id,
                'status' => $adaMasalah ? 'silang' : 'centang',
                'ada_masalah' => $adaMasalah,
                'bukti_foto_url' => $realisasi->bukti_foto_path
                    ? route('user.inventaris.foto-bukti', $inventaris)
                    : null,
                'message' => $pesan,
            ]);
        }

        return redirect()
            ->to(route('user.serah-terima-dinas').'#inventaris')
            ->with('success', $pesan);
    }

    private function buktiFotoInventarisTersedia(?string $path): bool
    {
        return $this->isInventarisEvidencePath($path)
            && Storage::disk('local')->exists($path);
    }

    private function isInventarisEvidencePath(mixed $path): bool
    {
        return is_string($path)
            && str_starts_with($path, 'inventaris/bukti/')
            && ! str_contains($path, '..');
    }

    /**
     * Membuat atau memperbarui serah terima oleh penyerah.
     */
    public function store(Request $request, ActivityLogService $activityLogService)
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
                    fn ($query) => $query->where('is_active', true)
                ),
                function ($attribute, $value, $fail) {
                    $user = User::find($value);

                    if (! $user || ! $user->hasRole('pjl')) {
                        $fail('Penerima harus merupakan petugas PJL yang aktif.');
                    }
                },
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
            'foto_bersama' => [
                'required_without:serah_terima_id',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $dariRiwayat = ! empty($validated['serah_terima_id']);

        if ($dariRiwayat) {
            $serahTerimaLama = SerahTerima::with('dinas')
                ->milikUser(auth()->id())
                ->findOrFail($validated['serah_terima_id']);

            $dinas = $serahTerimaLama->dinas;
        } else {
            $dinas = $this->dinasAktif();

            if (! $dinas) {
                return back()
                    ->withErrors([
                        'dinas' => 'Tidak ada dinas aktif untuk diserahterimakan.',
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

        if (! $dinas) {
            return back()
                ->withErrors([
                    'dinas' => 'Data dinas tidak ditemukan.',
                ])
                ->withInput();
        }

        if (
            ($serahTerimaLama && ! $this->bolehDiedit($serahTerimaLama))
            || $this->dinasTerkunci($dinas)
        ) {
            return back()
                ->withErrors([
                    'dinas' => 'Serah terima sudah dikonfirmasi penerima atau disetujui admin. Data tidak dapat diubah.',
                ])
                ->withInput();
        }

        $penerima = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'pjl'))
            ->where('is_active', true)
            ->findOrFail($validated['penerima_user_id']);

        if ((int) $penerima->id === (int) auth()->id()) {
            throw ValidationException::withMessages([
                'penerima_user_id' => 'Penerima harus petugas PJL lain, bukan akun penyerah.',
            ]);
        }

        $ringkasanMasuk = json_decode(
            $validated['ringkasan'],
            true
        );

        if (! is_array($ringkasanMasuk)) {
            throw ValidationException::withMessages([
                'ringkasan' => 'Format ringkasan serah terima tidak valid.',
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
                'inventaris' => "Masih ada {$jumlahBelum} inventaris yang belum diperiksa. Periksa semua inventaris terlebih dahulu.",
            ]);
        }

        $tandaTanganPenyerahLama =
            $serahTerimaLama?->tanda_tangan_penyerah;

        // Ambil tanda tangan penerima lama sebelum data diperbarui.
        $tandaTanganPenerimaLama =
            $serahTerimaLama?->tanda_tangan_penerima;

        $actionPenyerah =
            $validated['tanda_tangan_penyerah_action']
            ?? (
                ! empty($validated['tanda_tangan_penyerah'])
                    ? 'set'
                    : 'keep'
            );

        $akanAdaPenyerah = match ($actionPenyerah) {
            'clear' => false,
            'set' => ! empty(
                $validated['tanda_tangan_penyerah']
            ),
            default => ! empty($tandaTanganPenyerahLama),
        };

        if (! $akanAdaPenyerah) {
            throw ValidationException::withMessages([
                'tanda_tangan_penyerah' => 'Tanda tangan penyerah wajib diisi sebelum menyimpan serah terima.',
            ]);
        }

        $signaturePenyerah = null;
        $fotoBuktiPath = null;
        $fotoBuktiUpload = $request->file('foto_bersama');
        $fotoBuktiLama = data_get(
            $serahTerimaLama?->ringkasan,
            'foto_bukti_path'
        );

        if (
            $serahTerimaLama
            && ! $fotoBuktiLama
            && ! $request->hasFile('foto_bersama')
        ) {
            throw ValidationException::withMessages([
                'foto_bersama' => 'Foto bersama penyerah dan penerima wajib dilampirkan sebelum menyimpan serah terima.',
            ]);
        }

        try {
            if ($fotoBuktiUpload) {
                $fotoBuktiPath = $fotoBuktiUpload->store(
                    'serah-terima/bukti',
                    'local'
                );

                if (! is_string($fotoBuktiPath)) {
                    throw ValidationException::withMessages([
                        'foto_bersama' => 'Foto bukti gagal disimpan. Silakan coba kembali.',
                    ]);
                }
            }

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
                        'bukti_foto_path' => $hasil?->bukti_foto_path,
                        'diperiksa_pada' => $hasil?->diperiksa_pada?->toDateTimeString(),
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
                    'catatan_penting' => $validated['catatan_penting']
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

            unset($ringkasan['foto_bukti_path']);

            if ($fotoBuktiPath || $fotoBuktiLama) {
                $ringkasan['foto_bukti_path'] = $fotoBuktiPath
                    ?? $fotoBuktiLama;
            }

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
                $serahTerimaLama,
                $activityLogService
            ) {
                Dinas::query()
                    ->lockForUpdate()
                    ->findOrFail($dinas->id);

                $record = null;

                if ($serahTerimaLama) {
                    $record = SerahTerima::query()
                        ->lockForUpdate()
                        ->findOrFail($serahTerimaLama->id);

                    if (! $this->bolehDiedit($record)) {
                        throw ValidationException::withMessages([
                            'dinas' => 'Serah terima sudah dikonfirmasi penerima atau disetujui admin. Data tidak dapat diubah.',
                        ]);
                    }
                }

                if ($this->dinasTerkunci($dinas)) {
                    throw ValidationException::withMessages([
                        'dinas' => 'Serah terima dinas ini sudah disetujui admin dan terkunci.',
                    ]);
                }

                $oldValues = $record === null
                    ? null
                    : [
                        'dinas_id' => (int) $record->dinas_id,
                        'penerima_user_id' => $record->penerima_user_id === null
                            ? null
                            : (int) $record->penerima_user_id,
                        'status' => $record->status,
                        'status_review' => $record->status_review,
                    ];

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

                if ($record) {
                    $record->update($dataSimpan);
                } else {
                    $record = SerahTerima::create($dataSimpan);
                }

                $activityLogService->log(
                    $oldValues === null ? 'create' : 'update',
                    'serah_terima',
                    $oldValues === null
                        ? 'Serah Terima dibuat dan dikonfirmasi oleh penyerah.'
                        : 'Serah Terima diperbarui dan dikonfirmasi kembali oleh penyerah.',
                    $record,
                    $oldValues,
                    [
                        'dinas_id' => (int) $record->dinas_id,
                        'penerima_user_id' => (int) $record->penerima_user_id,
                        'status' => $record->status,
                        'status_review' => $record->status_review,
                        'sender_confirmation' => 'confirmed',
                    ]
                );
            });
        } catch (\Throwable $e) {
            if ($signaturePenyerah) {
                $this->deleteSignature(
                    $signaturePenyerah
                );
            }

            if ($fotoBuktiPath) {
                Storage::disk('local')->delete($fotoBuktiPath);
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
            $this->deleteSignature(
                $tandaTanganPenyerahLama
            );
        }

        if (
            $fotoBuktiLama
            && $fotoBuktiPath
            && $fotoBuktiPath !== $fotoBuktiLama
            && is_string($fotoBuktiLama)
            && str_starts_with($fotoBuktiLama, 'serah-terima/bukti/')
            && ! str_contains($fotoBuktiLama, '..')
        ) {
            Storage::disk('local')->delete($fotoBuktiLama);
        }

        /*
         * Tanda tangan penerima lama tidak lagi valid karena
         * isi serah terima atau penerimanya dapat berubah.
         */
        if ($tandaTanganPenerimaLama) {
            $this->deleteSignature(
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
        $serahTerima,
        ActivityLogService $activityLogService
    ) {
        abort_unless(
            auth()->user()?->hasRole('pjl'),
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

        abort_unless(
            $data->penerima_user_id !== null
                && (int) $data->penerima_user_id
                    === (int) auth()->id(),
            403,
            'Anda bukan petugas penerima yang ditunjuk untuk serah terima ini.'
        );

        abort_if(
            $data->isTerkunci(),
            422,
            'Serah terima ini sudah disetujui dan terkunci.'
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

        abort_unless(
            ! empty($data->tanda_tangan_penyerah),
            422,
            'Tanda tangan penyerah belum tersedia.'
        );

        $signaturePenerima = $this->saveSignature(
            $validated['tanda_tangan_penerima'],
            'penerima'
        );

        try {
            DB::transaction(function () use (
                $data,
                $signaturePenerima,
                $activityLogService
            ) {
                $terkini = SerahTerima::query()
                    ->lockForUpdate()
                    ->findOrFail($data->id);

                abort_unless(
                    $terkini->penerima_user_id !== null
                        && (int) $terkini->penerima_user_id === (int) auth()->id(),
                    403,
                    'Anda bukan penerima yang ditunjuk.'
                );

                abort_if(
                    $terkini->isTerkunci(),
                    422,
                    'Serah terima ini sudah disetujui dan terkunci.'
                );

                abort_unless(
                    $terkini->status === 'menunggu_penerima'
                        && empty($terkini->tanda_tangan_penerima),
                    422,
                    'Serah terima sudah dikonfirmasi atau tidak lagi menunggu penerima.'
                );

                abort_unless(
                    ! empty($terkini->tanda_tangan_penyerah),
                    422,
                    'Tanda tangan penyerah belum tersedia.'
                );

                $oldValues = [
                    'dinas_id' => (int) $terkini->dinas_id,
                    'penerima_user_id' => (int) $terkini->penerima_user_id,
                    'status' => $terkini->status,
                    'status_review' => $terkini->status_review,
                ];

                $terkini->update([
                    'tanda_tangan_penerima' => $signaturePenerima,
                    'status' => 'selesai',
                    'status_review' => 'menunggu_review',
                ]);

                $activityLogService->log(
                    'confirm_recipient',
                    'serah_terima',
                    'Penerima mengonfirmasi Serah Terima dan status berubah menjadi selesai.',
                    $terkini,
                    $oldValues,
                    [
                        'dinas_id' => (int) $terkini->dinas_id,
                        'penerima_user_id' => (int) $terkini->penerima_user_id,
                        'status' => $terkini->status,
                        'status_review' => $terkini->status_review,
                    ]
                );
            });
        } catch (\Throwable $e) {
            $this->deleteSignature($signaturePenerima);

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
        if (! $jam) {
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
                'tanda_tangan_'.$jenis => 'Tanda tangan belum tersedia. Silakan tanda tangan kembali.',
            ]);
        }

        if (! preg_match(
            '/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/',
            $dataUrl,
            $matches
        )) {
            throw ValidationException::withMessages([
                'tanda_tangan_'.$jenis => 'Format tanda tangan harus berupa gambar PNG.',
            ]);
        }

        $image = base64_decode($matches[1], true);

        if (
            $image === false
            || strlen($image) > 1024 * 1024
            || ! str_starts_with($image, "\x89PNG\r\n\x1a\n")
        ) {
            throw ValidationException::withMessages([
                'tanda_tangan_'.$jenis => 'Gambar tanda tangan tidak valid atau melebihi 1 MB.',
            ]);
        }

        $path = 'serah-terima/signatures/'
            .auth()->id()
            .'/'
            .uniqid($jenis.'_', true)
            .'.png';

        if (! Storage::disk('local')->put($path, $image)) {
            throw new \RuntimeException(
                'Gagal menyimpan tanda tangan ke storage.'
            );
        }

        return $path;
    }

    private function deleteSignature(?string $path): void
    {
        if (! is_string($path) || ! $this->isSignaturePath($path)) {
            return;
        }

        Storage::disk('local')->delete($path);
        Storage::disk('public')->delete($path);
    }

    private function isSignaturePath(string $path): bool
    {
        return preg_match('#^serah-terima/(?:signatures/)?[0-9]+/[A-Za-z0-9_.-]+\.png$#D', $path) === 1;
    }
}

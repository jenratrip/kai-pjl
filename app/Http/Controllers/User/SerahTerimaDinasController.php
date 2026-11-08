<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CatatanDinas;
use App\Models\Dinas;
use App\Models\Inventaris;
use App\Models\RealisasiInventaris;
use App\Models\RealisasiKa;
use App\Models\SerahTerima;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SerahTerimaDinasController extends Controller
{
    /**
     * Mengambil serah terima yang dimiliki pengguna
     * atau ditujukan kepada pengguna sebagai penerima.
     */
    private function querySerahTerimaUser()
    {
        $userId = auth()->id();

        return SerahTerima::query()
            ->where(function ($query) use ($userId) {
                $query->milikUser($userId)
                    ->orWhere('penerima_user_id', $userId);
            });
    }

    /**
     * Mendapatkan Dinas aktif milik user yang login.
     */
    private function dinasAktif(): ?Dinas
    {
        return Dinas::with([
            'user',
            'userDigantikan',
            'jpl.resort.wilayah',
            'shift',
        ])
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();
    }

    /**
     * Menentukan Dinas yang menjadi sumber bersama pemeriksaan inventaris.
     */
    private function dinasSumberInventaris(Dinas $dinas): Dinas
    {
        if (
            ! $dinas->is_pengganti
            || ! $dinas->user_digantikan_id
        ) {
            return $dinas;
        }

        $dinasUtama = Dinas::query()
            ->where('jpl_id', $dinas->jpl_id)
            ->where('shift_id', $dinas->shift_id)
            ->whereDate('tanggal_dinas', $dinas->tanggal_dinas)
            ->where('is_pengganti', false)
            ->where('user_id', $dinas->user_digantikan_id)
            ->latest('id')
            ->first();

        return $dinasUtama ?? $dinas;
    }

    /**
     * Mendapatkan ID Dinas utama dan wakilan dalam satu periode.
     */
    private function idDinasSatuPeriode(Dinas $dinasSumber): array
    {
        $query = Dinas::query()
            ->where('jpl_id', $dinasSumber->jpl_id)
            ->where('shift_id', $dinasSumber->shift_id)
            ->whereDate('tanggal_dinas', $dinasSumber->tanggal_dinas)
            ->where(function ($query) use ($dinasSumber) {
                $query->where('id', $dinasSumber->id);

                if (! $dinasSumber->is_pengganti) {
                    $query->orWhere(function ($wakilan) use ($dinasSumber) {
                        $wakilan
                            ->where('is_pengganti', true)
                            ->where(
                                'user_digantikan_id',
                                $dinasSumber->user_id
                            );
                    });
                }
            });

        return $query
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Menyiapkan hasil pemeriksaan inventaris periode berjalan.
     */
    private function siapkanRealisasiInventaris(Dinas $dinasSumber): void
    {
        $inventarisAktif = Inventaris::query()
            ->where('jpl_id', $dinasSumber->jpl_id)
            ->where('is_active', true)
            ->get();

        if ($inventarisAktif->isEmpty()) {
            return;
        }

        $idDinasPeriodeIni = $this->idDinasSatuPeriode($dinasSumber);

        foreach ($inventarisAktif as $item) {
            $sudahAda = RealisasiInventaris::query()
                ->where('dinas_id', $dinasSumber->id)
                ->where('inventaris_id', $item->id)
                ->exists();

            if ($sudahAda) {
                continue;
            }

            $hasilTerakhir = RealisasiInventaris::query()
                ->join(
                    'dinas',
                    'dinas.id',
                    '=',
                    'realisasi_inventaris.dinas_id'
                )
                ->where('dinas.jpl_id', $dinasSumber->jpl_id)
                ->whereNotIn('dinas.id', $idDinasPeriodeIni)
                ->where('realisasi_inventaris.inventaris_id', $item->id)
                ->where(function ($query) use ($dinasSumber) {
                    $query->whereDate(
                        'dinas.tanggal_dinas',
                        '<',
                        $dinasSumber->tanggal_dinas
                    );

                    $query->orWhere(function ($tanggalSama) use ($dinasSumber) {
                        $tanggalSama->whereDate(
                            'dinas.tanggal_dinas',
                            $dinasSumber->tanggal_dinas
                        );

                        if ($dinasSumber->jam_mulai) {
                            $tanggalSama->where(
                                'dinas.jam_mulai',
                                '<',
                                $dinasSumber->jam_mulai
                            );
                        } else {
                            $tanggalSama->where(
                                'dinas.id',
                                '<',
                                $dinasSumber->id
                            );
                        }
                    });
                })
                ->orderByDesc('dinas.tanggal_dinas')
                ->orderByDesc('dinas.jam_mulai')
                ->orderByDesc('dinas.id')
                ->select('realisasi_inventaris.*')
                ->first();

            if (! $hasilTerakhir) {
                continue;
            }

            RealisasiInventaris::create([
                'dinas_id' => $dinasSumber->id,
                'inventaris_id' => $item->id,
                'status' => $hasilTerakhir->status,
                'kondisi' => $hasilTerakhir->kondisi,
                'catatan' => $hasilTerakhir->catatan,
                'jumlah_aktual' => $hasilTerakhir->jumlah_aktual,
                'diperiksa_pada' => $hasilTerakhir->diperiksa_pada,
                'diperiksa_oleh' => $hasilTerakhir->diperiksa_oleh,
            ]);
        }
    }

    /**
     * Apakah Dinas ini sudah mempunyai serah terima
     * yang disetujui admin.
     */
    private function dinasTerkunci(Dinas $dinas): bool
    {
        return SerahTerima::query()
            ->where('dinas_id', $dinas->id)
            ->whereIn('status_review', SerahTerima::STATUS_TERKUNCI)
            ->exists();
    }

    /**
     * Apakah serah terima sudah dikonfirmasi penerima
     * dan sedang menunggu tinjauan admin.
     */
    private function menungguReview(SerahTerima $serahTerima): bool
    {
        return $serahTerima->status === 'selesai'
            && $serahTerima->status_review === 'menunggu_review';
    }

    /**
     * Apakah pengirim boleh mengubah serah terima.
     *
     * Pengirim hanya boleh mengubah:
     * - Menunggu tanda tangan penerima.
     * - Data yang diminta revisi oleh admin.
     */
    private function bolehDiedit(SerahTerima $serahTerima): bool
    {
        if ($serahTerima->isTerkunci()) {
            return false;
        }

        if ($serahTerima->status_review === 'perlu_revisi') {
            return true;
        }

        return $serahTerima->status === 'menunggu_penerima';
    }

    /**
     * Mengembalikan respons penolakan jika Dinas sudah terkunci,
     * atau null jika masih boleh diubah.
     */
    private function tolakJikaTerkunci(Request $request, Dinas $dinas)
    {
        if (! $this->dinasTerkunci($dinas)) {
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
     * Riwayat serah terima milik user yang login,
     * termasuk dokumen yang ditujukan kepadanya sebagai penerima.
     */
    public function riwayat()
    {
        $dinasAktif = $this->dinasAktif();
        $userId = auth()->id();

        $relasi = [
            'dinas.user',
            'dinas.userDigantikan',
            'dinas.jpl.resort.wilayah',
            'dinas.shift',
            'penerima',
        ];

        $riwayat = $this->querySerahTerimaUser()
            ->with($relasi)
            ->latest('waktu_serah_terima')
            ->latest('id')
            ->paginate(10, ['*'], 'riwayat_page');

        // Riwayat masuk: user login bertindak sebagai penerima.
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
     * Membuka data serah terima untuk diedit oleh penyerah
     * atau dikonfirmasi oleh penerima.
     */
    public function edit($serahTerima)
    {
        $data = $this->querySerahTerimaUser()
            ->with([
                'dinas.user',
                'dinas.userDigantikan',
                'dinas.jpl.resort.wilayah',
                'dinas.shift',
                'penerima',
            ])
            ->findOrFail($serahTerima);

        $isPenerima = (int) $data->penerima_user_id === (int) auth()->id();

        /*
         * Jika yang membuka adalah penerima,
         * jangan pernah berikan mode edit milik penyerah.
         */
        if ($isPenerima) {
            if (
                $data->status === 'menunggu_penerima'
                && empty($data->tanda_tangan_penerima)
                && ! $data->isTerkunci()
            ) {
                return $this->tampilkanHalaman(
                    $data->dinas,
                    $data,
                    false,
                    false,
                    true
                );
            }

            return $this->tampilkanHalaman(
                $data->dinas,
                $data,
                false,
                true
            );
        }

        /*
         * Penyerah tidak boleh mengedit setelah penerima
         * membubuhkan tanda tangan, meskipun admin belum review.
         */
        if (
            $data->status === 'selesai'
            || $data->isTerkunci()
        ) {
            return redirect()->route(
                'user.serah-terima-dinas.show',
                $data
            );
        }

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
     * Menampilkan detail serah terima.
     *
     * Penerima yang belum menandatangani akan diarahkan
     * ke mode konfirmasi. Dokumen yang sudah selesai
     * hanya dapat dilihat oleh kedua pihak.
     */
    public function show($serahTerima)
    {
        $data = $this->querySerahTerimaUser()
            ->with([
                'dinas.user',
                'dinas.userDigantikan',
                'dinas.jpl.resort.wilayah',
                'dinas.shift',
                'penerima',
            ])
            ->findOrFail($serahTerima);

        $isPenyerah = (int) $data->dinas->user_id === (int) auth()->id();
        $isPenerima = (int) $data->penerima_user_id === (int) auth()->id();

        abort_unless($isPenyerah || $isPenerima, 403);

        if ($isPenerima) {
            if (
                $data->status === 'menunggu_penerima'
                && empty($data->tanda_tangan_penerima)
                && ! $data->isTerkunci()
            ) {
                return $this->tampilkanHalaman(
                    $data->dinas,
                    $data,
                    false,
                    false,
                    true
                );
            }

            return $this->tampilkanHalaman(
                $data->dinas,
                $data,
                false,
                true
            );
        }

        /*
         * Penyerah masih dapat mengedit selama penerima belum
         * menandatangani dan dokumen belum dikunci admin.
         */
        if (
            $data->status !== 'selesai'
            && ! $data->isTerkunci()
            && $this->bolehDiedit($data)
        ) {
            return redirect()->route(
                'user.serah-terima-dinas.edit',
                $data
            );
        }

        $modeLihat = ! $this->bolehDiedit($data);

        return $this->tampilkanHalaman(
            $data->dinas,
            $data,
            false,
            $modeLihat,
            false
        );
    }

    /**
     * Menyiapkan data untuk halaman serah terima.
     *
     * Dinas:
     * Data Dinas untuk konten serah terima.
     *
     * Dinas aktif:
     * Data Dinas aktif user login untuk kebutuhan header.
     *
     * Inventaris:
     * Jika Dinas masih berlangsung, gunakan sumber bersama.
     * Jika Dinas historis, tetap gunakan hasil milik Dinas tersebut.
     */
    private function tampilkanHalaman(
        ?Dinas $dinas,
        ?SerahTerima $serahTerima = null,
        bool $modeEdit = false,
        bool $modeLihat = false,
        bool $modeKonfirmasiPenerima = false
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
                ->where(function ($query) use ($dinas) {
                    $query->whereNull('jpl_id')
                        ->orWhere('jpl_id', $dinas->jpl_id);
                })
                ->orderBy('nama_barang')
                ->get();

            /*
             * Dinas aktif memakai sumber inventaris bersama.
             * Dinas historis tetap membaca hasil miliknya sendiri.
             */
            $dinasInventaris = $dinas;

            if ($dinas->status === 'berlangsung') {
                $dinasInventaris = $this->dinasSumberInventaris($dinas);

                DB::transaction(function () use ($dinasInventaris) {
                    $this->siapkanRealisasiInventaris($dinasInventaris);
                });
            }

            $realisasi = RealisasiInventaris::query()
                ->where('dinas_id', $dinasInventaris->id)
                ->whereIn('inventaris_id', $inventaris->pluck('id'))
                ->get()
                ->keyBy('inventaris_id');
        }

        /*
         * Dokumen yang sudah ditandatangani penerima atau
         * sudah dikunci admin tidak boleh masuk mode konfirmasi.
         */
        if (
            $serahTerima?->status === 'selesai'
            || $serahTerima?->isTerkunci()
        ) {
            $modeLihat = true;
            $modeKonfirmasiPenerima = false;
            $modeEdit = false;
        }

        if ($serahTerima && ! $this->bolehDiedit($serahTerima)) {
            $modeLihat = true;
        }

        if ($modeKonfirmasiPenerima) {
            $modeEdit = false;
            $modeLihat = false;
        }

        if ($modeLihat) {
            $modeEdit = false;
            $modeKonfirmasiPenerima = false;
        }

        /*
         * Ambil Dinas aktif secara terpisah.
         * Jangan gunakan Dinas historis sebagai Dinas aktif.
         */
        $dinasAktif = $this->dinasAktif();

        return view('user.serahterimacatatan.index', [
            'dinasAktif' => $dinasAktif,
            'dinas' => $dinas,
            'catatan' => $catatan,
            'inventaris' => $inventaris,
            'realisasi' => $realisasi,
            'serahTerima' => $serahTerima,
            'dataRealisasiKA' => $dataRealisasiKA,
            'ringkasanKA' => $ringkasanKA,
            'modeEdit' => $modeEdit,
            'modeLihat' => $modeLihat,

            // Kompatibilitas dengan nama variabel Blade sebelumnya.
            'modePenerima' => $modeKonfirmasiPenerima,
            'modeKonfirmasiPenerima' => $modeKonfirmasiPenerima,
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
                ->filter(fn ($ka) => strtoupper($ka['status'] ?? '') === 'BATAL')
                ->values()
                ->all(),

            'berjalan' => $dataRealisasiKA
                ->filter(fn ($ka) => strtoupper($ka['status'] ?? '') === 'JALAN')
                ->values()
                ->all(),

            'belum' => $dataRealisasiKA
                ->filter(fn ($ka) => strtoupper($ka['status'] ?? '') === 'BELUM')
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
                $dataTersimpan = array_merge(
                    $dataTersimpan,
                    $ringkasan[$key]
                );
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
     * Mengambil jadwal KA dan realisasi milik Dinas.
     */
    private function ambilDataKA(Dinas $dinas)
    {
        $snapshot = $dinas->jadwal_ka_snapshot;

        $jadwalList = is_array($snapshot)
            && is_array($snapshot['jadwal'] ?? null)
                ? $snapshot['jadwal']
                : [];

        $realisasiTerjadwal = RealisasiKa::with([
            'keretaApi',
            'jadwalKa',
        ])
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
                'aktual' => $this->formatJam($realisasiKA?->jam_aktual),
                'status' => $this->formatStatus($realisasiKA?->status_lewat),
                'semboyan21' => $this->checkValue($realisasiKA?->status_s21),
                'genta' => $this->checkValue($realisasiKA?->status_genta),
                'pintu' => $this->checkValue($realisasiKA?->status_pintu_perlintasan),
                'semboyan35' => $this->checkValue($realisasiKA?->status_s35),
                'keterangan' => $realisasiKA?->catatan ?? '',
                'jadwal_ka_id' => $jadwalId,
                'is_manual' => false,
            ];
        }

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
                'aktual' => $this->formatJam($realisasiKA->jam_aktual),
                'status' => $this->formatStatus($realisasiKA->status_lewat),
                'semboyan21' => $this->checkValue($realisasiKA->status_s21),
                'genta' => $this->checkValue($realisasiKA->status_genta),
                'pintu' => $this->checkValue($realisasiKA->status_pintu_perlintasan),
                'semboyan35' => $this->checkValue($realisasiKA->status_s35),
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
        if (! $dinas->tanggal_dinas) {
            $query->whereRaw('1 = 0');

            return;
        }

        $tanggalDinas = Carbon::parse($dinas->tanggal_dinas);
        $namaHari = $this->namaHariIndonesia($tanggalDinas);

        $query->where(function ($q) use ($tanggalDinas, $namaHari) {
            $q->whereDate(
                'tanggal_berlaku',
                $tanggalDinas->toDateString()
            )->orWhere(function ($q2) use ($namaHari) {
                $q2->whereNull('tanggal_berlaku')
                    ->where(function ($hariQuery) use ($namaHari) {
                        $hariQuery
                            ->whereJsonContains('hari_berlaku', $namaHari)
                            ->orWhereJsonContains('hari_berlaku', 'Setiap Hari');
                    });
            });
        });
    }

    private function namaHariIndonesia(Carbon $tanggal): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
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

        if (! $dinas) {
            $pesanError = 'Tidak ada dinas aktif. Catatan tidak dapat disimpan.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $pesanError,
                ], 422);
            }

            return back()->withErrors(['dinas' => $pesanError])->withInput();
        }

        try {
            $catatan = DB::transaction(function () use ($dinas, $validated) {
                Dinas::query()
                    ->lockForUpdate()
                    ->findOrFail($dinas->id);

                if ($this->dinasTerkunci($dinas)) {
                    throw ValidationException::withMessages([
                        'dinas' => 'Serah terima dinas ini sudah disetujui admin. Catatan tidak dapat diubah.',
                    ]);
                }

                return CatatanDinas::create([
                    'dinas_id' => $dinas->id,
                    'user_id' => auth()->id(),
                    'judul' => $validated['judul'],
                    'kategori' => $validated['kategori'],
                    'isi_catatan' => $validated['isi_catatan'],
                    'status' => 'baru',
                    'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
                    'waktu_catatan' => now(),
                ]);
            });
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => collect($e->errors())->flatten()->first(),
                ], 422);
            }

            throw $e;
        }

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
     *
     * Petugas utama dan wakilan menyimpan pemeriksaan
     * pada satu sumber data inventaris bersama.
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
                Rule::in(['baik', 'perlu_perbaikan', 'rusak', 'hilang']),
            ],
            'jumlah_aktual' => ['required', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $dinas = $this->dinasAktif();

        if (! $dinas) {
            $pesanError = 'Tidak ada dinas aktif untuk pemeriksaan inventaris.';

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

        /*
         * Periksa kunci Dinas milik user.
         */
        if ($tolak = $this->tolakJikaTerkunci($request, $dinas)) {
            return $tolak;
        }

        /*
         * Tentukan sumber bersama.
         */
        $dinasSumber = $this->dinasSumberInventaris($dinas);

        /*
         * Jika sumber bersama sudah terkunci, pemeriksaan juga
         * tidak boleh diubah melalui akun wakilan.
         */
        if ($dinasSumber->id !== $dinas->id) {
            if ($tolak = $this->tolakJikaTerkunci($request, $dinasSumber)) {
                return $tolak;
            }
        }

        $inventaris = Inventaris::query()
            ->whereKey($validated['inventaris_id'])
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless(
            $inventaris->jpl_id === null
                || (int) $inventaris->jpl_id === (int) $dinas->jpl_id,
            403,
            'Inventaris ini bukan bagian dari penempatan JPL dinas Anda.'
        );

        $jumlahAktual = (int) $validated['jumlah_aktual'];
        $jumlahMaster = (int) $inventaris->jumlah;

        $adaMasalah = $validated['kondisi'] !== 'baik'
            || $jumlahAktual !== $jumlahMaster;

        DB::transaction(function () use (
            $dinasSumber,
            $inventaris,
            $validated,
            $jumlahAktual,
            $adaMasalah
        ) {
            /*
             * Pastikan hasil periode berjalan sudah disiapkan.
             */
            $this->siapkanRealisasiInventaris($dinasSumber);

            RealisasiInventaris::updateOrCreate(
                [
                    'dinas_id' => $dinasSumber->id,
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
                        $inventaris->status_tinjauan ?: 'perlu_tinjauan';
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
            'serah_terima_id' => ['nullable', 'integer'],
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
            'ringkasan' => ['required', 'string', 'max:500000'],
            'catatan_penting' => ['nullable', 'string', 'max:10000'],
            'ppka' => ['nullable', 'string', 'max:255'],
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

        $dariRiwayat = ! empty($validated['serah_terima_id']);

        if ($dariRiwayat) {
            /*
             * Pengeditan dokumen hanya boleh dilakukan pemilik/penyerah.
             * Penerima tidak menggunakan endpoint store ini.
             */
            $serahTerimaLama = SerahTerima::with('dinas')
                ->milikUser(auth()->id())
                ->findOrFail($validated['serah_terima_id']);

            $dinas = $serahTerimaLama->dinas;

            if (! $this->bolehDiedit($serahTerimaLama)) {
                return back()
                    ->withErrors([
                        'dinas' => 'Serah terima sudah dikonfirmasi penerima atau sudah disetujui admin. Data tidak dapat diubah.',
                    ])
                    ->withInput();
            }
        } else {
            $dinas = $this->dinasAktif();

            if (! $dinas) {
                return back()
                    ->withErrors([
                        'dinas' => 'Tidak ada dinas aktif untuk diserahterimakan.',
                    ])
                    ->withInput();
            }

            $serahTerimaLama = SerahTerima::where('dinas_id', $dinas->id)
                ->latest('id')
                ->first();

            if (
                $serahTerimaLama
                && ! $this->bolehDiedit($serahTerimaLama)
            ) {
                return back()
                    ->withErrors([
                        'dinas' => 'Serah terima sudah dikonfirmasi penerima atau disetujui admin. Data tidak dapat diubah.',
                    ])
                    ->withInput();
            }
        }

        if (! $dinas) {
            return back()
                ->withErrors(['dinas' => 'Data dinas tidak ditemukan.'])
                ->withInput();
        }

        /*
         * Dokumen yang sudah selesai ditandatangani penerima
         * tidak boleh diedit lagi oleh penyerah.
         */
        if (
            $serahTerimaLama?->status === 'selesai'
            || $serahTerimaLama?->isTerkunci()
            || $this->dinasTerkunci($dinas)
        ) {
            return back()
                ->withErrors([
                    'dinas' => 'Serah terima ini sudah ditandatangani penerima atau disetujui admin. Data tidak dapat diubah.',
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

        $ringkasanMasuk = json_decode($validated['ringkasan'], true);

        if (! is_array($ringkasanMasuk)) {
            throw ValidationException::withMessages([
                'ringkasan' => 'Format ringkasan serah terima tidak valid.',
            ]);
        }

        /*
         * Semua inventaris aktif pada JPL harus diperiksa
         * sebelum serah terima dapat disimpan.
         *
         * Untuk Dinas aktif, gunakan sumber pemeriksaan bersama.
         * Untuk pengeditan riwayat, gunakan data Dinas historis sendiri.
         */
        $inventarisAktif = Inventaris::query()
            ->where('is_active', true)
            ->where(function ($query) use ($dinas) {
                $query->whereNull('jpl_id')
                    ->orWhere('jpl_id', $dinas->jpl_id);
            })
            ->orderBy('nama_barang')
            ->get();

        $idInventarisAktif = $inventarisAktif->pluck('id');

        $dinasInventaris = $dinas;

        if (! $dariRiwayat && $dinas->status === 'berlangsung') {
            $dinasInventaris = $this->dinasSumberInventaris($dinas);
        }

        if (! $dariRiwayat) {
            /*
             * Siapkan data inventaris periode berjalan.
             * Hasil Dinas sebelumnya akan disalin jika belum tersedia.
             */
            DB::transaction(function () use ($dinasInventaris) {
                $this->siapkanRealisasiInventaris($dinasInventaris);
            });
        }

        $realisasiInventaris = RealisasiInventaris::query()
            ->where('dinas_id', $dinasInventaris->id)
            ->whereIn('inventaris_id', $idInventarisAktif)
            ->get()
            ->keyBy('inventaris_id');

        $jumlahBelum = $idInventarisAktif
            ->diff($realisasiInventaris->keys())
            ->count();

        if ($jumlahBelum > 0) {
            throw ValidationException::withMessages([
                'inventaris' => "Masih ada {$jumlahBelum} inventaris yang belum diperiksa. Periksa semua inventaris terlebih dahulu.",
            ]);
        }

        $tandaTanganPenyerahLama = $serahTerimaLama?->tanda_tangan_penyerah;
        $tandaTanganPenerimaLama = $serahTerimaLama?->tanda_tangan_penerima;

        $actionPenyerah =
            $validated['tanda_tangan_penyerah_action']
            ?? (
                ! empty($validated['tanda_tangan_penyerah'])
                    ? 'set'
                    : 'keep'
            );

        $akanAdaPenyerah = match ($actionPenyerah) {
            'clear' => false,
            'set' => ! empty($validated['tanda_tangan_penyerah']),
            default => ! empty($tandaTanganPenyerahLama),
        };

        if (! $akanAdaPenyerah) {
            throw ValidationException::withMessages([
                'tanda_tangan_penyerah' => 'Tanda tangan penyerah wajib diisi sebelum menyimpan serah terima.',
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
             * Ambil data KA terbaru dari Dinas masing-masing.
             */
            $dataKA = $this->ambilDataKA($dinas)->all();
            $ringkasanKA = $this->buatRingkasanKA($dataKA);

            /*
             * Snapshot inventaris berasal dari sumber bersama,
             * tetapi disimpan ke dokumen Dinas masing-masing.
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
                        'diperiksa_pada' => $hasil?->diperiksa_pada?->toDateTimeString(),
                        'diperiksa_oleh' => $hasil?->diperiksa_oleh,
                    ];
                })
                ->values()
                ->all();

            $catatanSerahTerima =
                $ringkasanMasuk['catatan_serah_terima']
                ?? $ringkasanMasuk['catatan']
                ?? $validated['catatan_penting']
                ?? '';

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

                    'ppka' => $userPenyerah->name ?? '',
                    'nama_ppka' => $userPenyerah->name ?? '',
                    'nip_ppka' => $userPenyerah->nip ?? '',

                    'nama_penerima' => $penerima->name,
                    'nip_penerima' => $penerima->nip ?? '',

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

                $dataSimpan = [
                    /*
                     * Tetap menggunakan Dinas milik masing-masing.
                     */
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
                    SerahTerima::create($dataSimpan);
                }
            });
        } catch (\Throwable $e) {
            if ($signaturePenyerah) {
                Storage::disk('public')->delete($signaturePenyerah);
            }

            throw $e;
        }

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
            Storage::disk('public')->delete($tandaTanganPenyerahLama);
        }

        if ($tandaTanganPenerimaLama) {
            Storage::disk('public')->delete($tandaTanganPenerimaLama);
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
    public function konfirmasi(Request $request, $serahTerima)
    {
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
                && (int) $data->penerima_user_id === (int) auth()->id(),
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
            ! empty($data->tanda_tangan_penyerah),
            422,
            'Tanda tangan penyerah belum tersedia.'
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
            DB::transaction(function () use ($data, $signaturePenerima) {
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

        return redirect()
            ->route('user.serah-terima-dinas.riwayat')
            ->with(
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
    private function saveSignature(?string $dataUrl, string $jenis): ?string
    {
        if (empty($dataUrl)) {
            throw ValidationException::withMessages([
                'tanda_tangan_' . $jenis => 'Tanda tangan belum tersedia. Silakan tanda tangan kembali.',
            ]);
        }

        if (! preg_match(
            '/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/',
            $dataUrl,
            $matches
        )) {
            throw ValidationException::withMessages([
                'tanda_tangan_' . $jenis => 'Format tanda tangan harus berupa gambar PNG.',
            ]);
        }

        $image = base64_decode($matches[1], true);

        if (
            $image === false
            || strlen($image) > 1024 * 1024
            || ! str_starts_with($image, "\x89PNG\r\n\x1a\n")
        ) {
            throw ValidationException::withMessages([
                'tanda_tangan_' . $jenis => 'Gambar tanda tangan tidak valid atau melebihi 1 MB.',
            ]);
        }

        $path = 'serah-terima/'
            . auth()->id()
            . '/'
            . uniqid($jenis . '_', true)
            . '.png';

        if (! Storage::disk('public')->put($path, $image)) {
            throw new \RuntimeException(
                'Gagal menyimpan tanda tangan ke storage.'
            );
        }

        return $path;
    }
}

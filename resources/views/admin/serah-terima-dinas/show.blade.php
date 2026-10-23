@extends('layouts.admin')

@section('title', 'Detail Serah Terima Dinas')

@section('content')
@php
    $status = $serahTerima->status;

    $statusClass = match ($status) {
        'selesai' => 'bg-green-100 text-green-800',
        'menunggu_penerima' => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };

    $statusLabel = match ($status) {
        'selesai' => 'Selesai',
        'menunggu_penerima' => 'Menunggu konfirmasi penerima',
        default => ucfirst(str_replace('_', ' ', $status ?? 'Tidak diketahui')),
    };

    /*
     * Normalisasi ringkasan JSON agar kompatibel dengan
     * data array maupun string JSON dari database.
     */
    $ringkasan = $serahTerima->ringkasan;

    if (is_string($ringkasan)) {
        $decoded = json_decode($ringkasan, true);

        $ringkasan = is_array($decoded)
            ? $decoded
            : ['catatan_serah_terima' => $ringkasan];
    }

    $ringkasan = is_array($ringkasan) ? $ringkasan : [];

    $ambil = function (array $data, array $keys, $default = null) {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return $default;
    };

    $formatNilai = function ($nilai) use (&$formatNilai) {
        if (is_array($nilai)) {
            if ($nilai === []) {
                return '—';
            }

            $hasil = [];

            foreach ($nilai as $key => $value) {
                $teks = $formatNilai($value);

                if (is_string($key)) {
                    $label = ucwords(str_replace(
                        ['_', '-'],
                        ' ',
                        $key
                    ));

                    $hasil[] = $label . ': ' . $teks;
                } else {
                    $hasil[] = $teks;
                }
            }

            return implode('; ', $hasil);
        }

        if (is_bool($nilai)) {
            return $nilai ? 'Ya' : 'Tidak';
        }

        if (is_scalar($nilai)) {
            $teks = trim((string) $nilai);

            return $teks !== '' ? $teks : '—';
        }

        return '—';
    };

    $labelKunci = function ($key) {
        return ucwords(str_replace(
            ['_', '-'],
            ' ',
            (string) $key
        ));
    };

    $alat = $ambil($ringkasan, [
        'alat',
        'alat_pengamanan',
        'kondisi_alat',
    ], []);

    $alatCatatan = $ambil($ringkasan, [
        'alat_catatan',
        'catatan_alat',
    ], []);

    $kaBatal = $ambil($ringkasan, [
        'ka_batal',
        'ka_batal_data',
    ], []);

    $kaBerjalan = $ambil($ringkasan, [
        'ka_berjalan',
        'ka_berjalan_data',
    ], []);

    $kaBelumLewat = $ambil($ringkasan, [
        'ka_belum_lewat',
        'ka_belum_lewat_data',
    ], []);

    $catatan = $ambil($ringkasan, [
        'catatan_serah_terima',
        'catatan',
    ]);

    $keysDikenal = [
        'alat',
        'alat_pengamanan',
        'kondisi_alat',
        'alat_catatan',
        'catatan_alat',
        'ka_batal',
        'ka_batal_data',
        'ka_berjalan',
        'ka_berjalan_data',
        'ka_belum_lewat',
        'ka_belum_lewat_data',
        'catatan_serah_terima',
        'catatan',
    ];

    $keysDisembunyikan = ['nama_penerima'];

    $dataTambahan = collect($ringkasan)->reject(
        fn ($value, $key) =>
            in_array((string) $key, $keysDikenal, true)
            || in_array((string) $key, $keysDisembunyikan, true)
    );

    /*
     * Nama penerima berasal dari ringkasan atau akun penerima.
     */
    $namaPenerima = data_get($ringkasan, 'nama_penerima')
        ?: ($serahTerima->penerima?->name ?? '—');

    /*
     * Status review administrasi.
     */
    $statusReview = $serahTerima->status_review ?? 'menunggu_review';

    $statusReviewClass = match ($statusReview) {
        'disetujui' => 'bg-green-100 text-green-800',
        'perlu_revisi' => 'bg-red-100 text-red-800',
        default => 'bg-amber-100 text-amber-800',
    };

    $statusReviewLabel = match ($statusReview) {
        'disetujui' => 'Disetujui',
        'perlu_revisi' => 'Perlu Revisi',
        default => 'Menunggu Review',
    };

    /*
     * Admin hanya boleh melakukan review setelah penerima
     * membubuhkan tanda tangan.
     */
    $penerimaanTerkonfirmasi = filled(
        $serahTerima->tanda_tangan_penerima
    );

    /*
     * Review yang sudah disetujui tidak dapat diproses ulang
     * melalui halaman ini.
     */
    $reviewSudahDisetujui = $statusReview === 'disetujui';
@endphp

<div class="mx-auto max-w-7xl px-5 py-8 sm:px-8">

    {{-- ===================================================== --}}
    {{-- HEADER --}}
    {{-- ===================================================== --}}

    <div class="mb-6">
        <a
            href="{{ route('admin.serah-terima-dinas.index') }}"
            class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-blue-900"
        >
            <span aria-hidden="true">←</span>
            Kembali ke daftar pengajuan
        </a>

        <div class="mt-4 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-3xl font-bold text-[#1d2d50]">
                    Detail Serah Terima Dinas
                </h1>

                <p class="mt-2 text-slate-500">
                    Periksa informasi serah terima, tanda tangan petugas,
                    dan hasil review administrasi.
                </p>
            </div>

            <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-semibold {{ $statusClass }}">
                {{ $statusLabel }}
            </span>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- NOTIFIKASI --}}
    {{-- ===================================================== --}}

    @if (session('success'))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
            <span class="font-bold" aria-hidden="true">✓</span>

            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="font-semibold">
                Pengajuan belum dapat diproses.
            </p>

            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===================================================== --}}
    {{-- INFORMASI PENGAJUAN --}}
    {{-- ===================================================== --}}

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-bold text-[#1d2d50]">
            Informasi Pengajuan
        </h2>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

            <div>
                <p class="text-sm text-slate-500">
                    Petugas PJL Penyerah
                </p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->dinasPenyerah?->user?->name ?? 'Tidak tersedia' }}
                </p>

                <p class="text-sm text-slate-500">
                    NIP:
                    {{ $serahTerima->dinasPenyerah?->user?->nip ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">
                    JPL
                </p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->dinasPenyerah?->jpl?->nama_jpl
                        ?? $serahTerima->dinasPenyerah?->jpl?->kode_jpl
                        ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">
                    Waktu Serah Terima
                </p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->waktu_serah_terima
                        ? $serahTerima->waktu_serah_terima->format('d/m/Y H:i')
                        : '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">
                    Shift Penyerah
                </p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->dinasPenyerah?->shift?->nama_shift ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">
                    Petugas Penerima
                </p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $namaPenerima }}
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    Petugas pada shift berikutnya.
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">
                    ID Pengajuan
                </p>

                <p class="mt-1 font-semibold text-slate-800">
                    #{{ $serahTerima->id }}
                </p>
            </div>

        </div>
    </section>

    {{-- ===================================================== --}}
    {{-- RINGKASAN SERAH TERIMA --}}
    {{-- ===================================================== --}}

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h2 class="text-lg font-bold text-[#1d2d50]">
                Ringkasan Serah Terima
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Rangkuman kondisi peralatan, perjalanan KA,
                dan catatan petugas PJL.
            </p>
        </div>

        <div class="space-y-6">

            {{-- Kondisi alat --}}
            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    1. Kondisi Alat Pengamanan
                </h3>

                @if (is_array($alat) && count($alat))
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($alat as $nama => $kondisi)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm text-slate-500">
                                    {{ is_string($nama) ? $labelKunci($nama) : 'Peralatan' }}
                                </p>

                                <p class="mt-1 font-semibold text-slate-800">
                                    {{ $formatNilai($kondisi) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl bg-slate-50 p-4 text-slate-500">
                        Belum ada data kondisi alat.
                    </p>
                @endif
            </section>

            {{-- Catatan alat --}}
            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    2. Catatan Alat Pengamanan
                </h3>

                @if (is_array($alatCatatan) && count($alatCatatan))
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($alatCatatan as $nama => $catatanAlat)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">
                                    {{ is_string($nama) ? $labelKunci($nama) : 'Catatan alat' }}
                                </p>

                                <p class="mt-1 whitespace-pre-wrap break-words text-slate-800">
                                    {{ $formatNilai($catatanAlat) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl border border-slate-200 p-4 text-slate-500">
                        Tidak ada catatan alat.
                    </p>
                @endif
            </section>

            <div class="border-t border-slate-100"></div>

            {{-- Data perjalanan KA --}}
            @foreach ([
                '3. KA Batal' => $kaBatal,
                '4. KA Berjalan' => $kaBerjalan,
                '5. KA Belum Lewat' => $kaBelumLewat,
            ] as $judul => $dataPerjalanan)
                <section>
                    <h3 class="mb-3 font-semibold text-slate-800">
                        {{ $judul }}
                    </h3>

                    <div class="rounded-xl border border-slate-200 p-4">
                        @if (is_array($dataPerjalanan) && count($dataPerjalanan))
                            <ul class="list-inside list-disc space-y-2 text-slate-700">
                                @foreach ($dataPerjalanan as $ka)
                                    <li class="break-words">
                                        {{ $formatNilai($ka) }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-slate-500">
                                Tidak ada data.
                            </p>
                        @endif
                    </div>
                </section>
            @endforeach

            {{-- Catatan serah terima --}}
            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    6. Catatan Serah Terima
                </h3>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="whitespace-pre-wrap break-words text-slate-700">{{ $formatNilai($catatan) }}</p>
                </div>
            </section>

            {{-- Informasi tambahan --}}
            @if ($dataTambahan->isNotEmpty())
                <section class="border-t border-slate-100 pt-5">
                    <h3 class="mb-3 font-semibold text-slate-800">
                        Informasi Tambahan
                    </h3>

                    <div class="space-y-3">
                        @foreach ($dataTambahan as $key => $value)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">
                                    {{ $labelKunci($key) }}
                                </p>

                                <p class="mt-1 break-words text-slate-800">
                                    {{ $formatNilai($value) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </section>

    {{-- ===================================================== --}}
    {{-- TANDA TANGAN PENYERAH DAN PENERIMA --}}
    {{-- ADMIN HANYA MELIHAT TANDA TANGAN --}}
    {{-- ===================================================== --}}

    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">

        {{-- Tanda tangan penyerah --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-[#1d2d50]">
                Tanda Tangan Penyerah
            </h2>

            @if ($serahTerima->tanda_tangan_penyerah)
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($serahTerima->tanda_tangan_penyerah) }}"
                        alt="Tanda tangan petugas PJL penyerah"
                        class="max-h-40 max-w-full object-contain"
                    >
                </div>

                <p class="mt-3 text-sm text-green-700">
                    Tanda tangan penyerah tersedia.
                </p>
            @else
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-center text-sm text-slate-500">
                    Tanda tangan penyerah belum tersedia.
                </div>
            @endif
        </section>

        {{-- Tanda tangan penerima --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-[#1d2d50]">
                Tanda Tangan Penerima
            </h2>

            @if ($serahTerima->tanda_tangan_penerima)
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($serahTerima->tanda_tangan_penerima) }}"
                        alt="Tanda tangan petugas PJL penerima"
                        class="max-h-40 max-w-full object-contain"
                    >
                </div>

                <p class="mt-3 text-sm text-green-700">
                    Penerimaan telah dikonfirmasi oleh petugas shift berikutnya.
                </p>
            @else
                <div class="flex min-h-48 flex-col items-center justify-center rounded-xl border border-dashed border-amber-300 bg-amber-50 p-6 text-center">
                    <svg
                        class="mb-3 h-10 w-10 text-amber-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                            stroke-width="1.5"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-width="1.5"
                            d="M12 7v5l3 2"
                        />
                    </svg>

                    <p class="font-semibold text-amber-900">
                        Menunggu konfirmasi penerima
                    </p>

                    <p class="mt-1 text-sm text-amber-800">
                        Petugas PJL shift berikutnya harus mengonfirmasi
                        penerimaan dan membubuhkan tanda tangan melalui
                        halaman petugas.
                    </p>
                </div>
            @endif
        </section>

    </div>

    {{-- ===================================================== --}}
    {{-- REVIEW ADMIN --}}
    {{-- ===================================================== --}}

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="mb-5">
            <h2 class="text-lg font-bold text-[#1d2d50]">
                Review Admin
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Admin melakukan pemeriksaan administratif terhadap
                data serah terima setelah penerima mengonfirmasi dinas.
            </p>
        </div>

        {{-- Status review --}}
        <div class="mb-5">
            <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold {{ $statusReviewClass }}">
                {{ $statusReviewLabel }}
            </span>
        </div>

        {{-- Informasi peninjauan --}}
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <p class="text-sm text-slate-500">
                    Ditinjau oleh
                </p>

                <p class="mt-1 font-medium text-slate-800">
                    {{ $serahTerima->ditinjauOleh?->name ?? 'Belum ditinjau' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">
                    Waktu review
                </p>

                <p class="mt-1 font-medium text-slate-800">
                    {{ $serahTerima->ditinjau_pada?->format('d/m/Y H:i') ?? '—' }}
                </p>
            </div>
        </div>

        {{-- Alasan revisi sebelumnya --}}
        @if ($serahTerima->catatan_revisi)
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z"
                        />
                    </svg>

                    <div>
                        <h3 class="font-semibold text-red-800">
                            Alasan Revisi
                        </h3>

                        <p class="mt-2 whitespace-pre-line break-words text-sm text-red-700">{{ $serahTerima->catatan_revisi }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Menunggu penerimaan --}}
        @if (!$penerimaanTerkonfirmasi)

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <svg
                        class="mt-0.5 h-6 w-6 shrink-0 text-amber-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                            stroke-width="1.5"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M12 7v5l3 2"
                        />
                    </svg>

                    <div>
                        <p class="font-semibold text-amber-900">
                            Review belum dapat dilakukan
                        </p>

                        <p class="mt-1 text-sm text-amber-800">
                            Menunggu petugas PJL pada shift berikutnya
                            mengonfirmasi penerimaan dan membubuhkan
                            tanda tangan terlebih dahulu.
                        </p>
                    </div>
                </div>
            </div>

        {{-- Sudah disetujui --}}
        @elseif ($reviewSudahDisetujui)

            <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                <div class="flex items-start gap-3">
                    <svg
                        class="mt-0.5 h-6 w-6 shrink-0 text-green-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m5 12 4 4L19 6"
                        />
                    </svg>

                    <div>
                        <p class="font-semibold text-green-900">
                            Serah terima telah disetujui
                        </p>

                        <p class="mt-1 text-sm text-green-800">
                            Keputusan review administrasi telah tersimpan.
                        </p>
                    </div>
                </div>
            </div>

        {{-- Tombol review --}}
        @else

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="mb-4 text-sm text-slate-600">
                    Pastikan seluruh informasi telah diperiksa sebelum
                    memilih keputusan.
                </p>

                <div class="flex flex-col gap-3 sm:flex-row">

                    {{-- Tombol setujui --}}
                    <form
                        action="{{ route('admin.serah-terima-dinas.update', $serahTerima->id) }}"
                        method="POST"
                        class="flex-1"
                        onsubmit="return confirm('Apakah kamu yakin ingin menyetujui serah terima ini?')"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="status_review"
                            value="disetujui"
                        >

                        <input
                            type="hidden"
                            name="catatan_revisi"
                            value=""
                        >

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-green-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-2"
                        >
                            <svg
                                class="h-5 w-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m5 12 4 4L19 6"
                                />
                            </svg>

                            Setujui Serah Terima
                        </button>
                    </form>

                    {{-- Tombol tolak / revisi --}}
                    <button
                        type="button"
                        id="bukaModalRevisi"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2"
                    >
                        <svg
                            class="h-5 w-5"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>

                        Tolak / Minta Revisi
                    </button>

                </div>
            </div>

        @endif

    </section>

    {{-- ===================================================== --}}
    {{-- MODAL ALASAN REVISI --}}
    {{-- ===================================================== --}}

    @if ($penerimaanTerkonfirmasi && !$reviewSudahDisetujui)
        <div
            id="modalRevisi"
            class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/60 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="judulModalRevisi"
            aria-describedby="deskripsiModalRevisi"
        >
            <div class="my-auto w-full max-w-lg rounded-2xl bg-white shadow-2xl">

                {{-- Header modal --}}
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-6">
                    <div>
                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-red-100">
                            <svg
                                class="h-6 w-6 text-red-600"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18 18 6M6 6l12 12"
                                />
                            </svg>
                        </div>

                        <h2
                            id="judulModalRevisi"
                            class="text-xl font-bold text-slate-900"
                        >
                            Tolak / Minta Revisi
                        </h2>

                        <p
                            id="deskripsiModalRevisi"
                            class="mt-2 text-sm leading-6 text-slate-500"
                        >
                            Tuliskan alasan yang jelas agar petugas PJL
                            mengetahui bagian yang perlu diperbaiki.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="tutupModalRevisi"
                        aria-label="Tutup modal"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    >
                        <svg
                            class="h-5 w-5"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                {{-- Form alasan --}}
                <form
                    action="{{ route('admin.serah-terima-dinas.update', $serahTerima->id) }}"
                    method="POST"
                    id="formRevisi"
                >
                    @csrf
                    @method('PATCH')

                    <input
                        type="hidden"
                        name="status_review"
                        value="perlu_revisi"
                    >

                    <div class="p-6">
                        <label
                            for="alasanRevisi"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Alasan revisi
                            <span class="text-red-600">*</span>
                        </label>

                        <textarea
                            id="alasanRevisi"
                            name="catatan_revisi"
                            rows="5"
                            minlength="5"
                            maxlength="5000"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-red-500 focus:ring-4 focus:ring-red-100"
                            placeholder="Contoh: Kondisi alat pengamanan belum dijelaskan secara lengkap. Mohon lengkapi catatan sebelum mengajukan kembali."
                        >{{ old('catatan_revisi', $serahTerima->catatan_revisi) }}</textarea>

                        <div class="mt-2 flex items-start justify-between gap-3">
                            <p class="text-xs text-slate-500">
                                Minimal 5 karakter dan maksimal 5.000 karakter.
                            </p>

                            <span
                                id="jumlahKarakterRevisi"
                                class="shrink-0 text-xs text-slate-400"
                            >0/5000</span>
                        </div>

                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <p class="text-xs leading-5 text-amber-800">
                                Keputusan ini akan tercatat sebagai permintaan
                                revisi. Pastikan alasan yang diberikan spesifik
                                dan dapat ditindaklanjuti oleh petugas.
                            </p>
                        </div>
                    </div>

                    {{-- Footer modal --}}
                    <div class="flex flex-col-reverse gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50 p-6 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            id="batalModalRevisi"
                            class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            id="kirimRevisi"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300"
                        >
                            <svg
                                class="h-5 w-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13 9 17 19 7"
                                />
                            </svg>

                            Kirim Keputusan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif

    {{-- Tombol kembali --}}
    <div class="flex justify-start pb-4">
        <a
            href="{{ route('admin.serah-terima-dinas.index') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-medium text-slate-700 transition hover:bg-slate-50"
        >
            <span aria-hidden="true">←</span>
            Kembali ke Daftar Pengajuan
        </a>
    </div>

</div>
@endsection

{{-- ========================================================= --}}
{{-- JAVASCRIPT MODAL REVIEW --}}
{{-- ========================================================= --}}

@if (
    $penerimaanTerkonfirmasi
    && !$reviewSudahDisetujui
)
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modalRevisi');
            const buka = document.getElementById('bukaModalRevisi');
            const tutup = document.getElementById('tutupModalRevisi');
            const batal = document.getElementById('batalModalRevisi');
            const alasan = document.getElementById('alasanRevisi');
            const form = document.getElementById('formRevisi');
            const jumlahKarakter = document.getElementById('jumlahKarakterRevisi');

            if (
                !modal
                || !buka
                || !tutup
                || !batal
                || !alasan
                || !form
            ) {
                return;
            }

            let elemenSebelumnya = null;

            function perbaruiJumlahKarakter() {
                if (jumlahKarakter) {
                    jumlahKarakter.textContent =
                        `${alasan.value.length}/5000`;
                }
            }

            function bukaModal() {
                elemenSebelumnya = document.activeElement;

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                document.body.classList.add('overflow-hidden');

                perbaruiJumlahKarakter();

                window.setTimeout(function () {
                    alasan.focus();
                }, 50);
            }

            function tutupModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');

                document.body.classList.remove('overflow-hidden');

                if (elemenSebelumnya) {
                    elemenSebelumnya.focus();
                }
            }

            buka.addEventListener('click', bukaModal);
            tutup.addEventListener('click', tutupModal);
            batal.addEventListener('click', tutupModal);

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    tutupModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (
                    event.key === 'Escape'
                    && !modal.classList.contains('hidden')
                ) {
                    tutupModal();
                }
            });

            alasan.addEventListener('input', function () {
                alasan.setCustomValidity('');
                perbaruiJumlahKarakter();
            });

            form.addEventListener('submit', function (event) {
                const nilai = alasan.value.trim();

                if (nilai.length < 5) {
                    event.preventDefault();

                    alasan.setCustomValidity(
                        'Alasan revisi wajib diisi minimal 5 karakter.'
                    );

                    alasan.reportValidity();
                    alasan.focus();

                    return;
                }

                if (nilai.length > 5000) {
                    event.preventDefault();

                    alasan.setCustomValidity(
                        'Alasan revisi maksimal 5.000 karakter.'
                    );

                    alasan.reportValidity();
                    alasan.focus();

                    return;
                }

                alasan.setCustomValidity('');

                /*
                 * Hilangkan spasi di awal dan akhir sebelum dikirim.
                 */
                alasan.value = nilai;
            });

            perbaruiJumlahKarakter();

            /*
             * Jika validasi Laravel gagal, buka kembali modal
             * agar alasan tidak perlu dimasukkan ulang.
             */
            @if ($errors->has('catatan_revisi'))
                bukaModal();
            @endif
        });
        </script>
    @endpush
@endif

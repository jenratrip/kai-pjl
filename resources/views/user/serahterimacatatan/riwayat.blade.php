@extends('layouts.user')

@section('title', 'Riwayat Serah Terima Dinas - Sistem PJL')

@section('content')
    @php
        // [teks, kelas badge, kelas titik, kelas garis kiri kartu HP]
        $label = [
            'draft' => [
                'Draft',
                'bg-slate-100 text-slate-700',
                'bg-slate-400',
                'border-l-slate-300',
            ],
            'menunggu_penerima' => [
                'Menunggu Konfirmasi Penerima',
                'bg-amber-100 text-amber-800',
                'bg-amber-500',
                'border-l-amber-400',
            ],
            'selesai' => [
                'Sudah Diterima · Menunggu review admin',
                'bg-blue-100 text-blue-800',
                'bg-blue-500',
                'border-l-blue-400',
            ],
        ];

        $labelDefault = [
            null,
            'bg-slate-100 text-slate-700',
            'bg-slate-400',
            'border-l-slate-300',
        ];

        $buatBaris = function ($item, $sebagaiPenerima = false) use ($label, $labelDefault) {
            $status = (string) $item->status;
            $statusReview = (string) ($item->status_review ?? 'menunggu_review');

            /*
             * Penentuan label status
             */
            if ($statusReview === 'disetujui') {
                $teks = 'Disetujui admin';
                $kelas = 'bg-green-100 text-green-800';
                $titik = 'bg-green-500';
                $garis = 'border-l-green-500';
            } elseif ($statusReview === 'perlu_revisi') {
                $teks = 'Perlu revisi';
                $kelas = 'bg-red-100 text-red-800';
                $titik = 'bg-red-500';
                $garis = 'border-l-red-400';
            } elseif ($status === 'selesai') {
                $teks = 'Sudah Diterima · Menunggu review admin';
                $kelas = 'bg-blue-100 text-blue-800';
                $titik = 'bg-blue-500';
                $garis = 'border-l-blue-400';
            } else {
                [$teks, $kelas, $titik, $garis] =
                    $label[$status] ?? $labelDefault;

                $teks ??= $status !== ''
                    ? ucfirst(str_replace('_', ' ', $status))
                    : 'Menunggu proses';
            }

            /*
             * Tanggal dinas
             */
            $tgl = $item->dinas?->tanggal_dinas
                ? \Carbon\Carbon::parse($item->dinas->tanggal_dinas)->locale('id')
                : null;

            $waktuSimpan = $item->waktu_serah_terima ?? $item->updated_at;
            $tanggal = $tgl?->translatedFormat('d M Y') ?? '-';

            /*
             * KUNCI DATA:
             * 1. Sudah disetujui admin.
             * 2. Penerima sudah konfirmasi dan menunggu review admin.
             *
             * Jika perlu_revisi, penyerah masih boleh mengedit.
             */
            $terkunci = (
                $statusReview === 'disetujui'
                || (
                    $status === 'selesai'
                    && $statusReview !== 'perlu_revisi'
                )
            );

            /*
             * Catatan revisi dari admin.
             * Field yang digunakan: catatan_review.
             */
            $catatanRevisi = $statusReview === 'perlu_revisi'
                ? (
                    $item->catatan_review
                    ?? $item->catatan_revisi
                    ?? $item->komentar_admin
                    ?? null
                )
                : null;

            return [
                'tanggal' => $tanggal,
                'hari' => $tgl?->translatedFormat('l'),
                'shift' => $item->dinas?->shift?->nama_shift ?? '-',
                'jpl' => $item->dinas?->jpl?->kode_jpl ?? '-',
                'namaJpl' => $item->dinas?->jpl?->nama_jpl,

                'penerima' => $sebagaiPenerima
                    ? ($item->dinas?->user?->name ?? '-')
                    : (
                        data_get($item->ringkasan, 'nama_penerima')
                        ?: ($item->penerima?->name ?? '-')
                    ),

                'namaPenyerah' => $item->dinas?->user?->name ?? '-',

                'disimpan' => $waktuSimpan
                    ? \Carbon\Carbon::parse($waktuSimpan)
                        ->timezone('Asia/Jakarta')
                        ->locale('id')
                        ->translatedFormat('d M Y, H:i') . ' WIB'
                    : '-',

                'teks' => $teks,
                'kelas' => $kelas,
                'titik' => $titik,
                'garis' => $garis,

                'terkunci' => $terkunci,
                'sebagaiPenerima' => $sebagaiPenerima,

                'catatanRevisi' => $catatanRevisi,

                'menungguKonfirmasi' => $sebagaiPenerima
                    && $status === 'menunggu_penerima'
                    && empty($item->tanda_tangan_penerima),

                'urlEdit' => route('user.serah-terima-dinas.edit', $item),
                'urlLihat' => route('user.serah-terima-dinas.show', $item),

                'aria' => 'serah terima dinas tanggal ' . $tanggal,
            ];
        };

        $baris = $riwayat->getCollection()
            ->map(fn ($item) => $buatBaris($item, false));

        $barisMasuk = isset($pengajuanMasuk)
            ? $pengajuanMasuk->getCollection()
                ->map(fn ($item) => $buatBaris($item, true))
            : collect();
    @endphp

    <div class="mx-auto min-h-[70vh] w-full max-w-7xl px-4 py-8 sm:px-8 lg:py-10">

        {{-- Judul halaman --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#1d2d50]">
                    Riwayat Serah Terima Dinas
                </h1>

                <p class="mt-1 max-w-xl text-sm leading-relaxed text-slate-500">
                    Lihat serah terima yang kamu kirim dan pengajuan yang ditujukan
                    kepadamu. Penerima dapat membuka detail dan memberikan tanda tangan.
                </p>
            </div>

            <a
                href="{{ route('user.serah-terima-dinas') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2"
            >
                Kembali ke serah terima
            </a>
        </div>

        {{-- Notifikasi --}}
        @if (session('success'))
            <div
                class="mb-4 rounded-lg border border-green-200 bg-green-50 px-[18px] py-3.5 text-[15px] leading-relaxed text-green-800 max-sm:text-sm"
                role="status"
            >
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div
                class="mb-4 rounded-lg border border-red-200 bg-red-50 px-[18px] py-3.5 text-[15px] leading-relaxed text-red-800 max-sm:text-sm"
                role="alert"
            >
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                class="mb-4 rounded-lg border border-red-200 bg-red-50 px-[18px] py-3.5 text-[15px] leading-relaxed text-red-800 max-sm:text-sm"
                role="alert"
            >
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- ====================================================== --}}
        {{-- PENGAJUAN MASUK --}}
        {{-- ====================================================== --}}
        <section class="mb-10">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-[#1d2d50]">
                        Pengajuan Masuk
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Serah terima dinas yang dikirim petugas lain kepadamu.
                    </p>
                </div>

                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    {{ isset($pengajuanMasuk) ? $pengajuanMasuk->total() : 0 }} pengajuan
                </span>
            </div>

            @if ($barisMasuk->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center">
                    <p class="font-semibold text-[#1d2d50]">
                        Belum ada pengajuan masuk
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Pengajuan dari petugas lain akan muncul di bagian ini.
                    </p>
                </div>
            @else
                {{-- Tabel desktop pengajuan masuk --}}
                <div class="hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm md:block">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <caption class="sr-only">
                            Daftar pengajuan serah terima masuk
                        </caption>

                        <thead class="bg-[#1d2d50] text-white">
                            <tr>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Tanggal dinas</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Shift</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">JPL</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Penyerah</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Terakhir disimpan</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Status / Revisi admin</th>
                                <th scope="col" class="px-4 py-4 text-right text-[15px] font-bold">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($barisMasuk as $b)
                                <tr class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-4 py-4 align-middle">
                                        <p class="font-semibold text-slate-800">
                                            {{ $b['tanggal'] }}
                                        </p>

                                        @if ($b['hari'])
                                            <p class="text-[13px] text-slate-500">
                                                {{ $b['hari'] }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-slate-700">
                                        {{ $b['shift'] }}
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-slate-800">
                                            {{ $b['jpl'] }}
                                        </p>

                                        @if ($b['namaJpl'])
                                            <p class="max-w-[220px] truncate text-[13px] text-slate-500">
                                                {{ $b['namaJpl'] }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-slate-700">
                                        {{ $b['namaPenyerah'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-[13px] text-slate-500">
                                        {{ $b['disimpan'] }}
                                    </td>

                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $b['kelas'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $b['titik'] }}"></span>
                                            {{ $b['teks'] }}
                                        </span>

                                        @if ($b['catatanRevisi'])
                                            <div class="mt-2 min-w-[200px] max-w-[300px] rounded-lg border border-red-200 bg-red-50 p-2.5">
                                                <p class="text-xs font-bold text-red-800">
                                                    Catatan revisi admin
                                                </p>
                                                <p class="mt-1 whitespace-pre-line break-words text-xs leading-relaxed text-red-700">
                                                    {{ $b['catatanRevisi'] }}
                                                </p>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        <a
                                            href="{{ $b['urlLihat'] }}"
                                            class="inline-flex items-center justify-center rounded-lg bg-[#1d2d50] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-slate-700"
                                        >
                                            {{ $b['menungguKonfirmasi'] ? 'Periksa & tanda tangan' : 'Lihat' }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Kartu HP pengajuan masuk --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($barisMasuk as $b)
                        <article class="rounded-xl border border-l-4 border-slate-200 bg-white p-4 shadow-sm {{ $b['garis'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-slate-800">
                                        {{ $b['tanggal'] }}
                                    </p>

                                    @if ($b['hari'])
                                        <p class="text-xs text-slate-500">
                                            {{ $b['hari'] }}
                                        </p>
                                    @endif
                                </div>

                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $b['kelas'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $b['titik'] }}"></span>
                                    {{ $b['teks'] }}
                                </span>
                            </div>

                            <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2.5 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-500">Shift</dt>
                                    <dd class="font-semibold text-slate-800">{{ $b['shift'] }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs text-slate-500">JPL</dt>
                                    <dd class="font-semibold text-slate-800">{{ $b['jpl'] }}</dd>
                                </div>

                                <div class="col-span-2">
                                    <dt class="text-xs text-slate-500">Penyerah</dt>
                                    <dd class="font-semibold text-slate-800">{{ $b['namaPenyerah'] }}</dd>
                                </div>

                                <div class="col-span-2">
                                    <dt class="text-xs text-slate-500">Terakhir disimpan</dt>
                                    <dd class="text-slate-700">{{ $b['disimpan'] }}</dd>
                                </div>
                            </dl>

                            @if ($b['catatanRevisi'])
                                <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3">
                                    <p class="text-xs font-bold text-red-800">
                                        Catatan revisi admin
                                    </p>
                                    <p class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-red-700">
                                        {{ $b['catatanRevisi'] }}
                                    </p>
                                </div>
                            @endif

                            <div class="mt-3 border-t border-slate-100 pt-3">
                                <a
                                    href="{{ $b['urlLihat'] }}"
                                    class="block w-full rounded-lg bg-[#1d2d50] px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-slate-700"
                                >
                                    {{ $b['menungguKonfirmasi'] ? 'Periksa & tanda tangan' : 'Lihat serah terima' }}
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-slate-500">
                        Menampilkan {{ $pengajuanMasuk->firstItem() }}–{{ $pengajuanMasuk->lastItem() }}
                        dari {{ $pengajuanMasuk->total() }} pengajuan masuk
                    </p>

                    <div>
                        {{ $pengajuanMasuk->links() }}
                    </div>
                </div>
            @endif
        </section>

        {{-- ====================================================== --}}
        {{-- RIWAYAT KELUAR --}}
        {{-- ====================================================== --}}
        <section>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-[#1d2d50]">
                        Riwayat Serah Terima Keluar
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Serah terima yang kamu buat dan kirim kepada petugas penerima.
                    </p>
                </div>

                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                    {{ $riwayat->total() }} data
                </span>
            </div>

            @if ($baris->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <p class="font-semibold text-[#1d2d50]">
                        Belum ada riwayat serah terima
                    </p>

                    <p class="mx-auto mt-1 max-w-sm text-sm leading-relaxed text-slate-500">
                        Riwayat muncul setelah Anda menekan tombol
                        <strong>Simpan serah terima</strong> di halaman serah terima.
                        Menyimpan catatan dinas atau inventaris saja tidak membuat riwayat.
                    </p>

                    <a
                        href="{{ route('user.serah-terima-dinas') }}"
                        class="mt-4 inline-flex items-center justify-center rounded-lg bg-[#1d2d50] px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700"
                    >
                        Buat serah terima
                    </a>
                </div>
            @else
                {{-- Tabel desktop --}}
                <div class="hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm md:block">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <caption class="sr-only">
                            Daftar riwayat serah terima dinas keluar
                        </caption>

                        <thead class="bg-[#1d2d50] text-white">
                            <tr>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Tanggal dinas</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Shift</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">JPL</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Penerima</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Terakhir disimpan</th>
                                <th scope="col" class="px-4 py-4 text-[15px] font-bold">Status / Revisi admin</th>
                                <th scope="col" class="px-4 py-4 text-right text-[15px] font-bold">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($baris as $b)
                                <tr class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-4 py-4 align-middle">
                                        <p class="font-semibold text-slate-800">
                                            {{ $b['tanggal'] }}
                                        </p>

                                        @if ($b['hari'])
                                            <p class="text-[13px] text-slate-500">
                                                {{ $b['hari'] }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-slate-700">
                                        {{ $b['shift'] }}
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-slate-800">
                                            {{ $b['jpl'] }}
                                        </p>

                                        @if ($b['namaJpl'])
                                            <p class="max-w-[220px] truncate text-[13px] text-slate-500">
                                                {{ $b['namaJpl'] }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-slate-700">
                                        {{ $b['penerima'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-[13px] text-slate-500">
                                        {{ $b['disimpan'] }}
                                    </td>

                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $b['kelas'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $b['titik'] }}"></span>
                                            {{ $b['teks'] }}
                                        </span>

                                        @if ($b['catatanRevisi'])
                                            <div class="mt-2 min-w-[200px] max-w-[300px] rounded-lg border border-red-200 bg-red-50 p-2.5">
                                                <p class="text-xs font-bold text-red-800">
                                                    Catatan revisi admin
                                                </p>
                                                <p class="mt-1 whitespace-pre-line break-words text-xs leading-relaxed text-red-700">
                                                    {{ $b['catatanRevisi'] }}
                                                </p>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        @if ($b['terkunci'])
                                            <a
                                                href="{{ $b['urlLihat'] }}"
                                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-[#1d2d50] transition hover:bg-slate-50"
                                            >
                                                Lihat
                                            </a>
                                        @else
                                            <a
                                                href="{{ $b['urlEdit'] }}"
                                                class="inline-flex items-center justify-center rounded-lg bg-[#1d2d50] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-slate-700"
                                            >
                                                Edit
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Kartu HP --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($baris as $b)
                        <article class="rounded-xl border border-l-4 border-slate-200 bg-white p-4 shadow-sm {{ $b['garis'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-800">
                                        {{ $b['tanggal'] }}
                                    </p>

                                    @if ($b['hari'])
                                        <p class="text-xs text-slate-500">
                                            {{ $b['hari'] }}
                                        </p>
                                    @endif
                                </div>

                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $b['kelas'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $b['titik'] }}"></span>
                                    {{ $b['teks'] }}
                                </span>
                            </div>

                            <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2.5 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-500">Shift</dt>
                                    <dd class="font-semibold text-slate-800">
                                        {{ $b['shift'] }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs text-slate-500">JPL</dt>
                                    <dd class="font-semibold text-slate-800">
                                        {{ $b['jpl'] }}
                                    </dd>
                                </div>

                                <div class="col-span-2">
                                    <dt class="text-xs text-slate-500">Penerima</dt>
                                    <dd class="font-semibold text-slate-800">
                                        {{ $b['penerima'] }}
                                    </dd>
                                </div>

                                <div class="col-span-2">
                                    <dt class="text-xs text-slate-500">Terakhir disimpan</dt>
                                    <dd class="text-slate-700">
                                        {{ $b['disimpan'] }}
                                    </dd>
                                </div>
                            </dl>

                            @if ($b['catatanRevisi'])
                                <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3">
                                    <p class="text-xs font-bold text-red-800">
                                        Catatan revisi admin
                                    </p>
                                    <p class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-red-700">
                                        {{ $b['catatanRevisi'] }}
                                    </p>
                                </div>
                            @endif

                            <div class="mt-3 border-t border-slate-100 pt-3">
                                @if ($b['terkunci'])
                                    <a
                                        href="{{ $b['urlLihat'] }}"
                                        class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50"
                                    >
                                        Lihat serah terima
                                    </a>
                                @else
                                    <a
                                        href="{{ $b['urlEdit'] }}"
                                        class="block w-full rounded-lg bg-[#1d2d50] px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-slate-700"
                                    >
                                        Edit serah terima
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-slate-500">
                        Menampilkan {{ $riwayat->firstItem() }}–{{ $riwayat->lastItem() }}
                        dari {{ $riwayat->total() }} data
                    </p>

                    <div>
                        {{ $riwayat->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection

@extends('layouts.user')

@php
    $modeLihat = !empty($modeLihat);
    $modeEdit = !empty($modeEdit);

    // Mendukung nama variabel dari kedua versi.
    $modeKonfirmasiPenerima = !empty($modeKonfirmasiPenerima)
        || !empty($modePenerima);

    $modePenerima = $modeKonfirmasiPenerima;

    // Mode lihat dan konfirmasi tidak boleh berjalan bersamaan.
    if ($modeLihat) {
        $modeKonfirmasiPenerima = false;
        $modePenerima = false;
    }

    $statusSerahTerima = $serahTerima?->status ?? null;
    $statusReview = $serahTerima?->status_review ?? null;

    $terkunci = $statusReview === 'disetujui';
@endphp

@section('title', $modeKonfirmasiPenerima
    ? 'Konfirmasi Serah Terima Dinas - Sistem PJL'
    : ($modeLihat
        ? 'Lihat Serah Terima Dinas - Sistem PJL'
        : ($modeEdit
            ? 'Edit Serah Terima Dinas - Sistem PJL'
            : 'Serah Terima Dinas PJL - Sistem PJL')))

@section('content')
    @php
        $sections = [
            'header',
            'ka-batal',
            'ka-berjalan',
            'ka-belum-lewat',
            'alat-pengaman',
            'inventaris',
            'catatan',
            'petugas',
            'tanda-tangan',
        ];
    @endphp

    <div class="mx-auto w-full max-w-7xl space-y-4 px-4 py-6 sm:px-6">

        {{-- =====================================================
             BANNER STATUS PENERIMA
        ====================================================== --}}
        @if ($modePenerima && $statusSerahTerima === 'menunggu_penerima')
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900"
                role="status"
            >
                <span>
                    Anda ditunjuk sebagai penerima serah terima dinas ini.
                    Silakan periksa data yang disampaikan, kemudian bubuhkan tanda tangan
                    untuk mengonfirmasi penerimaan.
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-blue-300 bg-white px-3 py-2 text-xs font-semibold text-blue-900 transition hover:bg-blue-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                >
                    Kembali ke riwayat
                </a>
            </div>

        {{-- Penerima menunggu penyerah melakukan revisi --}}
        @elseif ($modePenerima && $statusReview === 'perlu_revisi')
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
                role="status"
            >
                <span>
                    Admin meminta revisi terhadap serah terima ini.
                    Penyerah perlu memperbaiki dan mengirim ulang data terlebih dahulu.
                    Setelah dikirim ulang, Anda dapat melakukan konfirmasi kembali.
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-900 transition hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                >
                    Kembali ke riwayat
                </a>
            </div>

        {{-- Penerima sudah melakukan konfirmasi --}}
        @elseif ($modePenerima && $statusSerahTerima === 'selesai')
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                role="status"
            >
                <span>
                    Anda sudah mengonfirmasi serah terima ini.
                    @if ($statusReview === 'menunggu_review')
                        Data sedang menunggu tinjauan admin.
                    @elseif ($statusReview === 'disetujui')
                        Serah terima telah disetujui admin.
                    @endif
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2"
                >
                    Kembali ke riwayat
                </a>
            </div>

        {{-- =====================================================
             BANNER MODE KONFIRMASI PENERIMA
        ====================================================== --}}
        @elseif ($modeKonfirmasiPenerima)
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                role="status"
            >
                <div>
                    <p class="font-bold">
                        Konfirmasi serah terima sebagai petugas penerima
                    </p>

                    <p class="mt-1">
                        Periksa isi dokumen terlebih dahulu. Kamu hanya dapat
                        membubuhkan tanda tangan penerima, bukan mengubah isi
                        serah terima yang dibuat petugas penyerah.
                    </p>
                </div>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-100"
                >
                    Kembali ke riwayat
                </a>
            </div>

        {{-- =====================================================
             BANNER MODE LIHAT
        ====================================================== --}}
        @elseif ($modeLihat)
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                role="status"
            >
                <span class="font-semibold">
                    Serah terima sudah ditandatangani. Tampilan hanya bisa dilihat.
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-green-300 bg-white px-3 py-2 text-xs font-semibold text-green-800 transition hover:bg-green-100"
                >
                    Kembali ke riwayat
                </a>
            </div>

        {{-- =====================================================
             BANNER MODE EDIT
        ====================================================== --}}
        @elseif ($modeEdit)
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                role="status"
            >
                <span>
                    Anda sedang mengedit serah terima dari riwayat.
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-800 transition hover:bg-amber-100"
                >
                    Kembali ke riwayat
                </a>
            </div>

        {{-- =====================================================
             BANNER MENUNGGU TANDA TANGAN PENERIMA
        ====================================================== --}}
        @elseif ($dinas && $statusSerahTerima === 'menunggu_penerima')
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900"
                role="status"
            >
                <span>
                    Serah terima dinas ini sudah tersimpan dan menunggu tanda tangan penerima.
                    Anda masih bisa memperbarui isinya selama penerima belum melakukan konfirmasi.
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-blue-300 bg-white px-3 py-2 text-xs font-semibold text-blue-900 transition hover:bg-blue-100"
                >
                    Lihat riwayat
                </a>
            </div>

        {{-- Penerima sudah tanda tangan, menunggu tinjauan admin --}}
        @elseif ($dinas && $statusSerahTerima === 'selesai' && $statusReview === 'menunggu_review')
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                role="status"
            >
                <span>
                    Penerima sudah mengonfirmasi serah terima. Data sedang menunggu tinjauan admin
                    dan belum dapat diubah.
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2"
                >
                    Lihat riwayat
                </a>
            </div>

        {{-- Admin meminta revisi --}}
        @elseif ($dinas && $statusReview === 'perlu_revisi')
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
                role="status"
            >
                <span>
                    Serah terima perlu direvisi berdasarkan hasil tinjauan admin.
                    Silakan periksa kembali data dan lakukan perbaikan.
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-900 transition hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                >
                    Lihat riwayat
                </a>
            </div>
        @endif

        {{-- =====================================================
             NOTIFIKASI SUKSES
        ====================================================== --}}
        @if (session('success'))
            <div
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-800"
                role="status"
            >
                {{ session('success') }}

                @if (str_contains(session('success'), 'serah terima'))
                    <a
                        href="{{ route('user.serah-terima-dinas.riwayat') }}"
                        class="ml-1 font-semibold underline underline-offset-2 hover:text-green-950"
                    >
                        Lihat di riwayat
                    </a>
                @endif
            </div>
        @endif

        {{-- =====================================================
             NOTIFIKASI ERROR
        ====================================================== --}}
        @if ($errors->any())
            <div
                id="stErrorBox"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3.5 text-sm text-red-800"
                role="alert"
            >
                <p class="mb-1 font-bold">
                    Periksa kembali formulir:
                </p>

                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- =====================================================
             KONDISI TIDAK ADA DINAS
        ====================================================== --}}
        @if (! $dinas)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 sm:p-7">
                <h1 class="text-xl font-bold text-[#1d2d50]">
                    Serah Terima Dinas PJL
                </h1>

                <p class="mt-2 text-sm leading-relaxed text-amber-900">
                    Belum ada data dinas untuk ditampilkan.
                </p>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="mt-4 inline-flex items-center justify-center rounded-lg border border-[#1d2d50] bg-white px-4 py-2.5 text-sm font-semibold text-[#1d2d50]"
                >
                    Buka riwayat serah terima
                </a>
            </div>
        @else

            {{-- =================================================
                 MODE LIHAT
                 Seluruh input dinonaktifkan.
            ================================================== --}}
            @if ($modeLihat)
                <fieldset
                    disabled
                    data-mode-lihat
                    class="m-0 min-w-0 space-y-4 border-0 p-0"
                >
                    @foreach ($sections as $section)
                        @include('user.serahterimacatatan.partials.' . $section)
                    @endforeach
                </fieldset>

            {{-- =================================================
                 MODE KONFIRMASI PENERIMA
                 Konten penyerah hanya dibaca.
                 Area tanda tangan penerima tetap interaktif.
            ================================================== --}}
            @elseif ($modeKonfirmasiPenerima)
                <div
                    data-mode-konfirmasi-penerima
                    class="min-w-0 space-y-4"
                >
                    @foreach ($sections as $section)
                        @include('user.serahterimacatatan.partials.' . $section)
                    @endforeach

                    @include('user.serahterimacatatan.partials.actions-konfirmasi')
                </div>

            {{-- =================================================
                 MODE NORMAL / EDIT PENYERAH
            ================================================== --}}
            @else
                <fieldset
                    class="m-0 min-w-0 space-y-4 border-0 p-0"
                >
                    @foreach ($sections as $section)
                        @include('user.serahterimacatatan.partials.' . $section)
                    @endforeach
                </fieldset>

                @include('user.serahterimacatatan.partials.actions')
            @endif

        @endif
    </div>

    {{-- =====================================================
         PERILAKU MODE LIHAT
    ====================================================== --}}
    @if ($modeLihat)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll(
                    '[data-mode-lihat] input:disabled, ' +
                    '[data-mode-lihat] textarea:disabled, ' +
                    '[data-mode-lihat] select:disabled'
                ).forEach(function (field) {
                    field.style.opacity = '1';
                    field.style.cursor = 'default';
                    field.style.webkitTextFillColor = 'currentColor';
                });

                document.querySelectorAll(
                    '[data-mode-lihat] canvas'
                ).forEach(function (canvas) {
                    canvas.style.pointerEvents = 'none';
                });

                document.querySelectorAll(
                    '[data-mode-lihat] [data-sembunyi-lihat]'
                ).forEach(function (element) {
                    element.style.display = 'none';
                });
            });
        </script>
    @endif

    {{-- =====================================================
         PENGUNCIAN MODE KONFIRMASI PENERIMA
    ====================================================== --}}
    @if ($modeKonfirmasiPenerima)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.querySelector(
                    '[data-mode-konfirmasi-penerima]'
                );

                if (!container) {
                    return;
                }

                // Kunci semua kontrol selain area tanda tangan penerima.
                container.querySelectorAll(
                    'input, textarea, select, button'
                ).forEach(function (element) {
                    if (element.closest('[data-ttd-penerima]')) {
                        return;
                    }

                    element.disabled = true;
                });

                // Semua canvas selain milik penerima tidak boleh digambar ulang.
                container.querySelectorAll('canvas').forEach(function (canvas) {
                    if (canvas.closest('[data-ttd-penerima]')) {
                        return;
                    }

                    canvas.style.pointerEvents = 'none';
                });

                // Sembunyikan tombol/aksi yang hanya relevan untuk penyerah.
                container.querySelectorAll('[data-sembunyi-konfirmasi-penerima]')
                    .forEach(function (element) {
                        element.style.display = 'none';
                    });
            });
        </script>
    @endif

    {{-- =====================================================
         ARAHKAN LAYAR KE PESAN KESALAHAN
    ====================================================== --}}
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const box = document.getElementById('stErrorBox');

                if (box) {
                    box.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            });
        </script>
    @endif
@endsection
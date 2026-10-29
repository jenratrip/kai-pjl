
@extends('layouts.user')

@php
    $modeLihat = !empty($modeLihat);
    $modeEdit = !empty($modeEdit);
    $statusSerahTerima = $serahTerima?->status ?? null;
@endphp

@section('title', $modeLihat
    ? 'Lihat Serah Terima Dinas - Sistem PJL'
    : ($modeEdit
        ? 'Edit Serah Terima Dinas - Sistem PJL'
        : 'Serah Terima Dinas PJL - Sistem PJL'))

@section('content')
    @php
        // Urutan bagian formulir serah terima.
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
            'actions',
        ];

        // Di mode lihat, tombol simpan/kirim tidak ditampilkan.
        if ($modeLihat) {
            $sections = array_values(array_diff($sections, ['actions']));
        }
    @endphp

    <div class="mx-auto w-full max-w-7xl space-y-4 px-4 py-6 sm:px-6">

        {{-- Banner mode lihat --}}
        @if ($modeLihat)
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                role="status"
            >
                <span class="font-semibold">
                    Serah terima ini sudah disetujui. Tampilan hanya bisa dilihat.
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-green-300 bg-white px-3 py-2 text-xs font-semibold text-green-800 transition hover:bg-green-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2"
                >
                    Kembali ke riwayat
                </a>
            </div>

        {{-- Banner mode edit --}}
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
                    class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-800 transition hover:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2"
                >
                    Kembali ke riwayat
                </a>
            </div>

        {{-- Serah terima sudah dikirim dan menunggu admin --}}
        @elseif ($dinas && $statusSerahTerima === 'menunggu_penerima')
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900"
                role="status"
            >
                <span>
                    Serah terima dinas ini sudah tersimpan dan menunggu persetujuan admin.
                    Kamu masih bisa memperbarui isinya, lalu simpan kembali.
                </span>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-blue-300 bg-white px-3 py-2 text-xs font-semibold text-blue-900 transition hover:bg-blue-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                >
                    Lihat riwayat
                </a>
            </div>
        @endif

        {{-- Notifikasi sukses --}}
        @if (session('success'))
            <div
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-800"
                role="status"
            >
                {{ session('success') }}

                @if (str_contains(session('success'), 'serah terima'))
                    <a
                        href="{{ route('user.serah-terima-dinas.riwayat') }}"
                        class="ml-1 font-semibold underline underline-offset-2 hover:text-green-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500"
                    >
                        Lihat di riwayat
                    </a>
                @endif
            </div>
        @endif

        {{-- Notifikasi error validasi --}}
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

        {{-- Kondisi tidak ada dinas aktif --}}
        @if (! $dinas)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 sm:p-7">
                <h1 class="text-xl font-bold text-[#1d2d50]">
                    Serah Terima Dinas PJL
                </h1>

                <p class="mt-2 text-sm leading-relaxed text-amber-900">
                    Belum ada dinas aktif untuk akun ini. Data serah terima hanya dapat
                    dibuat ketika dinas berstatus berlangsung. Data sebelumnya dapat
                    dilihat di riwayat serah terima.
                </p>

                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="mt-4 inline-flex items-center justify-center rounded-lg border border-[#1d2d50] bg-white px-4 py-2.5 text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2"
                >
                    Buka riwayat serah terima
                </a>
            </div>
        @else
            {{-- Formulir dikunci penuh pada mode lihat --}}
            <fieldset
                @disabled($modeLihat)
                @class([
                    'm-0 min-w-0 space-y-4 border-0 p-0',
                    'st-lihat' => $modeLihat,
                ])
                @if ($modeLihat) data-mode-lihat @endif
            >
                @foreach ($sections as $section)
                    @include('user.serahterimacatatan.partials.' . $section)
                @endforeach
            </fieldset>
        @endif
    </div>

    {{-- Perilaku khusus mode lihat tanpa stylesheet tambahan --}}
    @if ($modeLihat)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-mode-lihat] input:disabled, [data-mode-lihat] textarea:disabled, [data-mode-lihat] select:disabled')
                    .forEach(function (field) {
                        field.style.opacity = '1';
                        field.style.cursor = 'default';
                        field.style.webkitTextFillColor = 'currentColor';
                    });

                document.querySelectorAll('[data-mode-lihat] canvas')
                    .forEach(function (canvas) {
                        canvas.style.pointerEvents = 'none';
                    });

                document.querySelectorAll('[data-mode-lihat] [data-sembunyi-lihat]')
                    .forEach(function (element) {
                        element.style.display = 'none';
                    });
            });
        </script>
    @endif

    {{-- Arahkan layar ke pesan kesalahan agar tidak terlewat --}}
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var box = document.getElementById('stErrorBox');

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

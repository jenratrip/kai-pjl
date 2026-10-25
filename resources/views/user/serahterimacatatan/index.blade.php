@extends('layouts.user-operasional')

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

@push('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/user/serah-terima.css') }}">

    @if ($modeLihat)
        <style>
            /* Tampilan tetap sama, hanya tidak bisa diubah */
            .st-lihat input:disabled,
            .st-lihat textarea:disabled,
            .st-lihat select:disabled {
                opacity: 1;
                cursor: default;
                -webkit-text-fill-color: currentColor;
            }
            .st-lihat canvas {
                pointer-events: none;
            }
            .st-lihat [data-sembunyi-lihat] {
                display: none !important;
            }
        </style>
    @endif
@endpush

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

    <div class="st-page mx-auto w-full max-w-5xl space-y-4 px-4 py-6 sm:px-6">

        {{-- Banner mode lihat --}}
        @if ($modeLihat)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm text-green-800" role="status">
                <span class="font-semibold">
                    Serah terima ini sudah disetujui. Tampilan hanya bisa dilihat.
                </span>

                <a href="{{ route('user.serah-terima-dinas.riwayat') }}"
                   class="rounded-lg border border-green-300 bg-white px-3 py-1.5 text-xs font-semibold text-green-800 hover:bg-green-100">
                    Kembali ke riwayat
                </a>
            </div>

        {{-- Banner mode edit (hanya saat mengedit dari riwayat) --}}
        @elseif ($modeEdit)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800" role="status">
                <span>Anda sedang mengedit serah terima dari riwayat.</span>

                <a href="{{ route('user.serah-terima-dinas.riwayat') }}"
                   class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100">
                    Kembali ke riwayat
                </a>
            </div>

        {{-- Serah terima dinas ini sudah dikirim dan menunggu admin --}}
        @elseif ($dinas && $statusSerahTerima === 'menunggu_penerima')
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm text-blue-900" role="status">
                <span>
                    Serah terima dinas ini sudah tersimpan dan menunggu persetujuan admin.
                    Kamu masih bisa memperbarui isinya, lalu simpan kembali.
                </span>

                <a href="{{ route('user.serah-terima-dinas.riwayat') }}"
                   class="rounded-lg border border-blue-300 bg-white px-3 py-1.5 text-xs font-semibold text-blue-900 hover:bg-blue-100">
                    Lihat riwayat
                </a>
            </div>
        @endif

        {{-- Notifikasi sukses --}}
        @if (session('success'))
            <div class="alert-message alert-success" role="status">
                {{ session('success') }}

                @if (str_contains(session('success'), 'serah terima'))
                    <a href="{{ route('user.serah-terima-dinas.riwayat') }}"
                       class="ml-1 font-semibold underline">
                        Lihat di riwayat
                    </a>
                @endif
            </div>
        @endif

        {{-- Notifikasi error validasi --}}
        @if ($errors->any())
            <div id="stErrorBox" class="alert-message alert-error" role="alert">
                <p class="mb-1 font-bold">Periksa kembali formulir:</p>
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $dinas)
            {{-- Tidak ada dinas aktif --}}
            <div class="no-active-duty">
                <h1 class="text-xl font-bold text-[#1d2d50]">Serah Terima Dinas PJL</h1>
                <p class="mt-2 text-sm leading-relaxed text-amber-900">
                    Belum ada dinas aktif untuk akun ini. Data serah terima hanya dapat
                    dibuat ketika dinas berstatus berlangsung. Data sebelumnya dapat
                    dilihat di riwayat serah terima.
                </p>

                <a href="{{ route('user.serah-terima-dinas.riwayat') }}"
                   class="mt-3 inline-block rounded-lg border border-[#1d2d50] bg-white px-4 py-2 text-sm font-semibold text-[#1d2d50] hover:bg-slate-50">
                    Buka riwayat serah terima
                </a>
            </div>
        @else
            {{-- Bagian formulir (dikunci penuh di mode lihat) --}}
            <fieldset @disabled($modeLihat)
                      @class([
                          'm-0 min-w-0 space-y-4 border-0 p-0',
                          'st-lihat' => $modeLihat,
                      ])>
                @foreach ($sections as $section)
                    @include('user.serahterimacatatan.partials.' . $section)
                @endforeach
            </fieldset>
        @endif

    </div>

    {{-- Arahkan layar ke pesan kesalahan agar tidak terlewat --}}
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var box = document.getElementById('stErrorBox');

                if (box) {
                    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        </script>
    @endif
@endsection
@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<div class="px-6 py-6">

    {{-- Header Halaman --}}
    <div class="mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#1d2d50]">
                Dashboard
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Ringkasan aktivitas dan operasional PJL
            </p>
        </div>
    </div>


    {{-- Statistik --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

        {{-- Total Jadwal KA --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Total Jadwal KA
                    </p>

                    <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                        {{ number_format($totalJadwalKa) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-[#1d2d50]">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 17h16M6 17V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10M8 9h8M8 13h8"
                        />
                    </svg>
                </div>
            </div>
        </div>


        {{-- Realisasi KA --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Realisasi KA
                    </p>

                    <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                        {{ number_format($totalRealisasiKa) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 12l4 4L19 6"
                        />
                    </svg>
                </div>
            </div>
        </div>


        {{-- Catatan Dinas --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Catatan Dinas
                    </p>

                    <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                        {{ number_format($totalCatatanDinas) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-yellow-50 text-yellow-600">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 5h6M9 3h6a1 1 0 0 1 1 1v1h1a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h1V4a1 1 0 0 1 1-1Z"
                        />
                    </svg>
                </div>
            </div>
        </div>


        {{-- Total Inventaris --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Total Inventaris
                    </p>

                    <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                        {{ number_format($totalInventaris) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 7h16M6 7v12h12V7M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                        />
                    </svg>
                </div>
            </div>
        </div>


        {{-- Realisasi Inventaris --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Realisasi Inventaris
                    </p>

                    <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                        {{ number_format($totalRealisasiInventaris) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-50 text-[#f5a400]">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"
                        />
                    </svg>
                </div>
            </div>
        </div>

    </div>


    {{-- Realisasi Perlintasan Terakhir --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- Table Header --}}
        <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h3 class="text-base font-bold text-[#1d2d50]">
                    Realisasi Perlintasan Terakhir
                </h3>

                <p class="mt-1 text-xs text-gray-500">
                    Data realisasi KA yang terakhir tercatat
                </p>
            </div>

            <a
                href="#"
                class="text-sm font-semibold text-[#1d2d50] hover:text-[#f5a400]"
            >
                Lihat Semua
            </a>

        </div>


        {{-- Table --}}
        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-gray-200">

                <thead class="bg-gray-50">

                    <tr>

                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                            Nomor & Nama KA
                        </th>

                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                            Titik Pos PJL (JPL)
                        </th>

                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                            Waktu Aktual
                        </th>

                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                            Status Perlintasan
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100 bg-white">

                    @forelse ($realisasiTerakhir as $realisasi)

                        <tr class="transition hover:bg-gray-50">

                            {{-- KA --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#1d2d50] text-xs font-bold text-white">
                                        KA
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ $realisasi->nomor_ka ?? '-' }}
                                        </p>

                                        <p class="mt-0.5 text-xs text-gray-500">
                                            {{ $realisasi->nama_ka ?? '-' }}
                                        </p>
                                    </div>

                                </div>

                            </td>


                            {{-- JPL --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <div>
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ $realisasi->kode_jpl ?? '-' }}
                                    </p>

                                    <p class="mt-0.5 text-xs text-gray-500">
                                        {{ $realisasi->nama_jpl ?? '-' }}
                                    </p>
                                </div>

                            </td>


                            {{-- Waktu Aktual --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                @if ($realisasi->jam_aktual)

                                    <p class="text-sm font-semibold text-gray-800">
                                        {{ \Carbon\Carbon::parse($realisasi->jam_aktual)->format('H:i:s') }}
                                    </p>

                                    <p class="mt-0.5 text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($realisasi->jam_aktual)->format('d/m/Y') }}
                                    </p>

                                @else

                                    <span class="text-sm text-gray-400">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                @php
                                    $status = strtolower((string) $realisasi->status_lewat);
                                @endphp

                                @if ($status === 'lewat' || $status === 'jalan' || $status === 'berjalan')

                                    <span class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                        Lewat
                                    </span>

                                @elseif ($status === 'batal' || $status === 'dibatalkan')

                                    <span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                        Dibatalkan
                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                        {{ $realisasi->status_lewat ?: 'Belum Ada Status' }}
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="4"
                                class="px-6 py-12 text-center"
                            >

                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 0 0 2-2V7.828a2 2 0 0 0-.586-1.414l-3.828-3.828A2 2 0 0 0 15.172 2H5a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2Z"
                                        />
                                    </svg>

                                </div>

                                <p class="mt-3 text-sm font-medium text-gray-600">
                                    Belum ada realisasi perlintasan
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
                                    Data realisasi KA akan tampil di sini.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>
@endsection
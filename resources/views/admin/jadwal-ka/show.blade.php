@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a
                href="{{ route('admin.jadwal-ka.index') }}"
                class="text-sm font-medium text-blue-700 hover:underline"
            >
                &larr; Kembali ke daftar JPL
            </a>

            <h1 class="mt-3 text-2xl font-bold text-slate-800">
                Jadwal KA — {{ $jpl->kode_jpl }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ $jpl->nama_jpl ?? 'Nama JPL tidak tersedia' }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Tanggal:
                {{ \Carbon\Carbon::parse($tanggalHariIni ?? now())->format('d-m-Y') }}
            </p>
        </div>

        <a
            href="{{ route('admin.jadwal-ka.import') }}"
            class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800"
        >
            Import Excel
        </a>
    </div>

    {{-- Total jadwal --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-sm text-slate-500">
            Total jadwal pada JPL ini
        </p>

        <p class="mt-1 text-2xl font-bold text-slate-800">
            {{ $jadwal->total() }}
        </p>

        <p class="mt-1 text-xs text-slate-400">
            Jumlah jadwal yang sesuai tanggal dan filter saat ini.
        </p>
    </div>

    {{-- Notifikasi sukses --}}
    @if (session('success'))
        <div
            role="alert"
            class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800"
        >
            {{ session('success') }}
        </div>
    @endif

    {{-- Notifikasi error --}}
    @if (session('error'))
        <div
            role="alert"
            class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
            {{ session('error') }}
        </div>
    @endif

    {{-- Validasi --}}
    @if ($errors->any())
        <div
            role="alert"
            class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
            <p class="mb-2 font-semibold">
                Terdapat kesalahan:
            </p>

            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filter pencarian dan status --}}
    <form
        method="GET"
        action="{{ route('admin.jadwal-ka.show', $jpl->id) }}"
        class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4"
    >
        <div class="sm:col-span-2">
            <label
                for="q"
                class="mb-1 block text-xs font-semibold text-slate-600"
            >
                Pencarian
            </label>

            <input
                id="q"
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari nomor atau nama KA..."
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
        </div>

        <div>
            <label
                for="status"
                class="mb-1 block text-xs font-semibold text-slate-600"
            >
                Status jadwal
            </label>

            <select
                id="status"
                name="status"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Semua status</option>

                <option
                    value="aktif"
                    @selected(request('status') === 'aktif')
                >
                    Aktif
                </option>

                <option
                    value="nonaktif"
                    @selected(request('status') === 'nonaktif')
                >
                    Nonaktif
                </option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button
                type="submit"
                class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900"
            >
                Terapkan
            </button>

            <a
                href="{{ route('admin.jadwal-ka.show', $jpl->id) }}"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            >
                Reset
            </a>
        </div>
    </form>

    {{-- Tabel jadwal --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
            <h2 class="font-semibold text-slate-800">
                Daftar Jadwal Kereta Api
            </h2>

            <span class="text-xs text-slate-500">
                {{ $jadwal->firstItem() ?? 0 }}
                – {{ $jadwal->lastItem() ?? 0 }}
                dari {{ $jadwal->total() }} jadwal
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3">
                            Nomor KA
                        </th>

                        <th class="whitespace-nowrap px-4 py-3">
                            Nama KA
                        </th>

                        <th class="whitespace-nowrap px-4 py-3">
                            Dari
                        </th>

                        <th class="whitespace-nowrap px-4 py-3">
                            Ke
                        </th>

                        <th class="whitespace-nowrap px-4 py-3">
                            Jam Lewat
                        </th>

                        <th class="min-w-48 px-4 py-3">
                            Keterangan
                        </th>

                        <th class="whitespace-nowrap px-4 py-3">
                            Status
                        </th>

                        <th class="whitespace-nowrap px-4 py-3">
                            Batch Import
                        </th>

                        <th class="whitespace-nowrap px-4 py-3">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($jadwal as $item)
                        <tr
                            class="transition hover:bg-slate-50"
                            wire:key="jadwal-ka-{{ $item->id }}"
                        >
                            {{-- Nomor KA --}}
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">
                                {{ $item->keretaApi?->nomor_ka ?? '—' }}
                            </td>

                            {{-- Nama KA --}}
                            <td class="px-4 py-3 text-slate-600">
                                {{ $item->keretaApi?->nama_ka ?? '—' }}
                            </td>

                            {{-- Stasiun asal --}}
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700">
                                {{ filled($item->dari) ? $item->dari : '—' }}
                            </td>

                            {{-- Stasiun tujuan --}}
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700">
                                {{ filled($item->ke) ? $item->ke : '—' }}
                            </td>

                            {{-- Jam lewat --}}
                            <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-slate-800">
                                {{ $item->jam_lewat
                                    ? substr((string) $item->jam_lewat, 0, 5)
                                    : '—' }}
                            </td>

                            {{-- Keterangan --}}
                            <td class="px-4 py-3 text-slate-600">
                                {{ filled($item->keterangan)
                                    ? $item->keterangan
                                    : '—' }}
                            </td>

                            {{-- Status --}}
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($item->is_active)
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            {{-- Batch import --}}
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-400">
                                {{ filled($item->batch_import)
                                    ? substr((string) $item->batch_import, 0, 8)
                                    : '—' }}
                            </td>

                            {{-- Aksi --}}
                            <td class="whitespace-nowrap px-4 py-3">
                                <a
                                    href="{{ route('admin.jadwal-ka.edit', $item->id) }}"
                                    class="inline-flex items-center rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800"
                                >
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="9"
                                class="px-4 py-12 text-center"
                            >
                                <div class="flex flex-col items-center">
                                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-6 w-6 text-slate-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 17v-2a4 4 0 018 0v2M5 21h14M7 7h10M7 11h4M5 3h14a2 2 0 012 2v12a2 2 0 01-2 2H3a2 2 0 01-2-2V5a2 2 0 012-2z"
                                            />
                                        </svg>
                                    </div>

                                    <p class="font-semibold text-slate-700">
                                        Jadwal tidak ditemukan
                                    </p>

                                    <p class="mt-1 max-w-md text-sm text-slate-500">
                                        Belum ada jadwal KA yang sesuai dengan tanggal dan filter saat ini.
                                        Coba ubah kata kunci atau pilih status lain.
                                    </p>

                                    @if (request()->hasAny(['q', 'status']))
                                        <a
                                            href="{{ route('admin.jadwal-ka.show', $jpl->id) }}"
                                            class="mt-4 text-sm font-semibold text-blue-700 hover:underline"
                                        >
                                            Hapus semua filter
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($jadwal->hasPages())
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            {{ $jadwal->links() }}
        </div>
    @endif

</div>
@endsection

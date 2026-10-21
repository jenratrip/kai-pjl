@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6">
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
        </div>

        <a
            href="{{ route('admin.jadwal-ka.import') }}"
            class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"
        >
            Import Excel
        </a>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-sm text-slate-500">Total jadwal pada JPL ini</p>
        <p class="mt-1 text-2xl font-bold text-slate-800">
            {{ $jadwal->total() }}
        </p>
        <p class="mt-1 text-xs text-slate-400">
            Jumlah pada hasil pencarian/filter saat ini.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form
        method="GET"
        action="{{ route('admin.jadwal-ka.show', $jpl->id) }}"
        class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4"
    >
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Cari nomor / nama KA"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm sm:col-span-2"
        >

        <select
            name="status"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
        >
            <option value="">Semua status</option>
            <option value="aktif" @selected(request('status') === 'aktif')>
                Aktif
            </option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>
                Nonaktif
            </option>
        </select>

        <div class="flex gap-2">
            <button
                type="submit"
                class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900"
            >
                Terapkan
            </button>

            <a
                href="{{ route('admin.jadwal-ka.show', $jpl->id) }}"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
            >
                Reset
            </a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nomor KA</th>
                    <th class="px-4 py-3">Nama KA</th>
                    <th class="px-4 py-3">Jam Lewat</th>
                    <th class="px-4 py-3">Keterangan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Batch Import</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($jadwal as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">
                            {{ $item->keretaApi?->nomor_ka ?? '-' }}
                        </td>

                        <td class="px-4 py-3 text-slate-600">
                            {{ $item->keretaApi?->nama_ka ?? '-' }}
                        </td>

                        <td class="whitespace-nowrap px-4 py-3 font-mono text-slate-800">
                            {{ substr((string) $item->jam_lewat, 0, 5) }}
                        </td>

                        <td class="px-4 py-3 text-slate-600">
                            {{ $item->keterangan ?? '-' }}
                        </td>

                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($item->is_active)
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                    Aktif
                                </span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    Nonaktif
                                </span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-400">
                            {{ $item->batch_import ? substr($item->batch_import, 0, 8) : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                            Belum ada jadwal KA pada JPL ini atau tidak ada jadwal yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $jadwal->links() }}
    </div>
</div>
@endsection

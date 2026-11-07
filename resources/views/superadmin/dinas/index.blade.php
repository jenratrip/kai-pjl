@extends('layouts.superadmin')

@section('title', 'Daftar Dinasan')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Daftar Dinasan</h1>
            <p class="mt-1 text-sm text-gray-500">Pantau jadwal dan status dinas petugas PJL.</p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
            Hanya baca
        </span>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Filter tidak valid.</p>
            <p class="mt-1">Periksa kembali filter yang dipilih, lalu coba lagi.</p>
        </div>
    @endif

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="overflow-x-auto pb-1">
            <form id="dinas-filter-form" method="GET" action="{{ route('superadmin.dinas.index') }}"
                  class="grid min-w-[980px] grid-cols-[minmax(220px,1.6fr)_repeat(3,minmax(150px,1fr))_minmax(160px,1fr)] items-end gap-3 2xl:min-w-0">
            <div class="min-w-0">
                <label for="search" class="mb-1.5 block text-sm font-medium text-gray-700">Cari Petugas</label>
                <input id="search" name="search" type="search" maxlength="100"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="Nama atau NIP petugas"
                       class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>

            <div class="min-w-0">
                <label for="user_id" class="mb-1.5 block text-sm font-medium text-gray-700">Petugas</label>
                <select id="user_id" name="user_id" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">Semua petugas</option>
                    @foreach ($petugas as $pjl)
                        <option value="{{ $pjl->id }}" @selected(($filters['user_id'] ?? '') == $pjl->id)>
                            {{ $pjl->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <label for="jpl_id" class="mb-1.5 block text-sm font-medium text-gray-700">JPL</label>
                <select id="jpl_id" name="jpl_id" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">Semua JPL</option>
                    @foreach ($jpls as $jpl)
                        <option value="{{ $jpl->id }}" @selected(($filters['jpl_id'] ?? '') == $jpl->id)>
                            {{ $jpl->kode_jpl }} — {{ $jpl->nama_jpl }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <label for="shift_id" class="mb-1.5 block text-sm font-medium text-gray-700">Shift</label>
                <select id="shift_id" name="shift_id" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">Semua shift</option>
                    @foreach ($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected(($filters['shift_id'] ?? '') == $shift->id)>
                            {{ $shift->nama_shift }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <label for="tanggal_dinas" class="mb-1.5 block text-sm font-medium text-gray-700">Tanggal dinas</label>
                <input id="tanggal_dinas" name="tanggal_dinas" type="date"
                       value="{{ $filters['tanggal_dinas'] ?? '' }}"
                       class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>

            </form>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-800">Data Dinasan</h2>
            <p class="mt-1 text-sm text-gray-500">{{ number_format($dinas->total()) }} data ditemukan.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th scope="col" class="px-4 py-3.5">Petugas</th>
                        <th scope="col" class="px-4 py-3.5">NIP</th>
                        <th scope="col" class="px-4 py-3.5">JPL</th>
                        <th scope="col" class="px-4 py-3.5">Shift</th>
                        <th scope="col" class="px-4 py-3.5">Tanggal</th>
                        <th scope="col" class="px-4 py-3.5">Jam mulai</th>
                        <th scope="col" class="px-4 py-3.5">Jam selesai</th>
                        <th scope="col" class="px-4 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($dinas as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3.5 font-medium text-gray-800">{{ $item->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-gray-600">{{ $item->user?->nip ?? '—' }}</td>
                            <td class="px-4 py-3.5">
                                @if ($item->jpl)
                                    <span class="font-medium text-gray-800">{{ $item->jpl->kode_jpl }}</span>
                                    <span class="block text-xs text-gray-500">{{ $item->jpl->nama_jpl }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-700">{{ $item->shift?->nama_shift ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-gray-700">{{ $item->tanggal_dinas?->format('d/m/Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-gray-700">{{ $item->jam_mulai?->format('H:i') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-gray-700">{{ $item->jam_selesai?->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-3.5">
                                @php
                                    $statusClass = match ($item->status) {
                                        'berlangsung' => 'bg-green-50 text-green-700',
                                        'selesai' => 'bg-slate-100 text-slate-700',
                                        'dijadwalkan' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $item->status ?? 'tidak diketahui')) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500">
                                Tidak ada data Dinasan yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($dinas->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $dinas->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('dinas-filter-form');
        const searchInput = document.getElementById('search');

        if (!form) {
            return;
        }

        let searchTimer;

        searchInput?.addEventListener('input', function () {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(function () {
                form.requestSubmit();
            }, 450);
        });

        form.querySelectorAll('select, input[type="date"]').forEach(function (filter) {
            filter.addEventListener('change', function () {
                window.clearTimeout(searchTimer);
                form.requestSubmit();
            });
        });
    });
</script>
@endpush

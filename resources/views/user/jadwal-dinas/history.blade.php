@extends('layouts.user')

@section('content')
<div class="min-h-screen bg-slate-50 px-4 py-6 md:px-6">
    <div class="mx-auto max-w-7xl space-y-6">

        {{-- HEADER --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Riwayat Tukar Jadwal
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Riwayat seluruh permintaan pertukaran jadwal yang pernah Anda buat atau terima.
                </p>
            </div>

            <a
                href="{{ route('user.tukar-jadwal-dinas') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                <i class="fas fa-arrow-left"></i>
                Kembali ke Tukar Jadwal
            </a>
        </div>

        {{-- FILTER --}}
        <form
            method="GET"
            action="{{ route('user.tukar-jadwal-dinas.history') }}"
            class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end"
        >
            <div class="flex-1">
                <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                    Status Permintaan
                </label>
                <select
                    name="status"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-orange-400"
                >
                    <option value="">Semua status</option>
                    <option value="menunggu" @selected(request('status') === 'menunggu')>Menunggu</option>
                    <option value="berhasil" @selected(request('status') === 'berhasil')>Berhasil</option>
                    <option value="ditolak" @selected(request('status') === 'ditolak')>Ditolak</option>
                    <option value="dibatalkan" @selected(request('status') === 'dibatalkan')>Dibatalkan</option>
                    <option value="gagal" @selected(request('status') === 'gagal')>Gagal</option>
                </select>
            </div>

            <div class="flex-1">
                <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                    Jenis Riwayat
                </label>
                <select
                    name="type"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-orange-400"
                >
                    <option value="">Semua riwayat</option>
                    <option value="dibuat" @selected(request('type') === 'dibuat')>Saya Mengajukan</option>
                    <option value="diterima" @selected(request('type') === 'diterima')>Saya Menerima</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-600"
                >
                    <i class="fas fa-filter mr-1"></i>
                    Filter
                </button>

                <a
                    href="{{ route('user.tukar-jadwal-dinas.history') }}"
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                >
                    Reset
                </a>
            </div>
        </form>

        {{-- RIWAYAT DIBUAT --}}
        @if (!request('type') || request('type') === 'dibuat')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="font-bold text-slate-800">Permintaan yang Saya Buat</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Riwayat pengajuan pertukaran jadwal kepada petugas lain.
                        </p>
                    </div>
                    <span class="text-xs text-slate-500">
                        {{ $dibuat->total() }} data
                    </span>
                </div>

                @forelse ($dibuat as $item)
                    @php
                        $asal = $item->snapshot_dinas_pemohon ?? [];
                        $tujuan = $item->snapshot_dinas_tujuan ?? [];

                        $statusStyle = match ($item->status) {
                            'menunggu' => 'bg-amber-50 text-amber-700',
                            'berhasil' => 'bg-emerald-50 text-emerald-700',
                            'ditolak' => 'bg-rose-50 text-rose-700',
                            'dibatalkan' => 'bg-slate-100 text-slate-600',
                            'gagal' => 'bg-red-50 text-red-700',
                            default => 'bg-slate-100 text-slate-600',
                        };
                    @endphp

                    <div class="border-b border-slate-100 p-5 last:border-0">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        {{ $item->diajukan_pada?->format('d M Y, H:i') ?? '-' }}
                                    </span>
                                </div>

                                <p class="mb-3 text-sm text-slate-700">
                                    Penerima:
                                    <span class="font-semibold">
                                        {{ $item->penerima_nama_snapshot ?? $item->penerima?->name ?? 'Pengguna tidak tersedia' }}
                                    </span>
                                </p>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <p class="text-xs font-semibold text-slate-400">JADWAL ASAL</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-800">
                                            {{ $asal['tanggal'] ?? $asal['tanggal_dinas'] ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $asal['jam_mulai'] ?? '-' }} – {{ $asal['jam_selesai'] ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $asal['jpl_nama'] ?? $asal['jpl'] ?? '-' }}
                                            ·
                                            {{ $asal['shift_nama'] ?? $asal['shift'] ?? '-' }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl bg-orange-50 p-3">
                                        <p class="text-xs font-semibold text-orange-500">JADWAL TUJUAN</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-800">
                                            {{ $tujuan['tanggal'] ?? $tujuan['tanggal_dinas'] ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $tujuan['jam_mulai'] ?? '-' }} – {{ $tujuan['jam_selesai'] ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $tujuan['jpl_nama'] ?? $tujuan['jpl'] ?? '-' }}
                                            ·
                                            {{ $tujuan['shift_nama'] ?? $tujuan['shift'] ?? '-' }}
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-3 line-clamp-2 text-sm text-slate-500">
                                    {{ $item->alasan_pemohon }}
                                </p>
                            </div>

                            <a
                                href="{{ route('user.tukar-jadwal-dinas.show', $item->id) }}"
                                class="shrink-0 rounded-lg border border-slate-200 px-3 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center">
                        <i class="fas fa-folder-open text-2xl text-slate-300"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-700">
                            Belum ada riwayat pengajuan
                        </p>
                    </div>
                @endforelse

                @if ($dibuat->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">
                        {{ $dibuat->appends(request()->except('dibuat_page'))->links() }}
                    </div>
                @endif
            </section>
        @endif

        {{-- RIWAYAT DITERIMA --}}
        @if (!request('type') || request('type') === 'diterima')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="font-bold text-slate-800">Permintaan yang Saya Terima</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Riwayat permintaan pertukaran jadwal yang ditujukan kepada Anda.
                        </p>
                    </div>
                    <span class="text-xs text-slate-500">
                        {{ $diterima->total() }} data
                    </span>
                </div>

                @forelse ($diterima as $item)
                    @php
                        $asal = $item->snapshot_dinas_pemohon ?? [];
                        $tujuan = $item->snapshot_dinas_tujuan ?? [];

                        $statusStyle = match ($item->status) {
                            'menunggu' => 'bg-amber-50 text-amber-700',
                            'berhasil' => 'bg-emerald-50 text-emerald-700',
                            'ditolak' => 'bg-rose-50 text-rose-700',
                            'dibatalkan' => 'bg-slate-100 text-slate-600',
                            'gagal' => 'bg-red-50 text-red-700',
                            default => 'bg-slate-100 text-slate-600',
                        };
                    @endphp

                    <div class="border-b border-slate-100 p-5 last:border-0">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        {{ $item->diajukan_pada?->format('d M Y, H:i') ?? '-' }}
                                    </span>
                                </div>

                                <p class="mb-3 text-sm text-slate-700">
                                    Pemohon:
                                    <span class="font-semibold">
                                        {{ $item->pemohon_nama_snapshot ?? $item->pemohon?->name ?? 'Pengguna tidak tersedia' }}
                                    </span>
                                </p>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <p class="text-xs font-semibold text-slate-400">JADWAL PEMOHON</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-800">
                                            {{ $asal['tanggal'] ?? $asal['tanggal_dinas'] ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $asal['jam_mulai'] ?? '-' }} – {{ $asal['jam_selesai'] ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $asal['jpl_nama'] ?? $asal['jpl'] ?? '-' }}
                                            ·
                                            {{ $asal['shift_nama'] ?? $asal['shift'] ?? '-' }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl bg-orange-50 p-3">
                                        <p class="text-xs font-semibold text-orange-500">JADWAL SAYA</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-800">
                                            {{ $tujuan['tanggal'] ?? $tujuan['tanggal_dinas'] ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $tujuan['jam_mulai'] ?? '-' }} – {{ $tujuan['jam_selesai'] ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $tujuan['jpl_nama'] ?? $tujuan['jpl'] ?? '-' }}
                                            ·
                                            {{ $tujuan['shift_nama'] ?? $tujuan['shift'] ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <a
                                href="{{ route('user.tukar-jadwal-dinas.show', $item->id) }}"
                                class="shrink-0 rounded-lg border border-slate-200 px-3 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center">
                        <i class="fas fa-inbox text-2xl text-slate-300"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-700">
                            Belum ada riwayat permintaan masuk
                        </p>
                    </div>
                @endforelse

                @if ($diterima->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">
                        {{ $diterima->appends(request()->except('diterima_page'))->links() }}
                    </div>
                @endif
            </section>
        @endif

    </div>
</div>
@endsection

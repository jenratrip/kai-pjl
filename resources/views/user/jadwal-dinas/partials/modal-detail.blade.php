@php
    $snapshotPemohon = $permintaan->snapshot_dinas_pemohon ?? [];
    $snapshotTujuan = $permintaan->snapshot_dinas_tujuan ?? [];

    $statusStyle = match ($permintaan->status) {
        'menunggu' => 'bg-amber-50 text-amber-700',
        'berhasil' => 'bg-emerald-50 text-emerald-700',
        'ditolak' => 'bg-rose-50 text-rose-700',
        'dibatalkan' => 'bg-slate-100 text-slate-600',
        'gagal' => 'bg-red-50 text-red-700',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp

<div class="overflow-hidden rounded-2xl bg-white shadow-xl">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Detail Permintaan Tukar Jadwal</h2>
            <p class="mt-1 text-xs text-slate-500">
                Nomor permintaan #{{ $permintaan->id }}
            </p>
        </div>

        <button
            type="button"
            @click="$dispatch('close-tukar-modal')"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
        >
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="max-h-[75vh] space-y-5 overflow-y-auto p-5">

        {{-- STATUS --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $statusStyle }}">
                {{ ucfirst($permintaan->status) }}
            </span>

            <span class="text-xs text-slate-400">
                Diajukan:
                {{ $permintaan->diajukan_pada?->format('d M Y, H:i') ?? '-' }}
            </span>
        </div>

        {{-- PIHAK TERLIBAT --}}
        <div class="grid gap-3 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Pemohon
                </p>
                <p class="mt-2 font-bold text-slate-800">
                    {{ $permintaan->pemohon_nama_snapshot ?? $permintaan->pemohon?->name ?? 'Pengguna tidak tersedia' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    NIP: {{ $permintaan->pemohon_nip_snapshot ?? '-' }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Penerima
                </p>
                <p class="mt-2 font-bold text-slate-800">
                    {{ $permintaan->penerima_nama_snapshot ?? $permintaan->penerima?->name ?? 'Pengguna tidak tersedia' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    NIP: {{ $permintaan->penerima_nip_snapshot ?? '-' }}
                </p>
            </div>
        </div>

        {{-- PERBANDINGAN JADWAL --}}
        <div>
            <h3 class="mb-3 text-sm font-bold text-slate-800">
                Jadwal yang Dipertukarkan
            </h3>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Jadwal Pemohon
                        </p>
                    </div>

                    <p class="text-base font-bold text-slate-800">
                        {{ $snapshotPemohon['tanggal'] ?? $snapshotPemohon['tanggal_dinas'] ?? '-' }}
                    </p>

                    <p class="mt-2 text-sm text-slate-600">
                        {{ $snapshotPemohon['jam_mulai'] ?? '-' }}
                        –
                        {{ $snapshotPemohon['jam_selesai'] ?? '-' }}
                    </p>

                    <div class="mt-3 space-y-1 text-xs text-slate-500">
                        <p>
                            JPL:
                            {{ $snapshotPemohon['jpl_nama'] ?? $snapshotPemohon['jpl'] ?? '-' }}
                        </p>
                        <p>
                            Resort:
                            {{ $snapshotPemohon['resort_nama'] ?? $snapshotPemohon['resort'] ?? '-' }}
                        </p>
                        <p>
                            Shift:
                            {{ $snapshotPemohon['shift_nama'] ?? $snapshotPemohon['shift'] ?? '-' }}
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-orange-200 bg-orange-50/50 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                        <p class="text-xs font-bold uppercase tracking-wide text-orange-600">
                            Jadwal Penerima
                        </p>
                    </div>

                    <p class="text-base font-bold text-slate-800">
                        {{ $snapshotTujuan['tanggal'] ?? $snapshotTujuan['tanggal_dinas'] ?? '-' }}
                    </p>

                    <p class="mt-2 text-sm text-slate-600">
                        {{ $snapshotTujuan['jam_mulai'] ?? '-' }}
                        –
                        {{ $snapshotTujuan['jam_selesai'] ?? '-' }}
                    </p>

                    <div class="mt-3 space-y-1 text-xs text-slate-500">
                        <p>
                            JPL:
                            {{ $snapshotTujuan['jpl_nama'] ?? $snapshotTujuan['jpl'] ?? '-' }}
                        </p>
                        <p>
                            Resort:
                            {{ $snapshotTujuan['resort_nama'] ?? $snapshotTujuan['resort'] ?? '-' }}
                        </p>
                        <p>
                            Shift:
                            {{ $snapshotTujuan['shift_nama'] ?? $snapshotTujuan['shift'] ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALASAN PEMOHON --}}
        <div class="rounded-xl bg-slate-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Alasan Pemohon
            </p>
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $permintaan->alasan_pemohon }}</p>
        </div>

        {{-- ALASAN PENOLAKAN --}}
        @if ($permintaan->status === 'ditolak')
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-rose-600">
                    Alasan Penolakan
                </p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-rose-800">
                    {{ $permintaan->alasan_penerima ?? '-' }}
                </p>
            </div>
        @endif

        {{-- ALASAN KEGAGALAN SISTEM --}}
        @if ($permintaan->status === 'gagal')
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-red-600">
                    Alasan Pertukaran Gagal
                </p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-red-800">
                    {{ $permintaan->alasan_gagal ?? 'Pertukaran tidak dapat dilanjutkan.' }}
                </p>
            </div>
        @endif

        {{-- WAKTU KEPUTUSAN --}}
        @if ($permintaan->dijawab_pada)
            <p class="text-xs text-slate-400">
                Dijawab pada:
                {{ $permintaan->dijawab_pada->format('d M Y, H:i') }}
            </p>
        @endif

        @if ($permintaan->selesai_pada)
            <p class="text-xs text-slate-400">
                Selesai pada:
                {{ $permintaan->selesai_pada->format('d M Y, H:i') }}
            </p>
        @endif

        {{-- FORM TANGGAPAN PENERIMA --}}
        @if ($canRespond && $permintaan->status === 'menunggu')
            @include('user.jadwal-dinas.partials.modal-respond')
        @endif

        {{-- TOMBOL PEMOHON --}}
        @if ($canCancel && $permintaan->status === 'menunggu')
            <div class="border-t border-slate-100 pt-4">
                <button
                    type="button"
                    @click="cancelRequest({{ $permintaan->id }})"
                    class="w-full rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50"
                >
                    Batalkan Permintaan
                </button>
            </div>
        @endif
    </div>
</div>

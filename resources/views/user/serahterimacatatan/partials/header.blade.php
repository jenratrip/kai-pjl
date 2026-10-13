
<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-5 text-center">
        <p class="text-xs font-bold uppercase tracking-wider text-[#1d2d50]">
            PT Kereta Api Indonesia (Persero)
        </p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#1d2d50]">
            Serah Terima Dinasan PJL
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Dokumen digital serah terima dinasan PJL untuk proses handover operasional.
        </p>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <div class="rounded-lg bg-slate-100 p-3">
            <p class="text-xs font-semibold text-slate-500">Petugas</p>
            <p class="mt-1 font-bold text-slate-800">{{ auth()->user()->name }}</p>
        </div>

        <div class="rounded-lg bg-slate-100 p-3">
            <p class="text-xs font-semibold text-slate-500">Tanggal dinas</p>
            <p class="mt-1 font-bold text-slate-800">
                {{ $dinas->tanggal_dinas?->translatedFormat('l, d F Y') ?? '-' }}
            </p>
        </div>

        <div class="rounded-lg bg-slate-100 p-3">
            <p class="text-xs font-semibold text-slate-500">Shift</p>
            <p class="mt-1 font-bold text-slate-800">
                {{ $dinas->shift?->nama_shift ?? $dinas->shift?->nama ?? 'Shift belum tersedia' }}
            </p>
        </div>
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-2">
        <div class="rounded-lg bg-slate-100 p-3">
            <p class="text-xs font-semibold text-slate-500">JPL</p>
            <p class="mt-1 font-bold text-slate-800">
                {{ $dinas->jpl?->nama_jpl ?? $dinas->jpl?->no_jpl ?? $dinas->jpl_id }}
            </p>
        </div>

        <div class="rounded-lg bg-slate-100 p-3">
            <p class="text-xs font-semibold text-slate-500">Status dinas</p>
            <p class="mt-1 font-bold text-slate-800">{{ ucfirst($dinas->status) }}</p>
        </div>
    </div>
</div>

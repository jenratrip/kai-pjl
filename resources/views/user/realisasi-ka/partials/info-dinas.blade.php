{{-- INFORMASI DINAS --}}
<div class="mb-7 grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-[1fr_1fr_1.1fr_0.7fr]">
    <div>
        <label for="tanggal" class="field-label mb-2 block text-sm font-semibold text-slate-700">HARI / TANGGAL</label>
        <input
            type="text"
            id="tanggal"
            class="field-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
            value="{{ ($dinasAktif ?? null)
                ? \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)->locale('id')->translatedFormat('l, d F Y')
                : \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}"
            readonly>
    </div>

    <div>
        <label for="nomorJpl" class="field-label mb-2 block text-sm font-semibold text-slate-700">NOMOR JPL</label>
        <input
            id="nomorJpl"
            type="text"
            class="field-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
            value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->kode_jpl ?? '-') : '-' }}"
            readonly>
    </div>

    <div>
        <label for="stasiunRegional" class="field-label mb-2 block text-sm font-semibold text-slate-700">STASIUN / REGIONAL</label>
        <input
            id="stasiunRegional"
            type="text"
            class="field-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
            value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->nama_jpl ?? '-') : '-' }}"
            readonly>
    </div>

    <div>
        <label for="shift" class="field-label mb-2 block text-sm font-semibold text-slate-700">SHIFT</label>
        <input
            id="shift"
            type="text"
            class="field-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
            value="{{ ($dinasAktif ?? null) ? ($dinasAktif->shift?->nama_shift ?? '-') : '-' }}"
            readonly>
    </div>
</div>
<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    @php
        $kaBerjalanTeks = collect($ringkasanKA['berjalan'] ?? [])
            ->map(function ($ka) {
                $nomor = trim((string) ($ka['nomor'] ?? '-'));
                $nama = trim((string) ($ka['nama'] ?? ''));

                if ($nomor === '' || $nomor === '-') {
                    return $nama !== '' && $nama !== '-'
                        ? $nama
                        : '-';
                }

                if ($nama !== '' && $nama !== '-') {
                    return $nomor . ' - ' . $nama;
                }

                return $nomor;
            })
            ->unique()
            ->implode("\n");
    @endphp

    {{-- Header bagian --}}
    <div class="mb-4 flex items-center gap-3">
        <span
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#1d2d50] text-base font-bold text-white"
        >
            2
        </span>

        <h2 class="text-lg font-bold text-[#1d2d50]">
            KA-KA Fak/PLB/KLB yang berjalan
        </h2>
    </div>

    <label
        for="ka_berjalan"
        class="mb-2 block text-sm font-medium text-[#1d2d50]"
    >
        Nomor dan nama KA yang berjalan selama dinas.
    </label>

    <textarea
        id="ka_berjalan"
        name="ka_berjalan"
        data-summary="ka_berjalan"
        rows="4"
        readonly
        placeholder="Tidak ada KA berjalan"
        class="w-full rounded-xl border border-slate-300 bg-slate-50 p-4 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
    >{{ $kaBerjalanTeks !== '' ? $kaBerjalanTeks : 'Tidak ada' }}</textarea>
</section>
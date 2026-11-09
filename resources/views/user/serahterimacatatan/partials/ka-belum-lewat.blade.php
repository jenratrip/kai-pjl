@php
    $kaBelumLewatTeks = collect($ringkasanKA['belum'] ?? [])
        ->map(function ($ka) {
            $nomor = trim((string) ($ka['nomor'] ?? '-'));
            $nama = trim((string) ($ka['nama'] ?? ''));

            if ($nomor === '' || $nomor === '-') {
                return $nama !== '' && $nama !== '-'
                    ? $nama
                    : '-';
            }

            return $nama !== '' && $nama !== '-'
                ? $nomor . ' - ' . $nama
                : $nomor;
        })
        ->filter(fn ($teks) => $teks !== '-')
        ->unique()
        ->values()
        ->implode("\n");
@endphp

<section class="serah-terima-ka-section rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <div class="mb-4 flex items-center gap-3">
        <span
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#1d2d50] text-base font-bold text-white"
        >
            3
        </span>

        <h2 class="text-lg font-bold text-[#1d2d50]">
            KA-KA yang Belum Lewat
        </h2>
    </div>

    <label
        for="ka_belum_lewat"
        class="mb-2 block text-sm font-medium text-[#1d2d50]"
    >
        Nomor dan nama KA yang belum lewat selama dinas.
    </label>

    <div class="serah-terima-ka-list-screen">
        <textarea
            id="ka_belum_lewat"
            name="ka_belum_lewat"
            data-summary="ka_belum_lewat"
            rows="4"
            readonly
            placeholder="Tidak ada KA yang belum lewat"
            class="w-full rounded-xl border border-slate-300 bg-slate-50 p-4 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
        >{{ $kaBelumLewatTeks !== '' ? $kaBelumLewatTeks : 'Tidak ada' }}</textarea>
    </div>

    @include('user.serahterimacatatan.partials.ka-print-table', [
        'rows' => $ringkasanKA['belum'] ?? [],
        'statusLabel' => 'Belum lewat',
        'showDetails' => false,
    ])
</section>

<section class="serah-terima-ka-section rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    @php
        $kaBatal = collect($ringkasanKA['batal'] ?? [])
            ->map(function ($ka) {
                $nomor = trim((string) ($ka['nomor'] ?? '-'));
                $nama = trim((string) ($ka['nama'] ?? ''));

                return [
                    'id' => $ka['id'] ?? null,
                    'nomor' => $nomor,
                    'nama' => $nama,
                    'semboyan21' => $ka['semboyan21'] ?? '',
                    'genta' => $ka['genta'] ?? '',
                    'pintu' => $ka['pintu'] ?? '',
                    'semboyan35' => $ka['semboyan35'] ?? '',
                ];
            })
            ->unique(function ($ka) {
                return $ka['nomor'] . '|' . $ka['nama'];
            })
            ->values();

        $daftarNomorKA = $kaBatal
            ->pluck('nomor')
            ->filter(fn ($nomor) => $nomor !== '' && $nomor !== '-')
            ->unique()
            ->implode(', ');
        $kaBatalTeks = $kaBatal
            ->map(function ($ka) {
                if ($ka['nomor'] === '' || $ka['nomor'] === '-') {
                    return $ka['nama'] !== '' && $ka['nama'] !== '-'
                        ? $ka['nama']
                        : '-';
                }

                return $ka['nama'] !== '' && $ka['nama'] !== '-'
                    ? $ka['nomor'] . ' - ' . $ka['nama']
                    : $ka['nomor'];
            })
            ->filter(fn ($teks) => $teks !== '-')
            ->unique()
            ->values()
            ->implode("\n");

    @endphp

    {{-- Header bagian --}}
    <div class="mb-4 flex items-center gap-3">
        <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white"
        >
            1
        </span>

        <div>
            <h2 class="font-bold text-[#1d2d50]">
                KA-KA yang batal
            </h2>

            <p class="mt-0.5 text-xs text-slate-500">
                Daftar kereta api yang dibatalkan selama dinas.
            </p>
        </div>
    </div>

    {{-- Nilai otomatis untuk dikirim melalui JavaScript --}}
    <input
        type="hidden"
        id="ka_batal"
        name="ka_batal"
        data-summary="ka_batal"
        value="{{ $daftarNomorKA }}"
    >

    {{-- Daftar KA batal dari realisasi --}}
    <div class="serah-terima-ka-list-screen">
        <textarea
            id="ka_batal_daftar"
            rows="4"
            readonly
            placeholder="Tidak ada KA yang batal"
            class="w-full rounded-xl border border-slate-300 bg-slate-50 p-4 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
        >{{ $kaBatalTeks !== '' ? $kaBatalTeks : 'Tidak ada' }}</textarea>
    </div>

    @include('user.serahterimacatatan.partials.ka-print-table', [
        'rows' => $ringkasanKA['batal'] ?? [],
        'statusLabel' => 'Batal',
        'showDetails' => false,
    ])

    <p class="mt-3 flex items-start gap-2 text-xs leading-relaxed text-slate-500">
        <span aria-hidden="true">ⓘ</span>

        <span>
            Data diambil otomatis dari Realisasi KA pada dinas aktif. Untuk mengubah status KA, lakukan melalui halaman Realisasi KA.
        </span>
    </p>
</section>

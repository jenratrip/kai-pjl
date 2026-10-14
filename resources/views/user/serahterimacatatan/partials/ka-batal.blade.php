<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    @php
        $kaBatal = collect($ringkasanKA['batal'] ?? [])
            ->map(function ($ka) {
                $nomor = trim((string) ($ka['nomor'] ?? '-'));
                $nama = trim((string) ($ka['nama'] ?? ''));

                return [
                    'id' => $ka['id'] ?? null,
                    'nomor' => $nomor,
                    'nama' => $nama,
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
    <div class="space-y-2">
        @forelse($kaBatal as $ka)
            <div
                class="rounded-lg border border-slate-200 bg-slate-50 p-3"
                wire:key="ka-batal-{{ $ka['id'] ?? $loop->index }}"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">
                            {{ $ka['nomor'] !== '' ? $ka['nomor'] : '-' }}
                        </p>

                        @if($ka['nama'] !== '' && $ka['nama'] !== '-')
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $ka['nama'] }}
                            </p>
                        @endif
                    </div>

                    <span class="shrink-0 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                        BATAL
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-center">
                <p class="text-sm font-medium text-slate-600">
                    Tidak ada KA yang batal.
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    Daftar akan terisi otomatis jika terdapat realisasi KA berstatus batal.
                </p>
            </div>
        @endforelse
    </div>

    <p class="mt-3 flex items-start gap-2 text-xs leading-relaxed text-slate-500">
        <span aria-hidden="true">ⓘ</span>

        <span>
            Data diambil otomatis dari Realisasi KA pada dinas aktif. Untuk mengubah status KA, lakukan melalui halaman Realisasi KA.
        </span>
    </p>
</section>
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

<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    {{-- Header bagian --}}
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

    {{-- Label --}}
    <label
        for="ka_belum_lewat"
        class="mb-2 block text-sm font-medium text-[#1d2d50]"
    >
        Nomor dan nama KA yang belum lewat selama dinas.
    </label>

    {{-- Ringkasan KA: tetap diam, tidak ikut scroll --}}
    <textarea
        id="ka_belum_lewat"
        name="ka_belum_lewat"
        data-summary="ka_belum_lewat"
        rows="4"
        readonly
        placeholder="Tidak ada KA yang belum lewat"
        class="w-full rounded-xl border border-slate-300 bg-slate-50 p-4 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
    >{{ $kaBelumLewatTeks !== '' ? $kaBelumLewatTeks : 'Tidak ada' }}</textarea>

    {{-- Daftar KA: hanya bagian ini yang dapat di-scroll --}}
    <div class="mt-3 max-h-[360px] space-y-2 overflow-y-auto overscroll-contain pr-2">
        @forelse ($ringkasanKA['belum'] ?? [] as $ka)
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="font-semibold text-slate-800">
                    {{ $ka['nomor'] ?? '-' }}

                    @if (! empty($ka['nama']))
                        <span class="font-normal text-slate-600">
                            — {{ $ka['nama'] }}
                        </span>
                    @endif
                </p>

                @include('user.serahterimacatatan.partials.ka-check-indicators', [
                    'ka' => $ka,
                ])
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-center text-sm text-slate-500">
                Tidak ada KA yang belum lewat.
            </div>
        @endforelse
    </div>
</section>

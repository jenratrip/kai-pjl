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

    {{-- Daftar KA --}}
    <div class="max-h-[360px] space-y-2 overflow-y-auto overscroll-contain pr-2">
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
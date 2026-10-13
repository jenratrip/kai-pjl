
<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white">4</span>
        <h2 class="font-bold text-[#1d2d50]">Alat pengamanan</h2>
    </div>

    <div class="space-y-3">
        @foreach ([
            'perangkat_pintu' => 'Perangkat pintu perlintasan',
            'perangkat_telekomunikasi' => 'Perangkat telekomunikasi',
        ] as $key => $label)
            <div class="flex flex-col gap-3 rounded-lg border border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>

                <div class="flex gap-4 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="alat[{{ $key }}]" value="normal" checked>
                        Normal
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="alat[{{ $key }}]" value="tidak_normal">
                        Tidak normal
                    </label>
                </div>

                <input type="text" data-alat-catatan="{{ $key }}"
                    placeholder="Catatan jika bermasalah"
                    class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm sm:max-w-xs">
            </div>
        @endforeach
    </div>
</section>

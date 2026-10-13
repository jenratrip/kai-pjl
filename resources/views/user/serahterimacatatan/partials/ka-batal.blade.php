<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
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

    {{-- Input nomor KA --}}
    <div>
        <label
            for="ka_batal"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            Nomor KA yang batal
        </label>

        <textarea
            id="ka_batal"
            data-summary="ka_batal"
            rows="3"
            placeholder="Contoh: KA 123&#10;KA 456&#10;KA 789"
            class="w-full rounded-lg border border-slate-300 bg-slate-50 p-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1d2d50] focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
        >{{ old('ka_batal') }}</textarea>

        <p class="mt-2 flex items-start gap-2 text-xs leading-relaxed text-slate-500">
            <span aria-hidden="true">ⓘ</span>
            <span>
                Masukkan nomor KA, satu nomor per baris atau pisahkan dengan koma.
                Jika tidak ada KA yang batal, biarkan kosong.
            </span>
        </p>
    </div>
</section>

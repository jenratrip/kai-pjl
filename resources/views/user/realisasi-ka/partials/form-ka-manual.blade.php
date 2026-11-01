{{-- TAMBAH KA MANUAL --}}
<section class="mt-4 w-full min-w-0">
    <button
        type="button"
        id="toggleTambahKa"
        class="rounded-md border border-[#1d2d50] bg-white px-3 py-1.5 text-xs font-semibold text-[#1d2d50] transition hover:bg-slate-50"
        aria-expanded="false"
        aria-controls="formTambahKa"
        onclick="toggleFormTambahKA()">
        + Tambah KA
    </button>

    <div class="mt-3 w-full min-w-0 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:p-4" id="formTambahKa" hidden>
        <div class="mb-4 text-sm font-bold text-[#1d2d50]">Tambah KA Manual</div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="min-w-0">
                <label for="manualNomor" class="mb-1.5 block text-xs font-semibold text-slate-700">NOMOR KA</label>
                <input id="manualNomor" type="text"
                    class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                    placeholder="Nomor KA">
            </div>

            <div class="min-w-0">
                <label for="manualNama" class="mb-1.5 block text-xs font-semibold text-slate-700">NAMA KA</label>
                <input id="manualNama" type="text"
                    class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                    placeholder="Nama KA">
            </div>

            <div class="min-w-0">
                <label for="manualHulu" class="mb-1.5 block text-xs font-semibold text-slate-700">HULU</label>
                <input id="manualHulu" type="text"
                    class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                    placeholder="Hulu">
            </div>

            <div class="min-w-0">
                <label for="manualHilir" class="mb-1.5 block text-xs font-semibold text-slate-700">HILIR</label>
                <input id="manualHilir" type="text"
                    class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                    placeholder="Hilir">
            </div>

            <div class="min-w-0">
                <label for="manualRelasi" class="mb-1.5 block text-xs font-semibold text-slate-700">RELASI</label>
                <input id="manualRelasi" type="text"
                    class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                    placeholder="Contoh: YK - SBI">
            </div>

            <div class="min-w-0">
                <label for="manualAktual" class="mb-1.5 block text-xs font-semibold text-slate-700">JAM AKTUAL</label>
                <input id="manualAktual" type="time"
                    class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20">
            </div>

            <div class="min-w-0">
                <label for="manualSemboyan21" class="mb-1.5 block text-xs font-semibold text-slate-700">SEMBOYAN 21</label>
                <div class="mini-check flex items-center gap-1">
                    <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-200" data-group="manual21" data-value="yes" aria-label="Semboyan 21 ya" aria-pressed="false" onclick="setManualCheck(this)">✓</button>
                    <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" data-group="manual21" data-value="no" aria-label="Semboyan 21 tidak" aria-pressed="false" onclick="setManualCheck(this)">×</button>
                </div>
            </div>

            <div class="min-w-0">
                <label for="manualGenta" class="mb-1.5 block text-xs font-semibold text-slate-700">GENTA</label>
                <div class="mini-check flex items-center gap-1">
                    <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-200" data-group="manualGenta" data-value="yes" aria-label="Genta ya" aria-pressed="false" onclick="setManualCheck(this)">✓</button>
                    <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" data-group="manualGenta" data-value="no" aria-label="Genta tidak" aria-pressed="false" onclick="setManualCheck(this)">×</button>
                </div>
            </div>

            <div class="min-w-0">
                <label for="manualPintu" class="mb-1.5 block text-xs font-semibold text-slate-700">PINTU PERLINTASAN</label>
                <div class="mini-check flex items-center gap-1">
                    <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-200" data-group="manualPintu" data-value="yes" aria-label="Pintu perlintasan ya" aria-pressed="false" onclick="setManualCheck(this)">✓</button>
                    <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" data-group="manualPintu" data-value="no" aria-label="Pintu perlintasan tidak" aria-pressed="false" onclick="setManualCheck(this)">×</button>
                </div>
            </div>

            <div class="min-w-0">
                <label for="manualSemboyan35" class="mb-1.5 block text-xs font-semibold text-slate-700">SEMBOYAN 35</label>
                <div class="mini-check flex items-center gap-1">
                    <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-200" data-group="manual35" data-value="yes" aria-label="Semboyan 35 ya" aria-pressed="false" onclick="setManualCheck(this)">✓</button>
                    <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" data-group="manual35" data-value="no" aria-label="Semboyan 35 tidak" aria-pressed="false" onclick="setManualCheck(this)">×</button>
                </div>
            </div>

            <div class="min-w-0">
                <label for="manualKeterangan" class="mb-1.5 block text-xs font-semibold text-slate-700">KETERANGAN</label>
                <input id="manualKeterangan" type="text"
                    class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                    placeholder="Keterangan">
            </div>

            <div class="min-w-0 flex flex-row items-center gap-2">
                <button
                    type="button"
                    class="add-button mt-0 inline-flex min-h-9 items-center justify-center rounded-md border border-[#1d2d50] bg-[#1d2d50] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#263e6a] focus:outline-none focus:ring-2 focus:ring-[#55769a]/30"
                    onclick="tambahKAManual()">
                    + Simpan KA Manual
                </button>

                <button
                    type="button"
                    class="action-btn btn-outline mt-0 inline-flex min-h-9 items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300"
                    onclick="toggleFormTambahKA(false)">
                    Batal
                </button>
            </div>
        </div>
    </div>
</section>
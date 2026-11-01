{{-- RINGKASAN REALISASI --}}
<div
    class="mb-4 flex w-full min-w-0 flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:p-4"
    role="group"
    aria-label="Ringkasan realisasi shift"
    title="Ringkasan dihitung dari seluruh KA dalam satu shift, termasuk KA manual.">

    <div class="summary-chips flex flex-wrap items-center gap-x-5 gap-y-2">
        <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
            <span class="chip-label text-xs font-semibold text-slate-500">Total</span>
            <span id="totalKA" class="chip-number">0</span>
        </div>

        <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
            <span class="chip-label text-xs font-semibold text-slate-500">KA Manual</span>
            <span id="manualKA" class="chip-number text-sm font-extrabold text-amber-600">0</span>
        </div>

        <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
            <span class="chip-dot dot-green h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
            <span class="chip-label text-xs font-semibold text-slate-500">Jalan</span>
            <span id="jalanKA" class="chip-number green text-sm font-extrabold text-emerald-600">0</span>
        </div>

        <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
            <span class="chip-dot dot-yellow h-2 w-2 shrink-0 rounded-full bg-amber-400"></span>
            <span class="chip-label text-xs font-semibold text-slate-500">Terlambat</span>
            <span id="telatKA" class="chip-number yellow text-sm font-extrabold text-amber-600">0</span>
        </div>

        <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
            <span class="chip-dot dot-blue h-2 w-2 shrink-0 rounded-full bg-sky-300"></span>
            <span class="chip-label text-xs font-semibold text-slate-500">Lebih cepat</span>
            <span id="cepatKA" class="chip-number blue text-sm font-extrabold text-sky-600">0</span>
        </div>

        <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
            <span class="chip-dot dot-red h-2 w-2 shrink-0 rounded-full bg-red-500"></span>
            <span class="chip-label text-xs font-semibold text-slate-500">Batal</span>
            <span id="batalKA" class="chip-number red text-sm font-extrabold text-red-500">0</span>
        </div>

        <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
            <span class="chip-dot dot-gray h-2 w-2 shrink-0 rounded-full bg-slate-400"></span>
            <span class="chip-label text-xs font-semibold text-slate-500">Belum diproses</span>
            <span id="belumKA" class="chip-number">0</span>
        </div>
    </div>

    <div class="summary-progress flex w-full min-w-0 items-center gap-2 pt-1">
        <div class="flex h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-200">
            <div id="progressGreen" class="progress-green h-full bg-emerald-500" style="width: 0%"></div>
            <div id="progressYellow" class="progress-yellow h-full bg-amber-400" style="width: 0%"></div>
            <div id="progressBlue" class="progress-blue h-full bg-sky-300" style="width: 0%"></div>
            <div id="progressRed" class="progress-red h-full bg-red-500" style="width: 0%"></div>
            <div id="progressGray" class="progress-gray h-full bg-slate-400" style="width: 100%"></div>
        </div>

        <span class="progress-label shrink-0 text-[11px] font-semibold text-slate-500">Progres</span>
        <span id="progressText" class="progress-text min-w-8 shrink-0 text-right text-xs font-extrabold text-[#1d2d50]">0%</span>
    </div>
</div>
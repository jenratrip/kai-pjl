{{-- AKSI REALISASI --}}
<div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="text-xs text-slate-600">
        Formulir realisasi digital untuk pencatatan dinas PJL.

        <div
            id="saveStatus"
            class="save-status idle mt-2"
            role="status"
            aria-live="polite">
            <span class="save-dot"></span>
            <span id="saveStatusText">Perubahan belum disimpan</span>
        </div>
    </div>

    <div class="actions flex flex-col items-stretch gap-2 sm:flex-row sm:flex-wrap sm:items-center">
        <button
            type="button"
            class="action-btn btn-outline inline-flex min-h-10 items-center justify-center rounded-md border border-[#1d2d50] bg-white px-4 py-2 text-xs font-semibold text-[#1d2d50] transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#55769a]/30"
            onclick="cetakDokumen()">
            ▣ &nbsp; Cetak Dokumen JPL
        </button>

        <button
            type="button"
            id="simpanBtn"
            class="action-btn btn-dark inline-flex min-h-10 items-center justify-center rounded-md border border-[#1d2d50] bg-[#1d2d50] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#263e6a] focus:outline-none focus:ring-2 focus:ring-[#55769a]/40 disabled:cursor-not-allowed disabled:opacity-60"
            onclick="simpanRealisasi()">
            ▣ &nbsp; Simpan Semua
        </button>

        <a
            id="selesaiLink"
            href="{{ url('/user/serah-terima-dinas') }}"
            class="action-btn btn-orange inline-flex min-h-10 items-center justify-center rounded-md border border-amber-600 bg-amber-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400/40">
            Selesai &amp; Generate Laporan Serah Terima
        </a>
    </div>
</div>
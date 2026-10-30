{{-- DAFTAR KA --}}
<div class="mb-4 border-b border-slate-200 pb-3 text-base font-bold text-[#1d2d50] sm:text-lg">
    Daftar KA Terjadwal
</div>

{{-- JAM SEKARANG DAN RENTANG FILTER --}}
<div class="mb-4 flex flex-col gap-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
    <span>
        Filter jadwal: <strong>60 menit ke depan</strong>.
        KA manual tetap ditampilkan.
    </span>

    <div class="waktu-info">
        <span>
            Jam sekarang:
            <strong id="jamSekarang">--:--:--</strong> WIB
        </span>

        <span id="windowRange">Rentang jadwal --:--–--:-- WIB</span>
    </div>
</div>
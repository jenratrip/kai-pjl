{{-- TABEL REALISASI KA --}}
<div class="table-wrapper w-full min-w-0 max-w-full overflow-hidden rounded-xl border border-slate-200">
    <table class="w-full table-fixed border-collapse text-center text-xs">
        <colgroup>
            {{-- NOMOR KA --}}
            <col style="width: 8%">

            {{-- NAMA KA --}}
            <col style="width: 8%">

            {{-- RELASI --}}
            <col style="width: 8%">

            {{-- JAM JADWAL --}}
            <col style="width: 7%">

            {{-- JAM AKTUAL --}}
            <col style="width: 8%">

            {{-- STATUS --}}
            <col style="width: 10%">

            {{-- SEMBOYAN 21 --}}
            <col style="width: 7%">

            {{-- GENTA --}}
            <col style="width: 6%">

            {{-- PINTU PERLINTASAN --}}
            <col style="width: 10%">

            {{-- SEMBOYAN 35 --}}
            <col style="width: 7%">

            {{-- KETERANGAN --}}
            <col style="width: 11%">

            {{-- AKSI --}}
            <col style="width: 10%">
        </colgroup>

        <thead class="bg-[#1d2d50] text-white">
            <tr>
                <th class="break-words px-1 py-3 font-semibold">
                    NO KA
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    NAMA KA
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    RELASI
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    JAM JADWAL
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    JAM AKTUAL
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    STATUS
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    S. 21
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    GENTA
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    PINTU PERLINTASAN
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    S. 35
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    KETERANGAN
                </th>

                <th class="break-words px-1 py-3 font-semibold">
                    AKSI
                </th>
            </tr>
        </thead>

        <tbody
            id="kaTableBody"
            class="text-slate-700"
        ></tbody>
    </table>
</div>

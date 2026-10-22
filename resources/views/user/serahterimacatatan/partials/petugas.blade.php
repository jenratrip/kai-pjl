<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white">
            7
        </span>

        <h2 class="font-bold text-[#1d2d50]">
            Petugas yang berdinas
        </h2>
    </div>

    @php
        $namaPenerimaLama = old(
            'nama_penerima',
            data_get($serahTerima, 'ringkasan.nama_penerima', '')
        );
        $nipPenerimaLama = old(
            'nip_penerima',
            data_get($serahTerima, 'ringkasan.nip_penerima', '')
        );
    @endphp

    {{-- Penanda data yang sedang diedit dari riwayat --}}
    @if (!empty($modeEdit) && $serahTerima)
        <input type="hidden"
               name="serah_terima_id"
               value="{{ $serahTerima->id }}"
               form="serahTerimaSubmitForm">
    @endif

    <div class="grid gap-3 md:grid-cols-2">
        {{-- Petugas penyerah --}}
        <div class="rounded-lg bg-slate-50 p-4">
            <p class="text-xs text-slate-500">
                Penyerah / petugas yang mengakhiri dinas
            </p>

            <p class="mt-1 font-bold text-slate-800">
                {{ auth()->user()->name }}
            </p>

            <p class="mt-1 text-sm text-slate-500">
                NIP: {{ auth()->user()->nip ?: '-' }}
            </p>
        </div>

        {{-- Penerima: langsung diketik --}}
        <div class="rounded-lg bg-slate-50 p-4">
            <label for="nama_penerima"
                   class="block text-xs font-semibold text-slate-500">
                Nama penerima serah terima
            </label>

            <input id="nama_penerima"
                   type="text"
                   name="nama_penerima"
                   form="serahTerimaSubmitForm"
                   value="{{ $namaPenerimaLama }}"
                   maxlength="255"
                   required
                   autocomplete="off"
                   placeholder="Ketik nama penerima"
                   class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">

            <label for="nip_penerima"
                   class="mt-3 block text-xs font-semibold text-slate-500">
                NIPP/NIPKWT
            </label>

            <input id="nip_penerima"
                   type="text"
                   name="nip_penerima"
                   form="serahTerimaSubmitForm"
                   value="{{ $nipPenerimaLama }}"
                   maxlength="50"
                   autocomplete="off"
                   placeholder="Ketik NIP penerima"
                   class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
        </div>
    </div>
</section>
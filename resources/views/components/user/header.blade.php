{{-- =========================================================
     DATA HEADER PJL
========================================================= --}}

@php
    /*
    |--------------------------------------------------------------------------
    | DATA DINAS AKTIF
    |--------------------------------------------------------------------------
    */

    $dinasHeader = $dinasAktif ?? null;

    /*
    |--------------------------------------------------------------------------
    | IDENTITAS PETUGAS
    |--------------------------------------------------------------------------
    */

    $petugasDinas = $dinasHeader?->user ?? auth()->user();

    $isPengganti = (bool) ($dinasHeader?->is_pengganti ?? false);

    $namaPetugas = $petugasDinas?->name ?? 'PJL';

    $namaPetugasReguler = $isPengganti
        ? ($dinasHeader?->userDigantikan?->name ?? 'Data tidak tersedia')
        : null;

    /*
    |--------------------------------------------------------------------------
    | IDENTITAS JPL
    |--------------------------------------------------------------------------
    */

    $jpl = $dinasHeader?->jpl;

    $kodeJpl = trim((string) ($jpl?->kode_jpl ?? ''));

    $namaJpl = $jpl?->nama_jpl
        ?? ($lokasiDinas ?? null)
        ?? 'JPL belum ditentukan';

    if ($jpl && $kodeJpl !== '') {
        // Menghilangkan awalan PJL jika sudah terdapat pada kode.
        $nomorJpl = preg_replace('/^PJL\s*/i', '', $kodeJpl);

        $namaJpl = 'PJL ' . $nomorJpl . ' - ' . $namaJpl;
    }

    /*
    |--------------------------------------------------------------------------
    | IDENTITAS SHIFT
    |--------------------------------------------------------------------------
    */

    $namaShiftHeader = $namaShift
        ?? ($dinasHeader?->shift?->nama_shift ?? 'Shift belum ditentukan');

    $jamShiftHeader = $jamDinas
        ?? (
            $dinasHeader?->shift
                ? substr((string) $dinasHeader->shift->jam_mulai, 0, 5)
                    . '–'
                    . substr((string) $dinasHeader->shift->jam_selesai, 0, 5)
                : null
        );
@endphp


{{-- =========================================================
     HEADER PJL
========================================================= --}}

<header
    class="user-header fixed inset-x-0 top-0 z-30 flex h-[76px] items-center justify-between border-b border-slate-200 bg-white px-4 transition-[margin,width] duration-300 ease-in-out sm:px-6"
    :class="sidebarOpen
        ? 'ml-[230px] w-[calc(100%-230px)]'
        : 'ml-0 w-full'"
>

    {{-- =====================================================
         BAGIAN KIRI: HAMBURGER DAN IDENTITAS SISTEM
    ====================================================== --}}

    <div class="flex min-w-0 shrink-0 items-center gap-3">

        {{-- TOMBOL HAMBURGER --}}
        <button
            type="button"
            @click="toggleSidebar()"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-600 transition-colors duration-200 hover:bg-slate-100 hover:text-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/20"
            aria-label="Buka atau tutup sidebar"
            aria-controls="sidebar"
            :aria-expanded="sidebarOpen.toString()"
        >
            <svg
                x-show="!sidebarOpen"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>

            <svg
                x-show="sidebarOpen"
                x-cloak
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>


        {{-- LOGO KAI DAN IDENTITAS SISTEM --}}
        <a
            href="{{ route('user.realisasi-ka') }}"
            class="flex min-w-0 items-center gap-2.5"
        >
            <img
                src="{{ asset('images/logo-kai.jpg') }}"
                alt="Logo KAI"
                class="h-9 w-9 shrink-0 rounded-md object-contain"
            >

            <div class="min-w-0">
                <div class="truncate text-sm font-bold text-[#1d2d50]">
                    Sistem PJL
                </div>

                <div class="hidden text-[10px] font-medium tracking-wide text-slate-500 sm:block">
                    PT. KERETA API INDONESIA
                </div>
            </div>
        </a>

    </div>


    {{-- =====================================================
         BAGIAN KANAN: IDENTITAS DINAS
    ====================================================== --}}

    <div class="ml-3 min-w-0 text-right">

        {{-- NOMOR DAN LOKASI PJL --}}
        <div class="truncate text-sm font-bold text-[#1d2d50]">
            {{ $namaJpl }}
        </div>


        {{-- NAMA PETUGAS DAN STATUS DINAS --}}
        <div class="flex items-center justify-end gap-1.5">

            <span class="max-w-[150px] truncate text-xs font-medium text-slate-700">
                {{ $namaPetugas }}
            </span>

            @if ($dinasHeader)
                <span
                    class="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-bold
                    {{ $isPengganti
                        ? 'bg-amber-50 text-amber-700'
                        : 'bg-emerald-50 text-emerald-700' }}"
                >
                    {{ $isPengganti ? 'WAKIL' : 'REGULER' }}
                </span>
            @endif

        </div>


        {{-- PETUGAS REGULER YANG DIGANTIKAN --}}
        @if ($isPengganti)
            <div class="truncate text-[10px] text-amber-700">
                Menggantikan: {{ $namaPetugasReguler }}
            </div>
        @endif


        {{-- INFORMASI SHIFT --}}
        <div class="truncate text-[10px] text-slate-500">
            {{ $namaShiftHeader }}

            @if ($jamShiftHeader && $jamShiftHeader !== '-')
                · {{ $jamShiftHeader }}
            @endif
        </div>

    </div>

</header>
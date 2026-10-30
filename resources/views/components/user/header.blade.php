<header class="user-header fixed inset-x-0 top-0 z-30 flex h-[76px] items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 transition-[margin,width] duration-300 ease-in-out"
    :class="sidebarOpen ? 'ml-[230px] w-[calc(100%-230px)]' : 'ml-0 w-full'"
>
    <div class="flex min-w-0 items-center gap-3">
        <button
            type="button"
            @click="sidebarOpen = !sidebarOpen"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100"
            aria-label="Buka atau tutup menu navigasi"
            aria-controls="sidebar"
            :aria-expanded="sidebarOpen.toString()"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <a href="{{ route('user.realisasi-ka') }}" class="flex min-w-0 items-center gap-3">
            <img
                src="{{ asset('images/logo-kai.jpg') }}"
                alt="Logo KAI"
                class="h-10 w-10 shrink-0 rounded-md object-contain"
            >

            <div class="min-w-0">
                <div class="truncate text-sm font-bold text-[#1d2d50] sm:text-base">
                    Sistem PJL
                </div>
                <div class="hidden truncate text-[10px] font-medium tracking-wide text-slate-500 sm:block">
                    PT. KERETA API INDONESIA
                </div>
            </div>
        </a>
    </div>

    <div class="ml-3 flex min-w-0 items-center justify-end">
        <div class="min-w-0 text-right">
            <div class="truncate text-sm font-semibold text-slate-800">
                {{ $lokasiDinas ?? ($dinasAktif->jpl->nama_jpl ?? 'PJL') }}
            </div>

            <div class="mt-1 truncate text-xs text-slate-500">
                @if (!empty($namaShift) && $namaShift !== '-')
                    {{ $namaShift }}
                    @if (!empty($jamDinas) && $jamDinas !== '-')
                        ({{ $jamDinas }})
                    @endif
                @elseif (isset($dinasAktif) && $dinasAktif)
                    {{ $dinasAktif->shift->nama_shift ?? 'Shift belum ditentukan' }}
                @else
                    Shift belum ditentukan
                @endif
            </div>
        </div>
    </div>
</header>
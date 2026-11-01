<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[230px] flex-col border-r border-slate-200 bg-white transition-transform duration-300 ease-in-out will-change-transform"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    id="sidebar"
    aria-label="Menu navigasi PJL"
>
    {{-- =====================================================
         HEADER SIDEBAR / LOGO
    ====================================================== --}}
    <div class="flex h-[76px] shrink-0 items-center border-b border-slate-200 px-4">

        <a
            href="{{ route('user.realisasi-ka') }}"
            class="flex min-w-0 items-center gap-3"
        >
            <img
                src="{{ asset('images/logo-kai.jpg') }}"
                alt="Logo KAI"
                class="h-10 w-10 shrink-0 rounded-lg object-contain"
            >

            <div class="min-w-0">
                <h1 class="truncate text-sm font-bold tracking-wide text-[#1d2d50]">
                    Sistem PJL
                </h1>

                <p class="mt-1 text-[10px] text-slate-500">
                    User Panel
                </p>
            </div>
        </a>

    </div>


    {{-- =====================================================
         MENU NAVIGASI
    ====================================================== --}}
    <nav
        class="flex-1 space-y-1 overflow-y-auto px-3 py-5"
        aria-label="Navigasi utama"
    >

        {{-- REALISASI KA --}}
        <a
            href="{{ route('user.realisasi-ka') }}"
            @class([
                'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-colors duration-200',
                'bg-blue-50 text-blue-800' => request()->routeIs('user.realisasi-ka'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('user.realisasi-ka'),
            ])
            @if(request()->routeIs('user.realisasi-ka'))
                aria-current="page"
            @endif
        >
            <svg
                class="h-5 w-5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="1.8"
            >
                <rect x="3" y="4" width="18" height="16" rx="2"/>
                <path d="M3 9h18M8 4v5m8-5v5M7 13h3m4 0h3M7 17h3"/>
            </svg>

            <span>Realisasi KA</span>
        </a>


        {{-- SERAH TERIMA DINAS --}}
        <a
            href="{{ route('user.serah-terima-dinas') }}"
            @class([
                'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-colors duration-200',
                'bg-blue-50 text-blue-800' => request()->routeIs('user.serah-terima-dinas'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('user.serah-terima-dinas'),
            ])
            @if(request()->routeIs('user.serah-terima-dinas'))
                aria-current="page"
            @endif
        >
            <svg
                class="h-5 w-5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="1.8"
            >
                <path d="M16 3h5v5"/>
                <path d="M21 3l-7 7"/>
                <path d="M8 21H3v-5"/>
                <path d="M3 21l7-7"/>
                <path d="M14 14l-4-4"/>
            </svg>

            <span>Serah Terima Dinas</span>
        </a>


        {{-- RIWAYAT SERAH TERIMA --}}
        <a
            href="{{ route('user.serah-terima-dinas.riwayat') }}"
            @class([
                'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-colors duration-200',
                'bg-blue-50 text-blue-800' => request()->routeIs('user.serah-terima-dinas.riwayat'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('user.serah-terima-dinas.riwayat'),
            ])
            @if(request()->routeIs('user.serah-terima-dinas.riwayat'))
                aria-current="page"
            @endif
        >
            <svg
                class="h-5 w-5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="1.8"
            >
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3 2"/>
                <path d="M3 4v4h4"/>
            </svg>

            <span>Riwayat Serah Terima</span>
        </a>


        {{-- CATATAN DINAS --}}
        <a
            href="{{ url('/user/catatan-dinas') }}"
            @class([
                'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-colors duration-200',
                'bg-blue-50 text-blue-800' => request()->is('user/catatan-dinas*'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->is('user/catatan-dinas*'),
            ])
            @if(request()->is('user/catatan-dinas*'))
                aria-current="page"
            @endif
        >
            <svg
                class="h-5 w-5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="1.8"
            >
                <path d="M7 3h10l4 4v14H3V3h4z"/>
                <path d="M14 3v5h5M7 12h10M7 16h10"/>
            </svg>

            <span>Catatan Dinas</span>
        </a>

    </nav>


    {{-- =====================================================
         LOGOUT
    ====================================================== --}}
    <div class="shrink-0 border-t border-slate-200 p-4">

        <form action="{{ route('logout') }}" method="POST">
            @csrf

            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-red-600 transition-colors duration-200 hover:bg-red-50"
            >
                <svg
                    class="h-5 w-5 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M10 17l5-5-5-5"/>
                    <path d="M15 12H3"/>
                    <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>
                </svg>

                <span>Keluar</span>
            </button>
        </form>

    </div>

</aside>
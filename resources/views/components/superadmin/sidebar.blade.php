<aside
    id="superadmin-sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] -translate-x-full flex-col bg-[#0b1f3a] text-white shadow-2xl transition-transform duration-200 ease-out lg:w-64 lg:translate-x-0 lg:shadow-none"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    aria-label="Navigasi Superadmin"
>

    {{-- Logo --}}
    <a href="{{ route('superadmin.dashboard') }}" class="flex h-20 shrink-0 items-center border-b border-white/10 px-6 transition hover:bg-white/5">
        <div class="flex items-center gap-3">

            <div
                class="flex h-11 w-11 items-center justify-center rounded-lg bg-white font-bold text-[#0b1f3a]"
            >
                KAI
            </div>

            <div>
                <h1 class="text-lg font-bold leading-tight">
                    KAI
                </h1>

                <p class="text-xs text-white/60">
                    Panel Pengawasan
                </p>
            </div>

        </div>
    </a>


    {{-- Navigation --}}
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5" x-on:click="if ($event.target.closest('a')) sidebarOpen = false">

        <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/40">Pengawasan</p>

        {{-- =========================================================
        | Dashboard
        ========================================================== --}}

        <a
            href="{{ route('superadmin.dashboard') }}"
            class="
                flex items-center gap-3 rounded-xl px-3 py-2.5
                text-sm font-medium transition
                {{ request()->routeIs('superadmin.dashboard')
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }}
            "
        >

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 12l9-9 9 9M5 10v10h14V10M9 20v-6h6v6"
                />
            </svg>

            <span>
                Dashboard
            </span>

        </a>


        <p class="mt-5 px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/40">Administrasi pengguna</p>

        {{-- =========================================================
        | Manajemen User
        ========================================================== --}}

        <a
            href="{{ route('superadmin.users.index') }}"
            class="
                flex items-center gap-3 rounded-xl px-3 py-2.5
                text-sm font-medium transition
                {{ request()->routeIs('superadmin.users.*')
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }}
            "
        >

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"
                />

                <circle
                    cx="9"
                    cy="7"
                    r="4"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"
                />
            </svg>

            <span>
                Manajemen User
            </span>

        </a>


        <p class="mt-5 px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/40">Pemantauan operasional</p>

        {{-- =========================================================
        | Master Data JPL
        ========================================================== --}}

        <a
            href="{{ route('superadmin.jpl.index') }}"
            class="
                flex items-center gap-3 rounded-xl px-3 py-2.5
                text-sm font-medium transition
                {{ request()->routeIs('superadmin.jpl.*')
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }}
            "
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 3v18M5 8l7-5 7 5M5 16l7 5 7-5"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 8h14M5 16h14"
                />
            </svg>

            <span>
                Master Data JPL
            </span>
        </a>

        {{-- =========================================================
        | Inventaris
        ========================================================== --}}

        <a
            href="{{ route('superadmin.inventaris.index') }}"
            class="
                flex items-center gap-3 rounded-xl px-3 py-2.5
                text-sm font-medium transition
                {{ request()->routeIs('superadmin.inventaris.*')
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }}
            "
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 7.5L12 3l9 4.5v9L12 21l-9-4.5v-9zM3 7.5l9 4.5m0 9v-9m9-4.5l-9 4.5"
                />
            </svg>

            <span>
                Inventaris
            </span>
        </a>

        {{-- =========================================================
        | Jadwal KA
        ========================================================== --}}

        <a
            href="{{ route('superadmin.jadwal-ka.index') }}"
            class="
                flex items-center gap-3 rounded-xl px-3 py-2.5
                text-sm font-medium transition
                {{ request()->routeIs('superadmin.jadwal-ka.*')
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }}
            "
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 15h3m2 0h3" />
            </svg>
            <span>Jadwal KA</span>
        </a>

        {{-- =========================================================
        | Dinasan
        ========================================================== --}}

        <a
            href="{{ route('superadmin.dinas.index') }}"
            class="
                flex items-center gap-3 rounded-xl px-3 py-2.5
                text-sm font-medium transition
                {{ request()->routeIs('superadmin.dinas.*')
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }}
            "
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1-1H5a1 1 0 01-1-1V6a1 1 0 011-1z"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 15h3m2 0h3"
                />
            </svg>
            <span>Dinasan</span>
        </a>

        <p class="mt-5 px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/40">Aktivitas dan sistem</p>

        {{-- =========================================================
        | Activity Log
        ========================================================== --}}

        <a
            href="{{ route('superadmin.activities.index') }}"
            class="
                flex items-center gap-3 rounded-lg px-4 py-3
                text-sm font-medium transition
                {{ request()->routeIs('superadmin.activities.*')
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }}
            "
        >

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 8v4l3 2"
                />

                <circle
                    cx="12"
                    cy="12"
                    r="9"
                />
            </svg>

            <span>
                Activity Log
            </span>

        </a>


        {{-- Monitoring Sistem --}}
        <a
            href="{{ route('superadmin.monitoring.index') }}"
            class="flex items-center gap-3 rounded-lg px-4 py-3
                text-sm font-medium transition
                {{ request()->routeIs('superadmin.monitoring.*')
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }}"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 3v18h18M7 14l4-4 4 4 5-7"
                />
            </svg>

            <span>Monitoring Sistem</span>
        </a>


        <p class="mt-5 px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/40">Akun</p>

        {{-- =========================================================
        | Pengaturan
        ========================================================== --}}

        <a
            href="{{ route('superadmin.settings.index') }}"
            class="
                flex items-center gap-3 rounded-lg px-4 py-3
                text-sm font-medium transition
                {{ request()->routeIs('superadmin.settings.*')
                    ? 'bg-white/10 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }}
            "
        >

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19.4 15a1.7 1.7 0 00.34 1.88l.06.06-1.8 1.8-.06-.06a1.7 1.7 0 00-1.88-.34 1.7 1.7 0 00-1.03 1.56V22h-2.54v-.1a1.7 1.7 0 00-1.03-1.56 1.7 1.7 0 00-1.88.34l-.06.06-1.8-1.8.06-.06A1.7 1.7 0 008.2 15a1.7 1.7 0 00-1.56-1.03H6.5v-2.54h.14A1.7 1.7 0 008.2 10.4a1.7 1.7 0 00-.34-1.88L7.8 8.46l1.8-1.8.06.06a1.7 1.7 0 001.88.34 1.7 1.7 0 001.03-1.56V5h2.54v.5a1.7 1.7 0 001.03 1.56 1.7 1.7 0 001.88-.34l.06-.06 1.8 1.8-.06.06a1.7 1.7 0 001.56 1.03H22v2.54h-.5A1.7 1.7 0 0019.4 15z"
                />
            </svg>

            <span>
                Pengaturan
            </span>

        </a>

    </nav>


    {{-- User Profile --}}
    <x-superadmin.user-profile />

</aside>

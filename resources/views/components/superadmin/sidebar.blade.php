<aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-[#0b1f3a] text-white">

    {{-- Logo --}}
    <div class="flex h-20 items-center border-b border-white/10 px-6">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white font-bold text-[#0b1f3a]">
                KAI
            </div>

            <div>
                <h1 class="text-lg font-bold leading-tight">
                    KAI
                </h1>

                <p class="text-xs text-white/60">
                    Sistem Operasional PJL
                </p>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-1 px-4 py-6">

        {{-- Dashboard --}}
        <a
            href="{{ route('superadmin.dashboard') }}"
            class="flex items-center gap-3 rounded-lg bg-white/10 px-4 py-3 text-sm font-medium transition hover:bg-white/15"
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

            <span>Dashboard</span>
        </a>

        {{-- Manajemen User --}}
        <a
            href="#"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-white/70 transition hover:bg-white/10 hover:text-white"
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
                <circle cx="9" cy="7" r="4" />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"
                />
            </svg>

            <span>Manajemen User</span>
        </a>

        {{-- Activity Log --}}
        <a
            href="#"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-white/70 transition hover:bg-white/10 hover:text-white"
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
                <circle cx="12" cy="12" r="9" />
            </svg>

            <span>Activity Log</span>
        </a>

        {{-- Pengaturan --}}
        <a
            href="#"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-white/70 transition hover:bg-white/10 hover:text-white"
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

            <span>Pengaturan</span>
        </a>

    </nav>

    {{-- User Profile --}}
    <x-superadmin.user-profile />

</aside>
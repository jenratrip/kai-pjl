<header class="sticky top-0 z-20 flex min-h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 shadow-sm backdrop-blur sm:px-6 xl:px-8">

    {{-- Left --}}
    <div class="flex min-w-0 items-center gap-3">
        <button
            type="button"
            x-on:click="sidebarOpen = true"
            aria-controls="superadmin-sidebar"
            x-bind:aria-expanded="sidebarOpen.toString()"
            aria-label="Buka navigasi"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 lg:hidden"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900 sm:text-base">KAI-PJL</p>
            <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                Area pengawasan Superadmin
            </p>
        </div>
    </div>

    {{-- Right --}}
    <div class="flex items-center gap-3">

        {{-- User --}}
        <div class="flex items-center gap-3">

            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0b1f3a] text-sm font-semibold text-white ring-4 ring-blue-50">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <div class="hidden text-left sm:block">
                <p class="text-sm font-semibold text-gray-800">
                    {{ auth()->user()->name }}
                </p>

                <p class="text-xs font-medium text-slate-500">
                    Superadmin
                </p>
            </div>

        </div>

    </div>

</header>

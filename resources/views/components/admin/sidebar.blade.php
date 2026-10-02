<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[230px] -translate-x-full flex-col bg-[#0b1f3a] text-white transition-transform duration-300 lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>

    {{-- =====================================================
        LOGO
    ====================================================== --}}
    <div class="flex h-[62px] shrink-0 items-center border-b border-white/10 px-5">

        <div class="flex items-center gap-3">

            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10">

                <span class="text-xs font-bold text-[#f5a400]">
                    KAI
                </span>

            </div>

            <div>

                <p class="text-sm font-bold leading-tight">
                    Sistem PJL
                </p>

                <p class="text-[10px] text-blue-200">
                    Admin Panel
                </p>

            </div>

        </div>


        {{-- Close Mobile --}}
        <button
            type="button"
            @click="sidebarOpen = false"
            class="ml-auto flex h-8 w-8 items-center justify-center rounded-lg text-white/70 hover:bg-white/10 hover:text-white lg:hidden"
        >

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6 18 18 6M6 6l12 12"
                />
            </svg>

        </button>

    </div>


    {{-- =====================================================
        NAVIGATION
    ====================================================== --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">

        {{-- Dashboard --}}
        <a
            href="{{ route('admin.dashboard') }}"
            @click="sidebarOpen = false"
            class="mb-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                {{ request()->routeIs('admin.dashboard')
                    ? 'bg-white/10 text-white'
                    : 'text-blue-100 hover:bg-white/5 hover:text-white' }}"
        >

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-10h8V3h-8v8Z"
                />
            </svg>

            Dashboard

        </a>


        {{-- =================================================
            MASTER DATA
        ================================================== --}}
        <div
            x-data="{ open: {{ request()->routeIs('admin.wilayah.*', 'admin.jpl.*') ? 'true' : 'false' }} }"
            class="mt-2"
        >

            <button
                type="button"
                @click="open = !open"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium text-blue-100 transition hover:bg-white/5 hover:text-white"
            >

                <span class="flex items-center gap-3">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>

                    Master Data

                </span>


                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4 transition-transform"
                    :class="open ? 'rotate-180' : ''"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m19 9-7 7-7-7"
                    />
                </svg>

            </button>


            <div
                x-show="open"
                x-collapse
                class="mt-1 space-y-1 pl-11"
            >

                {{-- Wilayah --}}
                <a
                    href="{{ route('admin.wilayah.index') }}"
                    @click="sidebarOpen = false"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.wilayah.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    Wilayah
                </a>


                {{-- JPL --}}
                <a
                    href="{{ route('admin.jpl.index') }}"
                    @click="sidebarOpen = false"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.jpl.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    JPL
                </a>

            </div>

        </div>


        {{-- =================================================
            MANAJEMEN
        ================================================== --}}
        <div
            x-data="{ open: false }"
            class="mt-2"
        >

            <button
                type="button"
                @click="open = !open"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium text-blue-100 transition hover:bg-white/5 hover:text-white"
            >

                <span class="flex items-center gap-3">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 6v12m6-6H6"
                        />
                    </svg>

                    Manajemen

                </span>


                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4 transition-transform"
                    :class="open ? 'rotate-180' : ''"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m19 9-7 7-7-7"
                    />
                </svg>

            </button>


            <div
                x-show="open"
                x-collapse
                class="mt-1 space-y-1 pl-11"
            >

                <a
                    href="#"
                    class="block rounded-lg px-3 py-2 text-sm text-blue-200 transition hover:bg-white/5 hover:text-white"
                >
                    Jadwal KA
                </a>

                <a
                    href="#"
                    class="block rounded-lg px-3 py-2 text-sm text-blue-200 transition hover:bg-white/5 hover:text-white"
                >
                    Inventaris
                </a>

            </div>

        </div>

    </nav>


    {{-- =====================================================
        BOTTOM
    ====================================================== --}}
    <div class="shrink-0 border-t border-white/10 p-3">

        <form
            method="POST"
            action="{{ route('logout') }}"
        >

            @csrf

            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-blue-100 transition hover:bg-red-500/10 hover:text-red-300"
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
                        d="M15 12H3m0 0 4-4m-4 4 4 4M21 3v18"
                    />
                </svg>

                Keluar

            </button>

        </form>

    </div>

</aside>
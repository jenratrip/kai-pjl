<aside
    x-data="{
        masterOpen: false,
        managementOpen: false,

        init() {
            const masterActive = {{ request()->routeIs(
                'admin.wilayah.*',
                'admin.resort.*',
                'admin.jpl.*'
            ) ? 'true' : 'false' }};

            const managementActive = {{ request()->routeIs(
                'admin.dinas.*',
                'admin.shift.*',
                'admin.jadwal-ka.*',
                'admin.inventaris.*',
                'admin.users.*',
                'admin.realisasi-ka-settings.*'
            ) ? 'true' : 'false' }};

            this.masterOpen =
                masterActive ||
                sessionStorage.getItem('admin.masterOpen') === 'true';

            this.managementOpen =
                managementActive ||
                sessionStorage.getItem('admin.managementOpen') === 'true';

            this.$watch('masterOpen', value => {
                sessionStorage.setItem(
                    'admin.masterOpen',
                    String(value)
                );
            });

            this.$watch('managementOpen', value => {
                sessionStorage.setItem(
                    'admin.managementOpen',
                    String(value)
                );
            });
        },

        toggleMaster() {
            this.masterOpen = !this.masterOpen;
        },

        toggleManagement() {
            this.managementOpen = !this.managementOpen;
        },

        closeSidebar() {
            sidebarOpen = false;
        }
    }"
    id="admin-sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-[230px] -translate-x-full flex-col bg-[#0b1f3a] text-white shadow-xl transition-transform duration-300 lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    aria-label="Navigasi Admin"
>

    {{-- =====================================================
        LOGO
    ====================================================== --}}
    <div class="flex h-[62px] shrink-0 items-center border-b border-white/10 px-5">

        <div class="flex items-center gap-3">

            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white p-1">
                <img
                    src="{{ asset('images/logo-kai.jpg') }}"
                    alt="Logo KAI"
                    class="h-full w-full object-contain"
                >
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
            @click="closeSidebar()"
            class="ml-auto flex h-8 w-8 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/10 hover:text-white lg:hidden"
            aria-label="Tutup sidebar"
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
            @click="closeSidebar()"
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
        <div class="mt-2">

            <button
                type="button"
                @click="toggleMaster()"
                :aria-expanded="masterOpen.toString()"
                aria-controls="admin-master-menu"
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
                    class="h-4 w-4 transition-transform duration-200"
                    :class="masterOpen ? 'rotate-180' : ''"
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
                id="admin-master-menu"
                x-show="masterOpen"
                x-collapse
                x-cloak
                class="mt-1 space-y-1 pl-11"
            >

                {{-- Wilayah --}}
                <a
                    href="{{ route('admin.wilayah.index') }}"
                    @click="closeSidebar()"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.wilayah.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    Wilayah
                </a>

                {{-- Resort --}}
                <a
                    href="{{ route('admin.resort.index') }}"
                    @click="closeSidebar()"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.resort.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    Resort
                </a>

                {{-- JPL --}}
                <a
                    href="{{ route('admin.jpl.index') }}"
                    @click="closeSidebar()"
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
        <div class="mt-2">

            <button
                type="button"
                @click="toggleManagement()"
                :aria-expanded="managementOpen.toString()"
                aria-controls="admin-management-menu"
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
                    class="h-4 w-4 transition-transform duration-200"
                    :class="managementOpen ? 'rotate-180' : ''"
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
                id="admin-management-menu"
                x-show="managementOpen"
                x-collapse
                x-cloak
                class="mt-1 space-y-1 pl-11"
            >

                {{-- Dinas --}}
                <a
                    href="{{ route('admin.dinas.index') }}"
                    @click="closeSidebar()"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.dinas.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    Dinas
                </a>

                {{-- Shift --}}
                <a
                    href="{{ route('admin.shift.index') }}"
                    @click="closeSidebar()"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.shift.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    Shift
                </a>

                {{-- Jadwal KA --}}
                <a
                    href="{{ route('admin.jadwal-ka.index') }}"
                    @click="closeSidebar()"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.jadwal-ka.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    Jadwal KA
                </a>

                {{-- Manajemen User --}}
                <a
                    href="{{ route('admin.users.index') }}"
                    @click="closeSidebar()"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.users.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    Manajemen User
                </a>

                {{-- Inventaris --}}
                <a
                    href="{{ route('admin.inventaris.index') }}"
                    @click="closeSidebar()"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.inventaris.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    Inventaris
                </a>

                {{-- Pengaturan Peringatan Realisasi KA --}}
                <a
                    href="{{ route('admin.realisasi-ka-settings.index') }}"
                    @click="closeSidebar()"
                    class="block rounded-lg px-3 py-2 text-sm transition
                        {{ request()->routeIs('admin.realisasi-ka-settings.*')
                            ? 'bg-white/10 font-semibold text-white'
                            : 'text-blue-200 hover:bg-white/5 hover:text-white' }}"
                >
                    Pengaturan Peringatan KA
                </a>

            </div>

        </div>

    </nav>


    {{-- =====================================================
        BOTTOM / LOGOUT
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

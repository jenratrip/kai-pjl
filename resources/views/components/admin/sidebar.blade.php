<aside
    x-data="{
        masterOpen: false,
        managementOpen: false,
        reviewOpen: false,

        init() {
            const masterActive = {{ request()->routeIs(
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

            const reviewActive = {{ request()->routeIs(
                'admin.catatan-dinas.*',
                'admin.serah-terima-dinas.*'
            ) ? 'true' : 'false' }};

            this.masterOpen =
                masterActive ||
                sessionStorage.getItem('admin.masterOpen') === 'true';

            this.managementOpen =
                managementActive ||
                sessionStorage.getItem('admin.managementOpen') === 'true';

            this.reviewOpen =
                reviewActive ||
                sessionStorage.getItem('admin.reviewOpen') === 'true';

            this.$watch('masterOpen', value => {
                sessionStorage.setItem('admin.masterOpen', String(value));
            });

            this.$watch('managementOpen', value => {
                sessionStorage.setItem('admin.managementOpen', String(value));
            });

            this.$watch('reviewOpen', value => {
                sessionStorage.setItem('admin.reviewOpen', String(value));
            });
        },

        toggleMaster() {
            this.masterOpen = !this.masterOpen;
        },

        toggleManagement() {
            this.managementOpen = !this.managementOpen;
        },

        toggleReview() {
            this.reviewOpen = !this.reviewOpen;
        },

        closeSidebar() {
            sidebarOpen = false;
        }
    }"
    id="admin-sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-[230px] -translate-x-full flex-col bg-[#0b1f3a] text-white transition-transform duration-300 lg:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }"
>
    <!-- HEADER / LOGO -->
    <div class="flex h-20 shrink-0 items-center gap-3 border-b border-white/10 px-5">
        <img
            src="{{ asset('images/logo-kai.jpg') }}"
            alt="Logo KAI"
            class="h-11 w-11 rounded-lg bg-white object-contain p-1"
        >

        <div>
            <h1 class="text-sm font-bold tracking-wide">
                Sistem PJL
            </h1>
            <p class="mt-1 text-xs text-gray-400">
                Admin Panel
            </p>
        </div>
    </div>

    <!-- NAVIGATION -->
    <nav class="flex-1 space-y-2 overflow-y-auto px-3 py-5">

        <!-- DASHBOARD -->
        <a
            href="{{ route('admin.dashboard') }}"
            @click="closeSidebar()"
            class="flex items-center gap-3 rounded-xl px-4 py-3 transition
            {{ request()->routeIs('admin.dashboard')
                ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20'
                : 'text-gray-300 hover:bg-white/5 hover:text-white' }}"
        >
            <svg
                class="h-5 w-5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M3 10.5L12 3l9 7.5M5 9v11h14V9M9 20v-7h6v7"
                />
            </svg>

            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <!-- MASTER DATA -->
        <div>
            <button
                type="button"
                @click="toggleMaster()"
                class="flex w-full items-center justify-between rounded-xl px-4 py-3 transition"
                :class="masterOpen
                    ? 'bg-white/5 text-white'
                    : 'text-gray-300 hover:bg-white/5 hover:text-white'"
            >
                <span class="flex items-center gap-3">
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M4 5a2 2 0 012-2h12a2 2 0 012 2v14H4V5zm0 4h16M8 3v6m8-6v6M8 13h3m-3 3h3m3-3h2m-2 3h2"
                        />
                    </svg>

                    <span class="text-sm font-medium">Master Data</span>
                </span>

                <svg
                    class="h-4 w-4 transition-transform duration-200"
                    :class="{ 'rotate-180': masterOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>

            <div
                x-show="masterOpen"
                x-transition
                x-cloak
                class="ml-5 mt-1 space-y-1 border-l border-white/10 pl-4"
            >
                <!-- JPL -->
                <a
                    href="{{ route('admin.jpl.index') }}"
                    @click="closeSidebar()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('admin.jpl.*')
                        ? 'bg-orange-500/10 text-orange-400'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11z"
                        />
                        <circle cx="12" cy="10" r="2.5" />
                    </svg>

                    <span>JPL</span>
                </a>
            </div>
        </div>

        <!-- MANAJEMEN -->
        <div>
            <button
                type="button"
                @click="toggleManagement()"
                class="flex w-full items-center justify-between rounded-xl px-4 py-3 transition"
                :class="managementOpen
                    ? 'bg-white/5 text-white'
                    : 'text-gray-300 hover:bg-white/5 hover:text-white'"
            >
                <span class="flex items-center gap-3">
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M4 5h16M4 12h16M4 19h16M8 3v4m8-4v4M8 10v4m8-4v4M8 17v4m8-4v4"
                        />
                    </svg>

                    <span class="text-sm font-medium">Manajemen</span>
                </span>

                <svg
                    class="h-4 w-4 transition-transform duration-200"
                    :class="{ 'rotate-180': managementOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>

            <div
                x-show="managementOpen"
                x-transition
                x-cloak
                class="ml-5 mt-1 space-y-1 border-l border-white/10 pl-4"
            >
                <!-- DINAS -->
                <a
                    href="{{ route('admin.dinas.index') }}"
                    @click="closeSidebar()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('admin.dinas.*')
                        ? 'bg-orange-500/10 text-orange-400'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M8 3h8l3 3v15H5V3h3zm0 6h8m-8 4h8m-8 4h5"
                        />
                    </svg>

                    <span>Dinas</span>
                </a>

                <!-- SHIFT -->
                <a
                    href="{{ route('admin.shift.index') }}"
                    @click="closeSidebar()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('admin.shift.*')
                        ? 'bg-orange-500/10 text-orange-400'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                >

                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <circle cx="12" cy="12" r="9" stroke-width="1.8" />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M12 7v5l3 2"
                        />
                    </svg>

                    <span>Shift</span>
                </a>

                <!-- JADWAL KA -->
                <a
                    href="{{ route('admin.jadwal-ka.index') }}"
                    @click="closeSidebar()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('admin.jadwal-ka.*')
                        ? 'bg-orange-500/10 text-orange-400'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <rect
                            x="3"
                            y="5"
                            width="18"
                            height="15"
                            rx="2"
                            stroke-width="1.8"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-width="1.8"
                            d="M3 10h18M8 3v4m8-4v4M7 14h3m4 0h3"
                        />
                    </svg>

                    <span>Jadwal KA</span>
                </a>

                <!-- MANAJEMEN USER -->
                <a
                    href="{{ route('admin.users.index') }}"
                    @click="closeSidebar()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('admin.users.*')
                        ? 'bg-orange-500/10 text-orange-400'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8zm10 10v-2a4 4 0 00-3-3.87m-1-11.96a4 4 0 010 7.75"
                        />
                    </svg>

                    <span>Manajemen User</span>
                </a>

                <!-- INVENTARIS -->
                <a
                    href="{{ route('admin.inventaris.index') }}"
                    @click="closeSidebar()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('admin.inventaris.*')
                        ? 'bg-orange-500/10 text-orange-400'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M4 7l8-4 8 4-8 4-8-4zm0 0v10l8 4 8-4V7M12 11v10"
                        />
                    </svg>

                    <span>Inventaris</span>
                </a>

                <!-- PENGATURAN PERINGATAN REALISASI KA -->
                <a
                    href="{{ route('admin.realisasi-ka-settings.index') }}"
                    @click="closeSidebar()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('admin.realisasi-ka-settings.*')
                        ? 'bg-orange-500/10 text-orange-400'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M12 3a6 6 0 00-6 6v3l-2 4h16l-2-4V9a6 6 0 00-6-6zm-2 16a2 2 0 004 0"
                        />
                    </svg>

                    <span>Pengaturan Peringatan KA</span>
                </a>
            </div>
        </div>

        <!-- REVIEW -->
        <div>
            <button
                type="button"
                @click="toggleReview()"
                class="flex w-full items-center justify-between rounded-xl px-4 py-3 transition"
                :class="reviewOpen
                    ? 'bg-white/5 text-white'
                    : 'text-gray-300 hover:bg-white/5 hover:text-white'"
            >
                <span class="flex items-center gap-3">
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 8h6m-6 4h6"
                        />
                    </svg>

                    <span class="text-sm font-medium">Review</span>
                </span>

                <svg
                    class="h-4 w-4 transition-transform duration-200"
                    :class="{ 'rotate-180': reviewOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>

            <div
                x-show="reviewOpen"
                x-transition
                x-cloak
                class="ml-5 mt-1 space-y-1 border-l border-white/10 pl-4"
            >
                <!-- REVIEW CATATAN DINAS -->
                <a
                    href="{{ route('admin.catatan-dinas.index') }}"
                    @click="closeSidebar()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('admin.catatan-dinas.*')
                        ? 'bg-orange-500/10 text-orange-400'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 12l2 2 4-4m5-3v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h3l1-2h4l1 2h3a2 2 0 012 2z"
                        />
                    </svg>

                    <span>Review Catatan Dinas</span>
                </a>

                <!-- REVIEW SERAH TERIMA DINAS -->
                <a
                    href="{{ route('admin.serah-terima-dinas.index') }}"
                    @click="closeSidebar()"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                    {{ request()->routeIs('admin.serah-terima-dinas.*')
                        ? 'bg-orange-500/10 text-orange-400'
                        : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M8 7h12m0 0l-3-3m3 3l-3 3M16 17H4m0 0l3-3m-3 3l3 3"
                        />
                    </svg>

                    <span>Review Serah Terima Dinas</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- LOGOUT -->
    <div class="shrink-0 border-t border-white/10 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-gray-300 transition hover:bg-red-500/10 hover:text-red-400"
            >
                <svg
                    class="h-5 w-5 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 012 2v14a2 2 0 01-2 2h-6"
                    />
                </svg>

                <span class="text-sm font-medium">Keluar</span>
            </button>
        </form>
    </div>
</aside>

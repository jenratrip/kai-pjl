<header class="flex h-20 items-center justify-between border-b border-gray-200 bg-white px-8">

    {{-- Left --}}
    <div>
        <h2 class="text-xl font-semibold text-gray-800">
            @yield('page-title', 'Dashboard')
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            Sistem Operasional PJL
        </p>
    </div>

    {{-- Right --}}
    <div class="flex items-center gap-4">

        {{-- Notification --}}
        <button
            type="button"
            class="relative flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
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
                    d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M10 20h4"
                />
            </svg>

            {{-- Notification indicator --}}
            <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-orange-500"></span>
        </button>

        {{-- Divider --}}
        <div class="h-8 w-px bg-gray-200"></div>

        {{-- User --}}
        <div class="flex items-center gap-3">

            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#0b1f3a] text-sm font-semibold text-white">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <div class="hidden text-left sm:block">
                <p class="text-sm font-semibold text-gray-800">
                    {{ auth()->user()->name }}
                </p>

                <p class="text-xs text-gray-500">
                    Superadmin
                </p>
            </div>

        </div>

    </div>

</header>
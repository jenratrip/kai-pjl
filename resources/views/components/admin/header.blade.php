<header
    class="fixed inset-x-0 top-0 z-30 h-[62px] bg-[#1d2d50] text-white lg:ml-[230px]"
>

    <div class="flex h-full items-center justify-between px-4 sm:px-6">


        {{-- LEFT --}}
        <div class="flex min-w-0 items-center gap-3 sm:gap-4">

            {{-- Mobile Menu --}}
            <button
                type="button"
                @click="sidebarOpen = true"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white hover:bg-white/10 lg:hidden"
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
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>

            </button>


            {{-- KAI --}}
            <div class="flex h-8 w-8 shrink-0 items-center justify-center">

                <span class="text-sm font-bold text-[#f5a400]">
                    KAI
                </span>

            </div>


            {{-- Title --}}
            <div class="hidden border-l border-white/20 pl-4 sm:block">

                <h1 class="text-sm font-bold leading-tight sm:text-base">
                    Sistem PJL - Admin
                </h1>

                <p class="mt-0.5 text-[10px] text-blue-200 sm:text-[11px]">
                    PT. KERETA API INDONESIA
                </p>

            </div>

        </div>


        {{-- RIGHT --}}
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">

            {{-- User Avatar --}}
            <div
                class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-[#1d2d50] sm:h-9 sm:w-9 sm:text-sm"
            >

                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}

            </div>


            {{-- User Info --}}
            <div class="hidden text-right md:block">

                <p class="max-w-[180px] truncate text-sm font-semibold leading-tight">

                    {{ auth()->user()->name }}

                </p>

                <p class="mt-0.5 text-[11px] text-blue-200">

                    NIP. {{ auth()->user()->nip }}

                </p>

            </div>

        </div>

    </div>

</header>
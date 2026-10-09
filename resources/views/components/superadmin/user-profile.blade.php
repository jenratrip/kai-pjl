<div class="border-t border-white/10 p-4">

    {{-- User Information --}}
    <div class="flex items-center gap-3">

        {{-- Avatar --}}
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-semibold">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>

        {{-- Name & Role --}}
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold">
                {{ auth()->user()->name }}
            </p>

            <p class="text-xs text-white/60">
                Superadmin
            </p>
        </div>

    </div>

    {{-- Logout --}}
    <form
        method="POST"
        action="{{ route('logout') }}"
        class="mt-4"
    >
        @csrf

        <button
            type="submit"
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-white/70 transition hover:bg-white/10 hover:text-white"
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
                    d="M15 12H3m0 0l4-4m-4 4l4 4"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 5V3h6v18h-6v-2"
                />
            </svg>

            <span>Logout</span>
        </button>

    </form>

</div>
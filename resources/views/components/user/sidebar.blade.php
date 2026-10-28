<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[230px] -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-300"
    :class="{ 'translate-x-0': sidebarOpen }"
    id="sidebar"
    aria-label="Menu navigasi PJL"
>
    <div class="flex h-[76px] shrink-0 items-center border-b border-slate-200 px-5">
        <a href="{{ route('user.realisasi-ka') }}" class="block">
            <div class="text-sm font-bold tracking-wide text-[#1d2d50]">
                KAI OPERASIONAL
            </div>
            <div class="mt-1 text-[10px] font-medium tracking-[0.12em] text-slate-500">
                SISTEM DIGITAL PJL
            </div>
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Navigasi utama">
        <a
            href="{{ route('user.realisasi-ka') }}"
            @class([
                'flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors',
                'bg-blue-50 text-blue-800' => request()->routeIs('user.realisasi-ka'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('user.realisasi-ka'),
            ])
            @if(request()->routeIs('user.realisasi-ka')) aria-current="page" @endif
            @click="sidebarOpen = false"
        >
            <span class="w-5 text-center text-base" aria-hidden="true">▣</span>
            <span>Realisasi KA</span>
        </a>

        <a
            href="{{ route('user.serah-terima-dinas') }}"
            @class([
                'flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors',
                'bg-blue-50 text-blue-800' =>
                    request()->routeIs('user.serah-terima-dinas')
                    || request()->routeIs('user.serah-terima-dinas.*'),

                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' =>
                    !request()->routeIs('user.serah-terima-dinas')
                    && !request()->routeIs('user.serah-terima-dinas.*'),
            ])
            @if(
                request()->routeIs('user.serah-terima-dinas')
                || request()->routeIs('user.serah-terima-dinas.*')
            )
                aria-current="page"
            @endif
            @click="sidebarOpen = false"
        >
            <span class="w-5 text-center text-base" aria-hidden="true">⇄</span>
            <span>Serah Terima Dinas</span>
        </a>

        <a
            href="{{ route('user.serah-terima-dinas.riwayat') }}"
            @class([
                'flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors',
                'bg-blue-50 text-blue-800' => request()->routeIs('user.serah-terima-dinas.riwayat'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('user.serah-terima-dinas.riwayat'),
            ])
            @if(request()->routeIs('user.serah-terima-dinas.riwayat')) aria-current="page" @endif
            @click="sidebarOpen = false"
        >
            <span class="w-5 text-center text-base" aria-hidden="true">◷</span>
            <span>Riwayat Serah Terima</span>
        </a>

        <a
            href="{{ url('/user/catatan-dinas') }}"
            @class([
                'flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors',
                'bg-blue-50 text-blue-800' => request()->is('user/catatan-dinas*'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->is('user/catatan-dinas*'),
            ])
            @if(request()->is('user/catatan-dinas*')) aria-current="page" @endif
            @click="sidebarOpen = false"
        >
            <span class="w-5 text-center text-base" aria-hidden="true">◧</span>
            <span>Catatan Dinas</span>
        </a>
    </nav>

    <div class="shrink-0 border-t border-slate-200 p-4">
        <div class="mb-3 min-w-0">
            <div class="text-[10px] font-semibold tracking-wider text-slate-400">
                PJL AKTIF
            </div>
            <div class="mt-1 truncate text-sm font-semibold text-slate-800">
                {{ auth()->user()->name ?? 'Pengguna' }}
            </div>
            <div class="mt-1 truncate text-xs text-slate-500">
                NIP: {{ auth()->user()->nip ?? '-' }}
            </div>
        </div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
            >
                <span class="w-5 text-center text-base" aria-hidden="true">↪</span>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>
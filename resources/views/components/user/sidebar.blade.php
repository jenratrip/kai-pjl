<aside
    x-cloak
    id="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex h-screen w-[260px] flex-col border-r border-[#e2e8f0] bg-white shadow-[0_4px_16px_rgba(30,49,86,0.08)] transition-transform duration-300 ease-in-out"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    :aria-hidden="(!sidebarOpen).toString()"
>
    <div class="flex h-[76px] shrink-0 items-center border-b border-[#e2e8f0] px-7">
        <div class="min-w-0">
            <div class="text-sm font-extrabold tracking-[0.5px] text-[#1e3156]">
                KAI OPERASIONAL
            </div>
            <div class="mt-1 text-[10px] font-semibold tracking-[0.8px] text-[#718096]">
                SISTEM DIGITAL PJL
            </div>
        </div>
    </div>

    <nav class="flex min-h-0 flex-1 flex-col gap-[7px] overflow-y-auto px-4 py-5" aria-label="Menu navigasi">
        <a
            @click="closeSidebar()"
            href="{{ url('/user/realisasi-ka') }}"
            @class([
                'flex min-h-[46px] items-center gap-[13px] rounded-[9px] border border-transparent px-[13px] py-[11px] text-[13px] font-semibold transition-colors',
                'border-[#dce6f5] bg-[#edf3fb] font-bold text-[#1e3156]' => request()->routeIs('user.realisasi-ka'),
                'text-[#59677d] hover:bg-[#f5f7fb] hover:text-[#1e3156]' => !request()->routeIs('user.realisasi-ka'),
            ])
            @if(request()->routeIs('user.realisasi-ka')) aria-current="page" @endif
        >
            <span class="inline-flex h-[25px] w-[25px] shrink-0 items-center justify-center text-xl">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M8 2v4M16 2v4M3 10h18"/>
    <rect x="3" y="4" width="18" height="18" rx="2"/>
    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>
</svg>
            </span>
            <span>Jadwal KA</span>
        </a>

        <a
            @click="closeSidebar()"
            href="{{ route('user.coaching-messages.index') }}"
            @class([
                'flex min-h-[46px] items-center gap-[13px] rounded-[9px] border border-transparent px-[13px] py-[11px] text-[13px] font-semibold transition-colors',
                'border-[#dce6f5] bg-[#edf3fb] font-bold text-[#1e3156]' => request()->routeIs('user.coaching-messages.*'),
                'text-[#59677d] hover:bg-[#f5f7fb] hover:text-[#1e3156]' => !request()->routeIs('user.coaching-messages.*'),
            ])
            @if(request()->routeIs('user.coaching-messages.*')) aria-current="page" @endif
        >
            <span class="inline-flex h-[25px] w-[25px] shrink-0 items-center justify-center text-xl">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/>
    <path d="M8 10h8M8 14h5"/>
</svg>
            </span>
            <span class="min-w-0 flex-1">Pesan Pembinaan</span>
            @if (isset($unreadCount) && $unreadCount > 0)
                <span
                    class="shrink-0 ml-auto rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800"
                    data-coaching-unread-count
                    data-count="{{ $unreadCount }}"
                    data-label="baru"
                    aria-label="{{ $unreadCount }} pesan belum dibaca"
                >{{ $unreadCount }} baru</span>
            @endif
        </a>

        <a
            @click="closeSidebar()"
            href="{{ route('user.inventaris.index') }}"
            @class([
                'flex min-h-[46px] items-center gap-[13px] rounded-[9px] border border-transparent px-[13px] py-[11px] text-[13px] font-semibold transition-colors',
                'border-[#dce6f5] bg-[#edf3fb] font-bold text-[#1e3156]' => request()->routeIs('user.inventaris.*'),
                'text-[#59677d] hover:bg-[#f5f7fb] hover:text-[#1e3156]' => !request()->routeIs('user.inventaris.*'),
            ])
            @if(request()->routeIs('user.inventaris.*')) aria-current="page" @endif
        >
            <span class="inline-flex h-[25px] w-[25px] shrink-0 items-center justify-center text-xl">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="m12 3 9 5-9 5-9-5 9-5z"/>
    <path d="m3 8 9 5 9-5M3 16l9 5 9-5M3 8v8M21 8v8M12 13v8"/>
</svg>
            </span>
            <span>Inventaris</span>
        </a>

        <a
            @click="closeSidebar()"
            href="{{ route('user.serah-terima-dinas') }}"
            @class([
                'flex min-h-[46px] items-center gap-[13px] rounded-[9px] border border-transparent px-[13px] py-[11px] text-[13px] font-semibold transition-colors',
                'border-[#dce6f5] bg-[#edf3fb] font-bold text-[#1e3156]' => request()->routeIs('user.serah-terima-dinas'),
                'text-[#59677d] hover:bg-[#f5f7fb] hover:text-[#1e3156]' => !request()->routeIs('user.serah-terima-dinas'),
            ])
        >
            <span class="inline-flex h-[25px] w-[25px] shrink-0 items-center justify-center text-xl">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M17 3l4 4-4 4M3 7h18M7 21l-4-4 4-4M21 17H3"/>
</svg>
            </span>
            <span>Serah Terima Dinas</span>
        </a>

        <a
            @click="closeSidebar()"
            href="{{ url('/user/catatan-dinas') }}"
            @class([
                'flex min-h-[46px] items-center gap-[13px] rounded-[9px] border border-transparent px-[13px] py-[11px] text-[13px] font-semibold transition-colors',
                'border-[#dce6f5] bg-[#edf3fb] font-bold text-[#1e3156]' => request()->routeIs('user.catatan-dinas*'),
                'text-[#59677d] hover:bg-[#f5f7fb] hover:text-[#1e3156]' => !request()->routeIs('user.catatan-dinas*'),
            ])
            @if(request()->routeIs('user.catatan-dinas*')) aria-current="page" @endif
        >
            <span class="inline-flex h-[25px] w-[25px] shrink-0 items-center justify-center text-xl">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <rect x="4" y="3" width="16" height="18" rx="2"/>
    <path d="M9 3.5h6M8 10h8M8 14h8M8 18h5"/>
</svg>
            </span>
            <span>Catatan Dinas</span>
        </a>

        {{-- PROFIL --}}
        <a
            @click="closeSidebar()"
            href="{{ route('user.profile.show') }}"
            @class([
                'flex min-h-[46px] items-center gap-[13px] rounded-[9px] border border-transparent px-[13px] py-[11px] text-[13px] font-semibold transition-colors',
                'border-[#dce6f5] bg-[#edf3fb] font-bold text-[#1e3156]' => request()->routeIs('user.profile.*'),
                'text-[#59677d] hover:bg-[#f5f7fb] hover:text-[#1e3156]' => !request()->routeIs('user.profile.*'),
            ])
            @if(request()->routeIs('user.profile.*')) aria-current="page" @endif
        >
            <span class="inline-flex h-[25px] w-[25px] shrink-0 items-center justify-center text-xl">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="8" r="4"/>
    <path d="M5 21a7 7 0 0 1 14 0"/>
</svg>
            </span>
            <span>Profil</span>
        </a>
    </nav>

    {{-- IDENTITAS PENGGUNA DAN KELUAR --}}
    <div class="shrink-0 border-t border-[#e2e8f0] bg-white px-4 py-4">
        <div class="flex min-w-0 items-center gap-[10px] rounded-[10px] border border-[#e2e8f0] bg-[#f7f9fc] px-[13px] py-[15px]">
            <x-user-operasional.user-avatar
                :user="auth()->user()"
                size="h-10 w-10"
            />

            <div class="min-w-0 flex-1">
                <div class="truncate text-[13px] font-bold text-[#1e3156]">
                    {{ auth()->user()->name ?? 'Pengguna' }}
                </div>
                <div class="mt-1 truncate text-[11px] text-[#718096]">
                    NIP: {{ auth()->user()->nip ?? '-' }}
                </div>
            </div>
        </div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button
                type="submit"
                class="flex min-h-[43px] w-full items-center justify-center gap-[10px] rounded-[9px] border border-[#f4d7da] bg-[#fff5f5] px-[14px] py-[10px] text-[13px] font-bold text-[#b42332] transition-colors hover:border-[#b42332] hover:bg-[#b42332] hover:text-white"
            >
                <span class="inline-flex h-[25px] w-[25px] shrink-0 items-center justify-center text-xl">&#8618;</span>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>

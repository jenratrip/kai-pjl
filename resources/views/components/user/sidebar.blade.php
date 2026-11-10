<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="closeSidebar()"
    aria-hidden="true"
></div>

<aside class="sidebar" id="sidebar">
    <!-- SIDEBAR -->
    <nav
        class="mobile-menu"
        id="mobileMenu"
        aria-label="Menu navigasi"
        aria-hidden="true"
        inert
    >
        <div class="side-brand">
            <div>
                <div class="side-brand-title">KAI OPERASIONAL</div>
                <div class="side-brand-subtitle">SISTEM DIGITAL PJL</div>
            </div>
        </div>

        <a href="{{ url('/user/realisasi-ka') }}">
            <span class="menu-icon">▣</span>
            Jadwal KA
        </a>

        <a
            href="{{ route('user.coaching-messages.index') }}"
            class="{{ request()->routeIs('user.coaching-messages.*') ? 'active' : '' }}"
            @if(request()->routeIs('user.coaching-messages.*')) aria-current="page" @endif
        >
            <span class="menu-icon">✉</span>
            <span class="flex min-w-0 flex-1 items-center justify-between gap-2">
                <span>Pesan Pembinaan</span>
                @if (isset($unreadCount))
                    <span
                        class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800"
                        data-coaching-unread-count
                        data-count="{{ $unreadCount }}"
                        data-label="baru"
                        aria-label="{{ $unreadCount }} pesan belum dibaca"
                        @if ($unreadCount === 0) hidden @endif
                    >{{ $unreadCount }} baru</span>
                @endif
            </span>
        </a>

        <a
            href="{{ route('user.inventaris.index') }}"
            class="{{ request()->routeIs('user.inventaris.*') ? 'active' : '' }}"
            @if(request()->routeIs('user.inventaris.*')) aria-current="page" @endif
        >
            <span class="menu-icon">▣</span>
            Inventaris
        </a>

        <a href="{{ route('user.serah-terima-dinas') }}">
            <span class="menu-icon">⇄</span>
            Serah Terima Dinas
        </a>

        <a
            href="{{ url('/user/catatan-dinas') }}"
            class="{{ request()->routeIs('user.catatan-dinas*') ? 'active' : '' }}"
            @if(request()->routeIs('user.catatan-dinas*')) aria-current="page" @endif
        >
            <span class="menu-icon">◧</span>
            Catatan Dinas
        </a>

        <div class="side-bottom">
            <div class="active-user">
                <div class="active-user-label">PJL AKTIF</div>

                <div class="active-user-name">
                    {{ auth()->user()->name ?? 'Pengguna' }}
                </div>

                <div class="active-user-info">
                    NIP: {{ auth()->user()->nip ?? '-' }}
                </div>
            </div>

            <form
                action="{{ route('logout') }}"
                method="POST"
                class="logout-form"
            >
                @csrf

                <button type="submit" class="logout-btn">
                    <span class="logout-icon">↪</span>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </nav>
</aside>

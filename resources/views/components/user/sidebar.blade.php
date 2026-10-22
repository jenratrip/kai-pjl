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

        <a href="{{ url('/user/dashboard') }}">
            <span class="menu-icon">⌂</span>
            Dashboard
        </a>

        <a href="{{ url('/user/realisasi-ka') }}">
            <span class="menu-icon">▣</span>
            Jadwal KA
        </a>

        <a href="{{ route('user.serah-terima-dinas') }}">
            <span class="menu-icon">⇄</span>
            Serah Terima Dinas
        </a>

        <a
            href="{{ url('/user/catatan-dinas') }}"
            class="active"
            aria-current="page"
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
<div
    class="op-sidebar-overlay"
    id="opSidebarOverlay"
    aria-hidden="true"
></div>

<aside
    class="op-sidebar"
    id="opSidebar"
    aria-hidden="true"
>
    <div class="op-sidebar-brand">
        <div class="op-sidebar-brand-title">MENU OPERASIONAL</div>
        <div class="op-sidebar-brand-subtitle">
            SISTEM PENCATATAN PJL
        </div>
    </div>

    <nav class="op-sidebar-nav" aria-label="Navigasi operasional">
        @php
            $halamanRiwayat = request()->is('user/serah-terima-dinas/riwayat*');

            $menuAktif = [
                'dashboard' => request()->is('user/dashboard*'),
                'realisasi' => request()->is('user/realisasi-ka*'),
                // Menyala di halaman serah terima, kecuali sedang di riwayat.
                'serahTerima' => request()->is('user/serah-terima-dinas*')
                    && !$halamanRiwayat,
                'riwayat' => $halamanRiwayat,
                'catatan' => request()->is('user/catatan-dinas*'),
            ];
        @endphp

        <a
            href="{{ url('/user/dashboard') }}"
            class="op-nav-link {{ $menuAktif['dashboard'] ? 'active' : '' }}"
            @if ($menuAktif['dashboard']) aria-current="page" @endif
        >
            <span class="op-nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>

        <a
            href="{{ url('/user/realisasi-ka') }}"
            class="op-nav-link {{ $menuAktif['realisasi'] ? 'active' : '' }}"
            @if ($menuAktif['realisasi']) aria-current="page" @endif
        >
            <span class="op-nav-icon">▤</span>
            <span>Realisasi KA</span>
        </a>

        <a
            href="{{ route('user.serah-terima-dinas') }}"
            class="op-nav-link {{ $menuAktif['serahTerima'] ? 'active' : '' }}"
            @if ($menuAktif['serahTerima']) aria-current="page" @endif
        >
            <span class="op-nav-icon">⇄</span>
            <span>Serah Terima Dinas</span>
        </a>

        <a
            href="{{ route('user.serah-terima-dinas.riwayat') }}"
            class="op-nav-link {{ $menuAktif['riwayat'] ? 'active' : '' }}"
            @if ($menuAktif['riwayat']) aria-current="page" @endif
        >
            <span class="op-nav-icon">↺</span>
            <span>Riwayat Serah Terima</span>
        </a>

        <a
            href="{{ route('user.catatan-dinas') }}"
            class="op-nav-link {{ $menuAktif['catatan'] ? 'active' : '' }}"
            @if ($menuAktif['catatan']) aria-current="page" @endif
        >
            <span class="op-nav-icon">▧</span>
            <span>Catatan Dinas</span>
        </a>
    </nav>

    <div class="op-sidebar-bottom">
        <div class="op-active-user">
            <div class="op-active-user-label">PETUGAS AKTIF</div>

            <div class="op-active-user-name">
                {{ auth()->user()->name ?? 'Pengguna' }}
            </div>

            <div class="op-active-user-info">
                NIP: {{ auth()->user()->nip ?? '-' }}
            </div>
        </div>

        <form
            action="{{ route('logout') }}"
            method="POST"
            class="op-logout-form"
        >
            @csrf

            <button type="submit" class="op-logout-button">
                <span>⇥</span>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>
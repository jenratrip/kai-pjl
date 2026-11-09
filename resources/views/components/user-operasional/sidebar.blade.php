<div
    class="op-sidebar-overlay"
    id="opSidebarOverlay"
    x-show="sidebarOpen"
    x-transition.opacity
    :class="{ 'is-visible': sidebarOpen }"
    @click="sidebarOpen = false"
    aria-hidden="true"
    :aria-hidden="(!sidebarOpen).toString()"
></div>

<aside
    class="op-sidebar"
    id="opSidebar"
    :class="{ 'is-open': sidebarOpen }"
    :aria-hidden="(!sidebarOpen && window.innerWidth <= 768).toString()"
    :inert="!sidebarOpen"
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
                'realisasi' => request()->is('user/realisasi-ka*'),
                'pesanPembinaan' => request()->routeIs('user.coaching-messages.*'),
                'inventaris' => request()->routeIs('user.inventaris.*'),
                // Menyala di halaman serah terima, kecuali sedang di riwayat.
                'serahTerima' => request()->is('user/serah-terima-dinas*')
                    && !$halamanRiwayat,
                'riwayat' => $halamanRiwayat,
                'catatan' => request()->is('user/catatan-dinas*'),
                'profile' => request()->routeIs('user.profile.*'),
            ];
        @endphp

        <a
            href="{{ url('/user/realisasi-ka') }}"
            class="op-nav-link {{ $menuAktif['realisasi'] ? 'active' : '' }}"
            @click="if (window.innerWidth <= 768) sidebarOpen = false"
            @if ($menuAktif['realisasi']) aria-current="page" @endif
        >
            <span class="op-nav-icon">▤</span>
            <span>Realisasi KA</span>
        </a>

        <a
            href="{{ route('user.coaching-messages.index') }}"
            class="op-nav-link {{ $menuAktif['pesanPembinaan'] ? 'active' : '' }}"
            @click="if (window.innerWidth <= 768) sidebarOpen = false"
            @if ($menuAktif['pesanPembinaan']) aria-current="page" @endif
        >
            <span class="op-nav-icon" aria-hidden="true">✉</span>
            <span>Pesan Pembinaan</span>
            @if (isset($unreadCount) && is_numeric($unreadCount))
                <span
                    class="op-unread-count"
                    data-coaching-unread-count
                    data-count="{{ $unreadCount }}"
                    data-label="baru"
                    aria-label="{{ $unreadCount }} pesan belum dibaca"
                    @if ((int) $unreadCount === 0) hidden @endif
                >{{ $unreadCount }} baru</span>
            @endif
        </a>

        <a
            href="{{ route('user.inventaris.index') }}"
            class="op-nav-link {{ $menuAktif['inventaris'] ? 'active' : '' }}"
            @if ($menuAktif['inventaris']) aria-current="page" @endif
            @click="if (window.innerWidth <= 768) sidebarOpen = false"
        >
            <span class="op-nav-icon">▣</span>
            <span>Inventaris</span>
        </a>

        <a
            href="{{ route('user.serah-terima-dinas') }}"
            class="op-nav-link {{ $menuAktif['serahTerima'] ? 'active' : '' }}"
            @click="if (window.innerWidth <= 768) sidebarOpen = false"
            @if ($menuAktif['serahTerima']) aria-current="page" @endif
        >
            <span class="op-nav-icon">⇄</span>
            <span>Serah Terima Dinas</span>
        </a>

        <a
            href="{{ route('user.serah-terima-dinas.riwayat') }}"
            class="op-nav-link {{ $menuAktif['riwayat'] ? 'active' : '' }}"
            @click="if (window.innerWidth <= 768) sidebarOpen = false"
            @if ($menuAktif['riwayat']) aria-current="page" @endif
        >
            <span class="op-nav-icon">↺</span>
            <span>Riwayat Serah Terima</span>
        </a>

        <a
            href="{{ route('user.catatan-dinas') }}"
            class="op-nav-link {{ $menuAktif['catatan'] ? 'active' : '' }}"
            @click="if (window.innerWidth <= 768) sidebarOpen = false"
            @if ($menuAktif['catatan']) aria-current="page" @endif
        >
            <span class="op-nav-icon">▧</span>
            <span>Catatan Dinas</span>
        </a>

        <a
            href="{{ route('user.profile.show') }}"
            class="op-nav-link {{ $menuAktif['profile'] ? 'active' : '' }}"
            @click="if (window.innerWidth <= 768) sidebarOpen = false"
            @if ($menuAktif['profile']) aria-current="page" @endif
        >
            <span class="op-nav-icon" aria-hidden="true">◉</span>
            <span>Profil Saya</span>
        </a>
    </nav>

    <div class="op-sidebar-bottom">
        <div class="op-active-user">
            <div class="op-active-user-label">PETUGAS AKTIF</div>
            <a href="{{ route('user.profile.show') }}" class="op-active-user-profile rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2">
                <x-user-operasional.user-avatar :user="auth()->user()" size="h-10 w-10" />
                <span class="min-w-0">
                    <span class="op-active-user-name">{{ auth()->user()->name ?? 'Pengguna' }}</span>
                    <span class="op-active-user-info">NIP/NIPKWT: {{ auth()->user()->nip ?? '-' }}</span>
                </span>
            </a>
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

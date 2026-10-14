<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Serah Terima Dinas PJL - Sistem PJL</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <style>
        :root {
            --navy: #20365c;
            --navy-dark: #192b4b;
            --navy-light: #304d78;
            --active: #397eae;
            --white: #ffffff;
            --muted: #cbd8e9;
            --page-bg: #f3f5f8;
            --sidebar-width: 280px;
            --header-height: 76px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--page-bg);
            color: #1d2d50;
            font-family: Arial, Helvetica, sans-serif;
        }

        /* =========================
           HEADER
        ========================= */

        .header {
            position: fixed;
            z-index: 900;
            top: 0;
            right: 0;
            left: 0;
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 28px;
            background: var(--navy-dark);
            color: var(--white);
            box-shadow: 0 2px 8px rgb(0 0 0 / 10%);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .kai-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            flex-shrink: 0;
            overflow: hidden;
            border-radius: 7px;
            background: #fff;
        }

        .kai-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-text {
            min-width: 0;
        }

        .brand-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: .4px;
        }

        .brand-subtitle {
            margin-top: 4px;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 1px;
        }

        .header-right {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        /* TOMBOL ☰ DI SEBELAH KIRI */

        .menu-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            padding: 0;
            border: 1px solid rgb(255 255 255 / 25%);
            border-radius: 8px;
            background: transparent;
            color: #fff;
            font-size: 26px;
            line-height: 1;
            cursor: pointer;
            transition: background .2s ease;
        }

        .menu-button:hover {
            background: rgb(255 255 255 / 12%);
        }

        .menu-button:focus-visible,
        .logout-btn:focus-visible {
            outline: 2px solid #93c5fd;
            outline-offset: 3px;
        }

        .avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 39px;
            height: 39px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #a87331;
            color: white;
            font-weight: 700;
        }

        /* =========================
           SIDEBAR
           Default: tersembunyi
        ========================= */

        .mobile-menu {
            position: fixed;
            z-index: 1000;
            inset: 0 auto 0 0;
            display: flex;
            flex-direction: column;
            width: var(--sidebar-width);
            max-width: 85vw;
            padding: 0 15px 18px;
            overflow-x: hidden;
            overflow-y: auto;
            background: var(--navy);
            color: white;
            box-shadow: 3px 0 15px rgb(0 0 0 / 8%);
            transform: translateX(-100%);
            visibility: hidden;
            transition:
                transform .25s ease,
                visibility .25s ease;
        }

        body.sidebar-open .mobile-menu {
            transform: translateX(0);
            visibility: visible;
        }

        .side-brand {
            display: flex;
            align-items: center;
            min-height: 100px;
            padding: 20px 12px;
            margin-bottom: 18px;
            border-bottom: 1px solid rgb(255 255 255 / 14%);
        }

        .side-brand-title {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .side-brand-subtitle {
            margin-top: 6px;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: .8px;
        }

        .mobile-menu > a {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 52px;
            margin-bottom: 7px;
            padding: 12px 15px;
            border-radius: 8px;
            color: #f8fafc;
            font-size: 14px;
            text-decoration: none;
            transition: background .2s ease;
        }

        .mobile-menu > a:hover {
            background: rgb(255 255 255 / 10%);
        }

        .mobile-menu > a.active {
            background: var(--active);
            color: white;
            font-weight: 700;
        }

        .menu-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 23px;
            flex-shrink: 0;
            font-size: 20px;
        }

        /* IDENTITAS PJL DAN LOGOUT */

        .side-bottom {
            margin-top: auto;
            padding-top: 25px;
        }

        .active-user {
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 9px;
            background: var(--navy-light);
        }

        .active-user-label {
            margin-bottom: 8px;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: .5px;
        }

        .active-user-name {
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .active-user-info {
            color: var(--muted);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .logout-form {
            margin: 0;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            min-height: 48px;
            padding: 12px 15px;
            border: 1px solid rgb(255 255 255 / 18%);
            border-radius: 8px;
            background: transparent;
            color: white;
            font-size: 14px;
            text-align: left;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: rgb(255 255 255 / 10%);
        }

        /* =========================
           OVERLAY
        ========================= */

        .mobile-menu-overlay {
            display: none;
            position: fixed;
            z-index: 950;
            inset: 0;
            background: rgb(15 23 42 / 50%);
        }

        body.sidebar-open .mobile-menu-overlay {
            display: block;
        }

        /* =========================
           KONTEN UTAMA
        ========================= */

        .page-content {
            width: 100%;
            min-height: 100vh;
            margin: 0;
            padding: calc(var(--header-height) + 24px) 25px 30px;
        }

        .content-inner {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .alert-message {
            margin-bottom: 18px;
            padding: 13px 16px;
            border-radius: 8px;
            font-size: 14px;
        }

        .alert-success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .alert-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .no-active-duty {
            padding: 24px;
            border: 1px solid #fde68a;
            border-radius: 12px;
            background: #fffbeb;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {
            :root {
                --header-height: 68px;
            }

            .header {
                padding: 0 16px;
            }

            .header-left {
                gap: 12px;
            }

            .brand {
                gap: 9px;
            }

            .kai-logo {
                width: 38px;
                height: 38px;
            }

            .brand-title {
                font-size: 13px;
            }

            .brand-subtitle {
                font-size: 9px;
                letter-spacing: .6px;
            }

            .avatar {
                width: 35px;
                height: 35px;
            }

            .page-content {
                padding: calc(var(--header-height) + 18px) 15px 22px;
            }
        }

        @media (max-width: 420px) {
            .header {
                padding: 0 10px;
            }

            .header-left {
                gap: 9px;
            }

            .brand-title {
                font-size: 11px;
            }

            .brand-subtitle {
                font-size: 8px;
                letter-spacing: .3px;
            }

            .kai-logo {
                width: 34px;
                height: 34px;
            }

            .menu-button {
                width: 38px;
                height: 38px;
                font-size: 23px;
            }

            .page-content {
                padding-right: 10px;
                padding-left: 10px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .mobile-menu,
            .menu-button {
                transition: none;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header class="header">
        <div class="header-left">

            <button
                class="menu-button"
                id="menuButton"
                type="button"
                aria-label="Buka menu"
                aria-controls="mobileMenu"
                aria-expanded="false"
                onclick="toggleMobileMenu()"
            >☰</button>

            <div class="brand">
                <div class="kai-logo">
                    <img
                        src="{{ asset('images/logo-kai.jpg') }}"
                        alt="Logo KAI"
                    >
                </div>

                <div class="brand-text">
                    <div class="brand-title">KAI OPERASIONAL</div>
                    <div class="brand-subtitle">SISTEM DIGITAL PJL</div>
                </div>
            </div>

        </div>

        <div class="header-right">
            <div class="avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- OVERLAY: HANYA SATU -->
    <div
        class="mobile-menu-overlay"
        id="mobileMenuOverlay"
        onclick="closeMobileMenu()"
        aria-hidden="true"
    ></div>

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
            <span>Dashboard</span>
        </a>

        <a href="{{ url('/user/realisasi-ka') }}">
            <span class="menu-icon">▣</span>
            <span>Jadwal KA</span>
        </a>

        <a
            href="{{ route('user.serah-terima-dinas') }}"
            class="active"
            aria-current="page"
        >
            <span class="menu-icon">⇄</span>
            <span>Serah Terima Dinas</span>
        </a>

        <a href="{{ route('user.catatan-dinas') }}">
            <span class="menu-icon">◧</span>
            <span>Catatan Dinas</span>
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
                    <span class="menu-icon">↪</span>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </nav>

    <!-- KONTEN SERAH TERIMA DINAS -->
    <main class="page-content">
        <div class="content-inner">

            @if (session('success'))
                <div class="alert-message alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-message alert-error">
                    <p class="mb-1 font-bold">Periksa kembali formulir:</p>

                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!$dinas)
                <div class="no-active-duty">
                    <h1 class="text-xl font-bold text-[#1d2d50]">
                        Serah Terima Dinas PJL
                    </h1>

                    <p class="mt-2 text-sm text-amber-900">
                        Belum ada dinas aktif untuk akun ini. Data serah terima hanya dapat
                        dibuat ketika dinas berstatus berlangsung.
                    </p>
                </div>
            @else
                @include('user.serahterimacatatan.partials.header')
                @include('user.serahterimacatatan.partials.ka-batal')
                @include('user.serahterimacatatan.partials.ka-berjalan')
                @include('user.serahterimacatatan.partials.ka-belum-lewat')
                @include('user.serahterimacatatan.partials.alat-pengaman')
                @include('user.serahterimacatatan.partials.inventaris')
                @include('user.serahterimacatatan.partials.catatan')
                @include('user.serahterimacatatan.partials.petugas')
                @include('user.serahterimacatatan.partials.tanda-tangan')
                @include('user.serahterimacatatan.partials.actions')
            @endif

        </div>
    </main>

    <script>
        function toggleMobileMenu() {
            const isOpen = document.body.classList.toggle('sidebar-open');
            updateMenuButton(isOpen);
        }

        function closeMobileMenu() {
            document.body.classList.remove('sidebar-open');
            updateMenuButton(false);
        }

        function updateMenuButton(isOpen) {
            const button = document.getElementById('menuButton');
            const sidebar = document.getElementById('mobileMenu');

            if (button) {
                button.setAttribute('aria-expanded', String(isOpen));
                button.setAttribute(
                    'aria-label',
                    isOpen ? 'Tutup menu' : 'Buka menu'
                );
            }

            if (sidebar) {
                sidebar.setAttribute('aria-hidden', String(!isOpen));
                sidebar.inert = !isOpen;
            }
        }

        /* Tutup sidebar setelah memilih menu */
        document.querySelectorAll('#mobileMenu > a').forEach(function (link) {
            link.addEventListener('click', closeMobileMenu);
        });

        /* Tutup sidebar dengan tombol Escape */
        document.addEventListener('keydown', function (event) {
            if (
                event.key === 'Escape' &&
                document.body.classList.contains('sidebar-open')
            ) {
                closeMobileMenu();
                document.getElementById('menuButton')?.focus();
            }
        });
    </script>

</body>
</html>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PJL Dashboard</title>

    <style>

        /* ========================================
           RESET
        ======================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        /* ========================================
           ROOT
        ======================================== */

        :root {
            --navy: #1E3156;
            --navy-dark: #17284A;

            --text: #1F2937;
            --muted: #7A8494;

            --border: #E3E7ED;
            --background: #F5F7FA;

            --white: #FFFFFF;

            --blue: #2563EB;
            --info-bg: #F0F6FF;
            --info-border: #C9DFFF;

            --radius: 10px;
        }


        /* ========================================
           BODY
        ======================================== */

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }


        /* ========================================
           HEADER
        ======================================== */

        .topbar {
            width: 100%;
            min-height: 64px;

            background: var(--navy);

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 32px;

            color: var(--white);

            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.08);
        }


        /* ========================================
           BRAND
        ======================================== */

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .kai-logo {
            width: 46px;
            height: 34px;

            background: var(--white);

            border-radius: 5px;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            flex-shrink: 0;
        }

        .kai-logo img {
            width: 100%;
            height: 100%;

            object-fit: contain;

            display: block;
        }

        .brand-text {
            display: flex;
            flex-direction: column;

            gap: 4px;
        }

        .brand-title {
            font-size: 14px;
            font-weight: 700;

            line-height: 1;

            letter-spacing: 0.1px;
        }

        .brand-subtitle {
            font-size: 8px;

            color: #D9E1EE;

            line-height: 1;

            letter-spacing: 0.4px;
        }


        /* ========================================
           HEADER RIGHT
        ======================================== */

        .header-right {
            display: flex;
            align-items: center;

            gap: 18px;
        }

        .pjl-info {
            text-align: right;

            padding-right: 18px;

            border-right:
                1px solid rgba(255, 255, 255, 0.25);
        }

        .pjl-name {
            font-size: 10px;
            font-weight: 700;

            margin-bottom: 4px;
        }

        .shift {
            font-size: 8px;

            color: #D9E1EE;
        }


        /* ========================================
           LOGOUT
        ======================================== */

        .logout-btn {
            height: 32px;
            min-width: 82px;

            padding: 0 13px;

            border:
                1px solid rgba(255, 255, 255, 0.5);

            border-radius: 6px;

            background:
                rgba(255, 255, 255, 0.04);

            color: var(--white);

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            font-family: inherit;

            font-size: 9px;
            font-weight: 600;

            cursor: pointer;

            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background:
                rgba(255, 255, 255, 0.12);

            border-color:
                rgba(255, 255, 255, 0.75);
        }


        /* ========================================
           MAIN CONTENT
        ======================================== */

        .content {
            width: 100%;
            max-width: 1200px;

            margin: 0 auto;

            padding: 30px 32px 50px;
        }


        /* ========================================
           USER CARD
        ======================================== */

        .user-card {
            width: 100%;
            min-height: 105px;

            background: var(--white);

            border:
                1px solid var(--border);

            border-radius: var(--radius);

            display: flex;
            align-items: center;

            padding: 20px 22px;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.025);
        }


        /* ========================================
           AVATAR
        ======================================== */

        .avatar {
            width: 48px;
            height: 48px;

            flex-shrink: 0;

            border-radius: 50%;

            background: var(--navy);
            color: var(--white);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 14px;
            font-weight: 700;

            margin-right: 16px;
        }


        /* ========================================
           EMPLOYEE
        ======================================== */

        .employee {
            flex: 1;

            min-width: 230px;

            padding-right: 20px;
        }

        .employee-label {
            color: var(--muted);

            font-size: 9px;
            font-weight: 500;

            margin-bottom: 6px;
        }

        .employee-name {
            color: var(--navy-dark);

            font-size: 13px;
            font-weight: 700;

            line-height: 1.5;
        }


        /* ========================================
           EMPLOYEE INFO
        ======================================== */

        .employee-info {
            display: flex;
            align-items: stretch;
        }

        .info-item {
            min-width: 155px;

            padding: 2px 24px;

            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .info-item:first-child {
            padding-left: 0;
        }

        .info-item + .info-item {
            border-left:
                1px solid var(--border);
        }

        .info-label {
            color: var(--muted);

            font-size: 8px;
            font-weight: 600;

            letter-spacing: 0.3px;

            margin-bottom: 6px;
        }

        .info-value {
            color: var(--navy-dark);

            font-size: 9px;
            font-weight: 700;

            white-space: nowrap;

            line-height: 1.4;
        }


        /* ========================================
           ALERT / CATATAN DINAS
        ======================================== */

        .alert {
            width: 100%;
            min-height: 44px;

            margin-top: 18px;

            padding: 10px 14px;

            border:
                1px solid var(--info-border);

            background:
                var(--info-bg);

            border-radius: 8px;

            display: flex;
            align-items: center;

            color: var(--blue);

            font-size: 9px;

            line-height: 1.5;
        }

        .alert-icon {
            width: 25px;

            flex-shrink: 0;

            margin-right: 6px;

            font-size: 15px;
            font-weight: 700;

            text-align: center;
        }

        .alert strong {
            font-weight: 700;
        }


        /* ========================================
           MENU TITLE
        ======================================== */

        .menu-title {
            margin-top: 28px;
            margin-bottom: 14px;

            color: var(--navy-dark);

            font-size: 14px;
            font-weight: 700;
        }


        /* ========================================
           MENU GRID
        ======================================== */

        .menu-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 16px;
        }


        /* ========================================
           MENU CARD
        ======================================== */

        .menu-card {
            min-height: 135px;

            background: var(--white);

            border:
                1px solid var(--border);

            border-radius: var(--radius);

            display: flex;
            flex-direction: column;

            align-items: center;
            justify-content: center;

            position: relative;

            overflow: hidden;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.025);

            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .menu-card::before {
            content: "";

            position: absolute;

            top: 0;
            left: 0;
            right: 0;

            height: 3px;

            background: var(--navy);

            transform: scaleX(0);

            transform-origin: center;

            transition:
                transform 0.2s ease;
        }

        .menu-card:hover {
            border-color: #C8D1DE;

            box-shadow:
                0 8px 20px rgba(15, 23, 42, 0.07);

            transform: translateY(-2px);
        }

        .menu-card:hover::before {
            transform: scaleX(1);
        }


        /* ========================================
           MENU ICON
        ======================================== */

        .menu-icon {
            width: 42px;
            height: 42px;

            margin-bottom: 12px;

            background: #F1F4F8;

            border:
                1px solid #E7EBF0;

            border-radius: 8px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: var(--navy);

            font-size: 18px;
            font-weight: 700;

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .menu-card:hover .menu-icon {
            background: #EAF0F8;

            transform: scale(1.04);
        }


        /* ========================================
           MENU TEXT
        ======================================== */

        .menu-text {
            color: var(--navy-dark);

            font-size: 11px;
            font-weight: 700;

            text-align: center;
        }


        /* ========================================
           TABLET
        ======================================== */

        @media (max-width: 950px) {

            .topbar {
                padding: 0 24px;
            }

            .content {
                padding: 25px 24px 45px;
            }

            .user-card {
                align-items: flex-start;

                flex-wrap: wrap;
            }

            .employee {
                padding-right: 0;
            }

            .employee-info {
                width: 100%;

                margin-top: 20px;

                padding-top: 18px;

                border-top:
                    1px solid var(--border);
            }

            .info-item {
                flex: 1;

                min-width: 0;
            }

            .info-item:first-child {
                padding-left: 0;
            }

            .menu-grid {
                grid-template-columns:
                    repeat(3, 1fr);
            }
        }


        /* ========================================
           MOBILE
        ======================================== */

        @media (max-width: 650px) {

            body {
                overflow-x: hidden;
            }

            .topbar {
                min-height: 60px;

                padding: 0 14px;
            }

            .brand {
                gap: 9px;
            }

            .kai-logo {
                width: 40px;
                height: 30px;
            }

            .brand-title {
                font-size: 12px;
            }

            .brand-subtitle {
                font-size: 6.5px;
            }

            .pjl-info {
                display: none;
            }

            .header-right {
                gap: 0;
            }

            .logout-btn {
                height: 30px;

                min-width: 68px;

                font-size: 8px;
            }


            /* CONTENT */

            .content {
                padding: 18px 14px 35px;
            }


            /* USER CARD */

            .user-card {
                min-height: auto;

                padding: 17px;

                display: grid;

                grid-template-columns:
                    44px 1fr;

                column-gap: 12px;

                row-gap: 16px;

                align-items: center;
            }

            .avatar {
                width: 44px;
                height: 44px;

                margin-right: 0;

                font-size: 12px;
            }

            .employee {
                min-width: 0;

                padding-right: 0;
            }

            .employee-label {
                font-size: 8px;

                line-height: 1.4;

                margin-bottom: 4px;
            }

            .employee-name {
                font-size: 11px;

                line-height: 1.5;
            }


            /* INFO */

            .employee-info {
                width: 100%;

                grid-column: 1 / -1;

                margin-top: 0;

                padding-top: 0;

                border-top: none;

                display: flex;

                flex-direction: column;

                align-items: stretch;
            }

            .info-item {
                width: 100%;

                min-width: 0;

                padding: 11px 0;
            }

            .info-item:first-child {
                padding-top: 0;
            }

            .info-item + .info-item {
                border-left: none;

                border-top:
                    1px solid var(--border);
            }

            .info-label {
                font-size: 7px;

                margin-bottom: 5px;
            }

            .info-value {
                font-size: 9px;

                white-space: normal;

                line-height: 1.5;
            }


            /* ALERT */

            .alert {
                margin-top: 15px;

                padding: 10px 11px;

                align-items: flex-start;

                font-size: 8.5px;

                line-height: 1.55;
            }

            .alert-icon {
                width: 20px;

                margin-top: 1px;

                margin-right: 4px;

                font-size: 13px;
            }


            /* MENU TITLE */

            .menu-title {
                margin-top: 23px;

                margin-bottom: 12px;

                font-size: 12px;
            }


            /* MENU */

            .menu-grid {
                grid-template-columns: 1fr;

                gap: 10px;
            }

            .menu-card {
                width: 100%;

                min-height: 78px;

                flex-direction: row;

                justify-content: flex-start;

                padding: 0 18px;

                gap: 14px;
            }


            /* MOBILE SIDE LINE */

            .menu-card::before {
                width: 3px;
                height: 100%;

                top: 0;
                bottom: 0;

                right: auto;

                transform: scaleY(0);
            }

            .menu-card:hover {
                transform: none;
            }

            .menu-card:hover::before {
                transform: scaleY(1);
            }


            /* ICON */

            .menu-icon {
                width: 38px;
                height: 38px;

                flex-shrink: 0;

                margin-bottom: 0;

                font-size: 16px;
            }


            /* TEXT */

            .menu-text {
                font-size: 11px;

                text-align: left;
            }
        }


        /* ========================================
           MOBILE KECIL
        ======================================== */

        @media (max-width: 380px) {

            .topbar {
                padding: 0 10px;
            }

            .content {
                padding: 15px 10px 30px;
            }

            .user-card {
                padding: 14px;
            }

            .employee-name {
                font-size: 10px;
            }

            .alert {
                font-size: 8px;
            }

            .menu-card {
                min-height: 74px;

                padding: 0 14px;
            }

            .menu-icon {
                width: 34px;
                height: 34px;

                font-size: 15px;
            }

            .menu-text {
                font-size: 10px;
            }
        }

    </style>
</head>


<body>


    <!-- ========================================
         HEADER
    ======================================== -->

    <header class="topbar">


        <!-- LOGO + BRAND -->

        <div class="brand">

            <div class="kai-logo">

                <img
                    src="{{ asset('images/logo-kai.jpg') }}"
                    alt="Logo KAI"
                >

            </div>


            <div class="brand-text">

                <div class="brand-title">
                    Sistem PJL
                </div>

                <div class="brand-subtitle">
                    PT. KERETA API INDONESIA
                </div>

            </div>

        </div>


        <!-- HEADER RIGHT -->

        <div class="header-right">

            <div class="pjl-info">
                <div class="pjl-name">
                    {{ $lokasiDinas }}
                </div>

                <div class="shift">
                    @if ($namaShift !== '-')
                        {{ $namaShift }}
                        @if ($jamDinas !== '-')
                            ({{ $jamDinas }})
                        @endif
                    @else
                        Shift belum ditentukan
                    @endif
                </div>
            </div>


            <!-- LOGOUT -->
            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                @csrf

                <button type="submit" class="logout-btn">
                    <span>↪</span>
                    <span>Keluar</span>
                </button>
            </form>


                    </div>

                </header>


    <!-- ========================================
         MAIN CONTENT
    ======================================== -->

    <main class="content">


        <!-- ====================================
             INFORMASI PETUGAS
        ==================================== -->

        <section class="user-card">


            <!-- AVATAR -->

            <div class="employee">
                <div class="employee-label">
                    Petugas Jaga Jalan Lintasan (PJL)
                </div>

                <div class="employee-name">
                    {{ $user->name ?? '-' }}
                    (NIP: {{ $user->nip ?? '-' }})
                </div>
            </div>


            <!-- DATA PETUGAS -->

            <div class="employee">

                <div class="employee-label">
                    Petugas Jaga Jalan Lintasan (PJL)
                </div>

                <div class="employee-name">
                    Suhartono (NIP: 199208242015031002)
                </div>

            </div>


            <!-- INFORMASI DINAS -->

            <div class="employee-info">


                <!-- LOKASI DINAS -->

                <div class="info-item">

                    <div class="info-label">
                        LOKASI DINAS
                    </div>

                    <div class="info-value">
                        @if ($namaShift !== '-')
                            {{ $namaShift }}
                            @if ($jamDinas !== '-')
                                ({{ $jamDinas }})
                            @endif
                        @else
                            Shift belum ditentukan
                        @endif
                    </div>

                </div>


                <!-- SHIFT DINAS -->

                <div class="info-item">

                    <div class="info-label">
                        SHIFT DINAS
                    </div>

                    <div class="info-value">
                        Shift B (Siang)
                    </div>

                </div>


                <!-- TANGGAL TUGAS -->

                <div class="info-item">

                    <div class="info-label">
                        TANGGAL TUGAS
                    </div>

                    <div class="info-value">
                        {{ $tanggalTugas }}
                    </div>

                </div>

            </div>

        </section>


        <!-- ====================================
             CATATAN DINAS AKTIF
        ==================================== -->

        <div class="alert">

            <span class="alert-icon">
                ▤
            </span>

            <span>
                <strong>
                    Catatan Dinas Aktif:
                </strong>

                @if ($catatanAktif)
                    {{ $catatanAktif->judul ?? 'Catatan Dinas' }}

                    @if (!empty($catatanAktif->isi_catatan))
                        — {{ $catatanAktif->isi_catatan }}
                    @endif
                @else
                    Belum ada catatan untuk dinas aktif.
                @endif
            </span>

        </div>


        <!-- ====================================
             MENU OPERASIONAL
        ==================================== -->

        <h2 class="menu-title">
            Menu Operasional PJL
        </h2>


        <div class="menu-grid">


            <!-- ==================================
                 REALISASI KA
            ================================== -->

            <a href="{{ url('/user/realisasi-ka') }}" class="menu-card">

                <div class="menu-icon">
                    ✓
                </div>

                <div class="menu-text">
                    Realisasi KA
                </div>

            </a>


            <!-- ==================================
                 CATATAN DINAS
            ================================== -->

            <a href="{{ url('/user/catatan-dinas') }}" class="menu-card">

                <div class="menu-icon">
                    ▤
                </div>

                <div class="menu-text">
                    Catatan Dinas
                </div>

            </a>


            <!-- ==================================
                 SERAH TERIMA DINAS
            ================================== -->

            <a href="{{ url('/user/serah-terima-dinas') }}" class="menu-card">

                <div class="menu-icon">
                    ⇄
                </div>

                <div class="menu-text">
                    Serah Terima Dinas
                </div>

            </a>


        </div>

    </main>


</body>

</html>

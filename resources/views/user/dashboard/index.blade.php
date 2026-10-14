<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PJL Dashboard</title>

    <link rel="stylesheet" href="{{ asset('css/user/dashboard.css') }}">
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



            <!-- INFORMASI DINAS -->

            <div class="employee-info">


                <!-- LOKASI DINAS -->

                <div class="info-item">

                    <div class="info-label">
                        LOKASI DINAS
                    </div>

                <div class="info-value">
                    {{ $lokasiDinas ?? '-' }}
                </div>

                </div>


                <!-- SHIFT DINAS -->

                <div class="info-item">

                    <div class="info-label">
                        SHIFT DINAS
                    </div>

                    <div class="info-value">
                        @if (($namaShift ?? '-') !== '-')
                            {{ $namaShift }}
                            @if (($jamDinas ?? '-') !== '-')
                                ({{ $jamDinas }})
                            @endif
                        @else
                            Shift belum ditentukan
                        @endif
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

            <a href="{{  route('user.serah-terima-dinas') }}" class="menu-card">

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

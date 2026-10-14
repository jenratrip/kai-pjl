    <!-- =====================================================
         HEADER
    ===================================================== -->

    <header class="top-header">

        <div class="header-left">

            <button
                type="button"
                class="menu-button"
                id="menuButton"
                aria-label="Buka menu"
                aria-expanded="false"
                aria-controls="mobileMenu"
                onclick="toggleMobileMenu()"
            >
                ☰
            </button>

            <img
                src="{{ asset('images/logo-kai.jpg') }}"
                alt="Logo KAI"
                class="kai-logo"
            >

            <div class="header-brand">

                <div class="brand-title">
                    KAI OPERASIONAL
                </div>

                <div class="brand-subtitle">
                    SISTEM DIGITAL PJL
                </div>

            </div>

        </div>

        <div class="header-right">

            <div class="user-circle">
                S
            </div>

        </div>

    </header>


    <!-- =====================================================
         OVERLAY
    ===================================================== -->

    <div
        class="menu-overlay"
        id="menuOverlay"
        onclick="closeMobileMenu()"
    ></div>


    <!-- =====================================================
         SIDE MENU
    ===================================================== -->

    <aside
        class="mobile-menu"
        id="mobileMenu"
        aria-label="Menu navigasi"
    >

        <div class="menu-brand">

            <div class="menu-brand-title">
                KAI OPERASIONAL
            </div>

            <div class="menu-brand-subtitle">
                SISTEM DIGITAL PJL
            </div>

        </div>


        <nav class="menu-list">

            <a
                href="/user/dashboard"
                class="menu-link"
                onclick="closeMobileMenu()"
            >
                <span class="menu-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a
                href="/user/realisasi-ka"
                class="menu-link"
                onclick="closeMobileMenu()"
            >
                <span class="menu-icon">▣</span>
                <span>Jadwal KA</span>
            </a>

            <a
                href="/user/serah-terima-dinas"
                class="menu-link active"
                onclick="closeMobileMenu()"
            >
                <span class="menu-icon">⇄</span>
                <span>Serah Terima Dinas</span>
            </a>

            <a
                href="/user/catatan-dinas"
                class="menu-link"
                onclick="closeMobileMenu()"
            >
                <span class="menu-icon">◧</span>
                <span>Catatan Dinas</span>
            </a>

        </nav>


        <div class="menu-bottom">

            <div class="active-user-card">

                <div class="active-user-label">
                    PJL AKTIF
                </div>

                <div class="active-user-name">
                    Budi Santoso
                </div>

                <div class="active-user-info">
                    NIP: 199208242015031002
                    <br>
                    Shift B&nbsp;&nbsp;•&nbsp;&nbsp;08:00–16:00
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

        </div>

    </aside>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Catatan Dinas - Sistem PJL</title>


    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
            color: #26344d;
            overflow-x: hidden;
        }


        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .header {
            height: 58px;

            background: #1e3156;
            color: white;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 24px 0 26px;

            position: relative;
            z-index: 900;
        }


        .brand {
            display: flex;
            align-items: center;
            gap: 10px;

            min-width: 0;
        }


        /* =====================================================
           LOGO KAI
        ===================================================== */

        .kai-logo {
            width: 42px;
            height: 32px;

            background: white;

            border-radius: 4px;

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
            min-width: 0;
            line-height: 1.2;
        }


        .brand-title {
            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }


        .brand-subtitle {
            margin-top: 3px;

            font-size: 7px;
            color: #d2dae6;

            white-space: nowrap;
        }


        /* =====================================================
           HEADER RIGHT
        ===================================================== */

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .menu-button {
            width: 34px;
            height: 34px;

            border: none;
            background: transparent;

            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
            line-height: 1;

            cursor: pointer;
        }


        .menu-button:hover {
            opacity: .8;
        }


        .avatar {
            width: 30px;
            height: 30px;

            border-radius: 50%;

            background: #e89a36;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 10px;
            font-weight: 700;

            flex-shrink: 0;
        }


        /* =====================================================
           SIDE MENU
        ===================================================== */

        .mobile-menu {
            position: fixed;

            top: 0;
            left: -310px;

            width: 290px;
            height: 100vh;

            background: #1e3156;

            z-index: 1001;

            padding: 0 14px 14px;

            display: flex;
            flex-direction: column;

            transition: left .25s ease;

            box-shadow: 8px 0 25px rgba(0, 0, 0, .18);
        }


        .mobile-menu.show {
            left: 0;
        }


        /* =====================================================
           SIDE BRAND
        ===================================================== */

        .side-brand {
            height: 88px;

            margin: 0 -14px 12px;

            padding: 0 20px;

            display: flex;
            align-items: center;

            border-bottom: 1px solid rgba(255,255,255,.12);
        }


        .side-brand-title {
            color: white;

            font-size: 15px;
            font-weight: 700;

            line-height: 1.2;
        }


        .side-brand-subtitle {
            margin-top: 5px;

            color: #d3dceb;

            font-size: 9px;
        }


        /* =====================================================
           SIDE MENU LINK
        ===================================================== */

        .mobile-menu a {
            display: flex;
            align-items: center;

            min-height: 52px;

            padding: 0 16px;

            margin-bottom: 4px;

            border-radius: 8px;

            color: white;

            text-decoration: none;

            font-size: 13px;
            font-weight: 500;

            transition: .2s;
        }


        .mobile-menu a:hover {
            background: rgba(255,255,255,.08);
        }


        .mobile-menu a.active {
            background: #3677aa;
        }


        .menu-icon {
            width: 28px;

            margin-right: 8px;

            font-size: 17px;

            text-align: center;

            color: #e1e8f2;

            flex-shrink: 0;
        }


        /* =====================================================
           SIDE MENU BOTTOM
        ===================================================== */

        .side-bottom {
            margin-top: auto;
        }


        .active-user {
            background: #304b78;

            border-radius: 8px;

            padding: 12px;

            margin-bottom: 10px;
        }


        .active-user-label {
            font-size: 10px;

            color: #d5ddec;

            margin-bottom: 4px;
        }


        .active-user-name {
            font-size: 14px;

            font-weight: 700;

            color: white;

            margin-bottom: 5px;
        }


        .active-user-info {
            font-size: 10px;

            line-height: 1.6;

            color: #d9e1ed;
        }


        /* =====================================================
           LOGOUT
        ===================================================== */

        .menu-logout {
            min-height: 50px !important;

            border: 1px solid rgba(255,255,255,.2);

            margin-bottom: 0 !important;
        }


        .menu-logout:hover {
            background: rgba(255,255,255,.08) !important;
        }


        /* =====================================================
           OVERLAY
        ===================================================== */

        .mobile-menu-overlay {
            position: fixed;

            inset: 0;

            background: rgba(15, 23, 42, .42);

            z-index: 1000;

            display: none;
        }


        .mobile-menu-overlay.show {
            display: block;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            padding: 27px 26px 40px;

            min-height: calc(100vh - 58px);
        }


        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 20px;
        }


        .page-title {
            font-size: 18px;
            font-weight: 700;

            color: #26344d;
        }


        /* =====================================================
           ADD BUTTON
        ===================================================== */

        .add-button {
            height: 32px;

            padding: 0 14px;

            border: none;
            border-radius: 5px;

            background: #1e3156;
            color: white;

            font-size: 9px;
            font-weight: 600;

            cursor: pointer;

            white-space: nowrap;

            transition: .2s;
        }


        .add-button:hover {
            background: #29436f;
        }


        /* =====================================================
           TABLE CARD
        ===================================================== */

        .table-card {
            width: 100%;

            background: white;

            border-radius: 6px;

            overflow-x: auto;
            overflow-y: hidden;

            box-shadow: 0 1px 3px rgba(0, 0, 0, .04);

            -webkit-overflow-scrolling: touch;
        }


        table {
            width: 100%;

            min-width: 850px;

            border-collapse: collapse;

            table-layout: fixed;
        }


        thead {
            background: #f7f8fa;
        }


        th {
            height: 36px;

            padding: 0 14px;

            text-align: left;

            font-size: 8px;
            font-weight: 700;

            color: #475569;

            border-bottom: 1px solid #e5e8ed;
        }


        td {
            height: 34px;

            padding: 0 14px;

            font-size: 9px;

            color: #4b5563;

            border-bottom: 1px solid #edf0f3;

            vertical-align: middle;
        }


        tbody tr:hover {
            background: #fafbfd;
        }


        /* =====================================================
           COLUMN WIDTH
        ===================================================== */

        th:nth-child(1),
        td:nth-child(1) {
            width: 42px;
            text-align: center;
        }


        th:nth-child(2),
        td:nth-child(2) {
            width: 105px;
        }


        th:nth-child(3),
        td:nth-child(3) {
            width: 125px;
        }


        th:nth-child(4),
        td:nth-child(4) {
            width: 105px;
        }


        th:nth-child(5),
        td:nth-child(5) {
            width: auto;
        }


        th:nth-child(6),
        td:nth-child(6) {
            width: 50px;
            text-align: center;
        }


        .date {
            color: #26344d;
            font-weight: 600;
        }


        .pjl {
            color: #3d4758;
        }


        .shift-text {
            color: #647084;
        }


        .note {
            color: #5b6574;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        .view-button {
            border: none;

            background: transparent;

            color: #2563eb;

            font-size: 9px;
            font-weight: 600;

            cursor: pointer;
        }


        .view-button:hover {
            text-decoration: underline;
        }


        /* =====================================================
           TABLE FOOTER
        ===================================================== */

        .table-footer {
            min-width: 850px;

            height: 49px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 14px;
        }


        .showing {
            font-size: 8px;

            color: #687588;
        }


        .pagination {
            display: flex;
            align-items: center;

            gap: 4px;
        }


        .page-button {
            width: 22px;
            height: 22px;

            border: 1px solid #e1e5ea;
            border-radius: 4px;

            background: white;

            color: #334155;

            font-size: 8px;

            cursor: pointer;

            transition: .2s;
        }


        .page-button:hover {
            background: #f3f5f8;
        }


        .page-button.active {
            background: #1e3156;

            border-color: #1e3156;

            color: white;
        }


        /* =====================================================
           MODAL
        ===================================================== */

        .modal-overlay {
            position: fixed;

            inset: 0;

            display: none;

            align-items: center;
            justify-content: center;

            padding: 16px;

            background: rgba(15, 23, 42, .4);

            z-index: 1100;
        }


        .modal-overlay.show {
            display: flex;
        }


        .modal {
            width: 430px;
            max-width: 100%;

            max-height: calc(100vh - 32px);

            overflow-y: auto;

            background: white;

            border-radius: 7px;

            padding: 20px;

            box-shadow: 0 15px 40px rgba(0, 0, 0, .15);
        }


        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 18px;
        }


        .modal-title {
            font-size: 15px;
            font-weight: 700;

            color: #26344d;
        }


        .close {
            width: 30px;
            height: 30px;

            border: none;
            background: transparent;

            font-size: 20px;
            color: #647084;

            cursor: pointer;

            display: flex;
            align-items: center;
            justify-content: center;
        }


        .close:hover {
            color: #1e3156;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {
            margin-bottom: 13px;
        }


        .form-label {
            display: block;

            margin-bottom: 5px;

            font-size: 9px;
            font-weight: 600;

            color: #475569;
        }


        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;

            border: 1px solid #dce1e7;
            border-radius: 4px;

            background: white;

            padding: 8px 9px;

            font-size: 9px;

            color: #334155;

            outline: none;

            transition: .2s;
        }


        .form-input,
        .form-select {
            height: 31px;
        }


        .form-textarea {
            height: 80px;

            resize: vertical;

            line-height: 1.5;
        }


        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: #55759e;

            box-shadow: 0 0 0 2px rgba(85, 117, 158, .08);
        }


        /* =====================================================
           MODAL FOOTER
        ===================================================== */

        .modal-footer {
            display: flex;

            justify-content: flex-end;

            gap: 7px;

            margin-top: 18px;
        }


        .modal-button {
            height: 29px;

            padding: 0 14px;

            border-radius: 4px;

            font-size: 9px;
            font-weight: 600;

            cursor: pointer;
        }


        .cancel {
            background: white;

            border: 1px solid #d8dee6;

            color: #475569;
        }


        .cancel:hover {
            background: #f7f8fa;
        }


        .save {
            background: #1e3156;

            border: 1px solid #1e3156;

            color: white;
        }


        .save:hover {
            background: #29436f;
        }


        /* =====================================================
           DETAIL MODAL
        ===================================================== */

        .detail-row {
            display: grid;

            grid-template-columns: 100px minmax(0, 1fr);

            gap: 8px;

            padding: 9px 0;

            border-bottom: 1px solid #edf0f3;
        }


        .detail-label {
            font-size: 8px;
            font-weight: 700;

            color: #647084;
        }


        .detail-value {
            font-size: 9px;

            color: #374151;

            line-height: 1.5;

            word-break: break-word;
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 900px) {

            .header {
                padding: 0 16px;
            }


            .main {
                padding: 22px 16px 35px;
            }


            .page-header {
                gap: 12px;
            }


            .modal {
                width: calc(100% - 32px);

                max-width: 430px;
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 650px) {

            body {
                overflow-x: hidden;
            }


            /* HEADER */

            .header {
                height: 56px;

                padding: 0 12px;
            }


            .brand {
                gap: 8px;
            }


            .kai-logo {
                width: 38px;
                height: 29px;
            }


            .brand-title {
                font-size: 11px;

                overflow: hidden;

                text-overflow: ellipsis;
            }


            .brand-subtitle {
                font-size: 6px;

                margin-top: 2px;
            }


            .header-right {
                gap: 4px;
            }


            .menu-button {
                display: flex;

                font-size: 24px;
            }


            .avatar {
                width: 30px;
                height: 30px;
            }


            /* MAIN */

            .main {
                min-height: calc(100vh - 56px);

                padding: 16px 10px 30px;
            }


            /* PAGE HEADER */

            .page-header {
                align-items: flex-start;

                margin-bottom: 14px;

                gap: 10px;
            }


            .page-title {
                font-size: 15px;

                line-height: 1.4;
            }


            .add-button {
                height: 30px;

                padding: 0 10px;

                font-size: 8px;
            }


            /* TABLE */

            .table-card {
                width: 100%;

                border-radius: 6px;

                overflow-x: auto;

                -webkit-overflow-scrolling: touch;
            }


            table {
                min-width: 850px;
            }


            th {
                height: 34px;

                padding: 0 10px;

                font-size: 8px;
            }


            td {
                height: 40px;

                padding: 0 10px;

                font-size: 8px;
            }


            .view-button {
                font-size: 8px;
            }


            /* FOOTER */

            .table-footer {
                min-width: 850px;

                height: 48px;

                padding: 0 10px;
            }


            .showing {
                font-size: 7px;
            }


            .pagination {
                gap: 3px;
            }


            .page-button {
                width: 24px;
                height: 24px;

                font-size: 8px;
            }


            /* =================================================
               MOBILE SIDE MENU
            ================================================= */

            .mobile-menu {
                width: 290px;

                max-width: 84vw;

                padding: 0 14px 14px;
            }


            .side-brand {
                height: 88px;

                padding: 0 20px;

                margin-bottom: 12px;
            }


            .side-brand-title {
                font-size: 15px;
            }


            .side-brand-subtitle {
                font-size: 9px;
            }


            .mobile-menu a {
                min-height: 52px;

                padding: 0 16px;

                margin-bottom: 4px;

                font-size: 13px;
            }


            .menu-icon {
                width: 28px;

                margin-right: 8px;

                font-size: 17px;
            }


            .active-user {
                padding: 12px;

                margin-bottom: 10px;
            }


            .active-user-label {
                font-size: 10px;
            }


            .active-user-name {
                font-size: 14px;
            }


            .active-user-info {
                font-size: 10px;
            }


            .menu-logout {
                min-height: 50px !important;
            }


            /* MODAL */

            .modal-overlay {
                padding: 14px;
            }


            .modal {
                width: 100%;

                max-width: 430px;

                max-height: calc(100vh - 28px);

                padding: 17px;

                border-radius: 7px;
            }


            .modal-header {
                margin-bottom: 15px;
            }


            .modal-title {
                font-size: 14px;
            }


            .form-group {
                margin-bottom: 12px;
            }


            .form-label {
                margin-bottom: 5px;

                font-size: 8px;
            }


            .form-input,
            .form-select,
            .form-textarea {
                font-size: 11px;

                padding: 8px 9px;

                border-radius: 5px;
            }


            .form-input,
            .form-select {
                height: 38px;
            }


            .form-textarea {
                height: 100px;
            }


            .modal-footer {
                margin-top: 15px;

                gap: 7px;
            }


            .modal-button {
                height: 36px;

                padding: 0 14px;

                font-size: 9px;
            }


            /* DETAIL */

            .detail-row {
                grid-template-columns: 75px minmax(0, 1fr);

                gap: 8px;

                padding: 10px 0;
            }


            .detail-label {
                font-size: 8px;
            }


            .detail-value {
                font-size: 9px;

                line-height: 1.5;
            }

        }


        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 400px) {

            .header {
                padding: 0 9px;
            }


            .kai-logo {
                width: 35px;
                height: 27px;
            }


            .brand-title {
                font-size: 10px;
            }


            .brand-subtitle {
                font-size: 5px;
            }


            .main {
                padding: 14px 7px 25px;
            }


            .page-title {
                font-size: 14px;
            }


            .add-button {
                height: 28px;

                padding: 0 8px;

                font-size: 7px;
            }


            .mobile-menu {
                width: 280px;

                max-width: 84vw;
            }


            .side-brand {
                height: 84px;

                padding: 0 18px;
            }


            .side-brand-title {
                font-size: 14px;
            }


            .side-brand-subtitle {
                font-size: 8px;
            }


            .mobile-menu a {
                min-height: 50px;

                font-size: 12px;

                padding: 0 14px;
            }


            .menu-icon {
                font-size: 16px;
            }


            .active-user {
                padding: 11px;
            }


            .active-user-label {
                font-size: 9px;
            }


            .active-user-name {
                font-size: 13px;
            }


            .active-user-info {
                font-size: 9px;
            }


            .menu-logout {
                min-height: 48px !important;
            }


            th {
                font-size: 7px;
            }


            td {
                font-size: 7px;
            }


            .view-button {
                font-size: 7px;
            }


            .table-footer {
                min-width: 850px;
            }


            .modal {
                padding: 14px;
            }


            .modal-title {
                font-size: 13px;
            }


            .form-input,
            .form-select,
            .form-textarea {
                font-size: 10px;
            }


            .detail-row {
                grid-template-columns: 65px minmax(0, 1fr);
            }


            .detail-label {
                font-size: 7px;
            }


            .detail-value {
                font-size: 8px;
            }


            .modal-button {
                font-size: 8px;
            }

        }

    </style>

</head>


<body>


    <!-- =====================================================
         HEADER
    ===================================================== -->

    <header class="header">

        <div class="brand">

            <!-- LOGO KAI -->

            <div class="kai-logo">

                <img
                    src="{{ asset('images/logo-kai.jpg') }}"
                    alt="Logo KAI"
                >

            </div>


            <div class="brand-text">

                <div class="brand-title">
                    KAI OPERASIONAL
                </div>


                <div class="brand-subtitle">
                    SISTEM DIGITAL PJL
                </div>

            </div>

        </div>


        <div class="header-right">

            <button
                class="menu-button"
                type="button"
                aria-label="Buka menu"
                onclick="toggleMobileMenu()"
            >
                ☰
            </button>


            <div class="avatar">
                S
            </div>

        </div>

    </header>


    <!-- =====================================================
         OVERLAY
    ===================================================== -->

    <div
        class="mobile-menu-overlay"
        id="mobileMenuOverlay"
        onclick="toggleMobileMenu()"
    ></div>


    <!-- =====================================================
         SIDE MENU
    ===================================================== -->

    <nav
        class="mobile-menu"
        id="mobileMenu"
        aria-label="Menu navigasi"
    >


        <!-- BRAND -->

        <div class="side-brand">

            <div>

                <div class="side-brand-title">
                    KAI OPERASIONAL
                </div>


                <div class="side-brand-subtitle">
                    SISTEM DIGITAL PJL
                </div>

            </div>

        </div>


        <!-- DASHBOARD -->

        <a href="/user/dashboard">

            <span class="menu-icon">
                ⌂
            </span>

            Dashboard

        </a>


        <!-- JADWAL KA -->

        <a href="/user/realisasi-ka">

            <span class="menu-icon">
                ▣
            </span>

            Jadwal KA

        </a>


        <!-- SERAH TERIMA -->

        <a href="/user/serah-terima-dinas">

            <span class="menu-icon">
                ⇄
            </span>

            Serah Terima Dinas

        </a>


        <!-- CATATAN DINAS -->

        <a
            href="/user/catatan-dinas"
            class="active"
        >

            <span class="menu-icon">
                ▣
            </span>

            Catatan Dinas

        </a>


        <!-- BOTTOM -->

        <div class="side-bottom">


            <!-- PJL AKTIF -->

            <div class="active-user">

                <div class="active-user-label">
                    PJL AKTIF
                </div>


                <div class="active-user-name">
                    Budi Santoso
                </div>


                <div class="active-user-info">

                    NIP: 199208242015031002

                    <br>

                    Shift B&nbsp; • &nbsp;08:00–16:00

                </div>

            </div>


            <!-- LOGOUT -->

            <a
                href="/login"
                class="menu-logout"
            >

                <span class="menu-icon">
                    ↪
                </span>

                Keluar

            </a>

        </div>

    </nav>


    <!-- =====================================================
         MAIN
    ===================================================== -->

    <main class="main">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <h1 class="page-title">
                Catatan Dinas
            </h1>


            <button
                class="add-button"
                type="button"
                onclick="openAddModal()"
            >
                ⊕ &nbsp; Tambah Catatan
            </button>

        </div>


        <!-- =================================================
             TABLE
        ================================================= -->

        <div class="table-card">

            <table>

                <thead>

                    <tr>

                        <th>
                            No
                        </th>


                        <th>
                            Tanggal
                        </th>


                        <th>
                            PJL
                        </th>


                        <th>
                            Shift
                        </th>


                        <th>
                            Catatan
                        </th>


                        <th>
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody id="catatanTable">


                    <!-- DATA 1 -->

                    <tr>

                        <td>
                            1
                        </td>


                        <td class="date">
                            20 Okt 2024
                        </td>


                        <td class="pjl">
                            Andi Saputra
                        </td>


                        <td class="shift-text">
                            Shift 1 - Pagi
                        </td>


                        <td class="note">
                            Serah terima tugas berjalan lancar, semua peralatan JPL dalam kondisi baik dan berfungsi normal.
                        </td>


                        <td>

                            <button
                                class="view-button"
                                type="button"
                                onclick="viewDetail(
                                    '20 Okt 2024',
                                    'Andi Saputra',
                                    'Shift 1 - Pagi',
                                    'Serah terima tugas berjalan lancar, semua peralatan JPL dalam kondisi baik dan berfungsi normal.'
                                )"
                            >
                                Lihat
                            </button>

                        </td>

                    </tr>


                    <!-- DATA 2 -->

                    <tr>

                        <td>
                            2
                        </td>


                        <td class="date">
                            19 Okt 2024
                        </td>


                        <td class="pjl">
                            Budi Santoso
                        </td>


                        <td class="shift-text">
                            Shift 3 - Malam
                        </td>


                        <td class="note">
                            Ada perbaikan sinyal di dekat JPL oleh tim teknis pukul 23:00, aman terkendali.
                        </td>


                        <td>

                            <button
                                class="view-button"
                                type="button"
                                onclick="viewDetail(
                                    '19 Okt 2024',
                                    'Budi Santoso',
                                    'Shift 3 - Malam',
                                    'Ada perbaikan sinyal di dekat JPL oleh tim teknis pukul 23:00, aman terkendali.'
                                )"
                            >
                                Lihat
                            </button>

                        </td>

                    </tr>


                    <!-- DATA 3 -->

                    <tr>

                        <td>
                            3
                        </td>


                        <td class="date">
                            19 Okt 2024
                        </td>


                        <td class="pjl">
                            Chandra Wijaya
                        </td>


                        <td class="shift-text">
                            Shift 2 - Siang
                        </td>


                        <td class="note">
                            Kondisi lalu lintas jalan raya padat, palang pintu ditutup lebih awal untuk antisipasi.
                        </td>


                        <td>

                            <button
                                class="view-button"
                                type="button"
                                onclick="viewDetail(
                                    '19 Okt 2024',
                                    'Chandra Wijaya',
                                    'Shift 2 - Siang',
                                    'Kondisi lalu lintas jalan raya padat, palang pintu ditutup lebih awal untuk antisipasi.'
                                )"
                            >
                                Lihat
                            </button>

                        </td>

                    </tr>


                    <!-- DATA 4 -->

                    <tr>

                        <td>
                            4
                        </td>


                        <td class="date">
                            18 Okt 2024
                        </td>


                        <td class="pjl">
                            Andi Saputra
                        </td>


                        <td class="shift-text">
                            Shift 1 - Pagi
                        </td>


                        <td class="note">
                            Terjadi pemadaman listrik sementara, genset cadangan JPL berfungsi dengan baik.
                        </td>


                        <td>

                            <button
                                class="view-button"
                                type="button"
                                onclick="viewDetail(
                                    '18 Okt 2024',
                                    'Andi Saputra',
                                    'Shift 1 - Pagi',
                                    'Terjadi pemadaman listrik sementara, genset cadangan JPL berfungsi dengan baik.'
                                )"
                            >
                                Lihat
                            </button>

                        </td>

                    </tr>


                    <!-- DATA 5 -->

                    <tr>

                        <td>
                            5
                        </td>


                        <td class="date">
                            17 Okt 2024
                        </td>


                        <td class="pjl">
                            Budi Santoso
                        </td>


                        <td class="shift-text">
                            Shift 3 - Malam
                        </td>


                        <td class="note">
                            Kondisi cuaca hujan lebat, visibilitas terbatas namun operasional palang pintu lancar.
                        </td>


                        <td>

                            <button
                                class="view-button"
                                type="button"
                                onclick="viewDetail(
                                    '17 Okt 2024',
                                    'Budi Santoso',
                                    'Shift 3 - Malam',
                                    'Kondisi cuaca hujan lebat, visibilitas terbatas namun operasional palang pintu lancar.'
                                )"
                            >
                                Lihat
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>


            <!-- TABLE FOOTER -->

            <div class="table-footer">

                <div class="showing">
                    Menampilkan 5 catatan dinas
                </div>


                <div class="pagination">

                    <button
                        class="page-button"
                        type="button"
                        onclick="changePage('prev')"
                    >
                        ‹
                    </button>


                    <button
                        class="page-button active"
                        type="button"
                        onclick="changePage(1)"
                    >
                        1
                    </button>


                    <button
                        class="page-button"
                        type="button"
                        onclick="changePage(2)"
                    >
                        2
                    </button>


                    <button
                        class="page-button"
                        type="button"
                        onclick="changePage(3)"
                    >
                        3
                    </button>


                    <button
                        class="page-button"
                        type="button"
                        onclick="changePage('next')"
                    >
                        ›
                    </button>

                </div>

            </div>

        </div>

    </main>


    <!-- =====================================================
         MODAL TAMBAH CATATAN
    ===================================================== -->

    <div
        class="modal-overlay"
        id="addModal"
        onclick="closeOutside(event)"
    >

        <div
            class="modal"
            onclick="event.stopPropagation()"
        >

            <div class="modal-header">

                <div class="modal-title">
                    Tambah Catatan Dinas
                </div>


                <button
                    class="close"
                    type="button"
                    aria-label="Tutup"
                    onclick="closeAddModal()"
                >
                    ×
                </button>

            </div>


            <!-- TANGGAL -->

            <div class="form-group">

                <label
                    class="form-label"
                    for="tanggal"
                >
                    Tanggal
                </label>


                <input
                    type="date"
                    class="form-input"
                    id="tanggal"
                >

            </div>


            <!-- PJL -->

            <div class="form-group">

                <label
                    class="form-label"
                    for="pjl"
                >
                    PJL
                </label>


                <input
                    type="text"
                    class="form-input"
                    id="pjl"
                    placeholder="Masukkan nama PJL"
                >

            </div>


            <!-- SHIFT -->

            <div class="form-group">

                <label
                    class="form-label"
                    for="shift"
                >
                    Shift
                </label>


                <select
                    class="form-select"
                    id="shift"
                >

                    <option value="">
                        Pilih Shift
                    </option>


                    <option value="Shift 1 - Pagi">
                        Shift 1 - Pagi
                    </option>


                    <option value="Shift 2 - Siang">
                        Shift 2 - Siang
                    </option>


                    <option value="Shift 3 - Malam">
                        Shift 3 - Malam
                    </option>

                </select>

            </div>


            <!-- CATATAN -->

            <div class="form-group">

                <label
                    class="form-label"
                    for="catatan"
                >
                    Catatan
                </label>


                <textarea
                    class="form-textarea"
                    id="catatan"
                    placeholder="Tuliskan catatan dinas..."
                ></textarea>

            </div>


            <!-- FOOTER -->

            <div class="modal-footer">

                <button
                    class="modal-button cancel"
                    type="button"
                    onclick="closeAddModal()"
                >
                    Batal
                </button>


                <button
                    class="modal-button save"
                    type="button"
                    onclick="saveCatatan()"
                >
                    Simpan
                </button>

            </div>

        </div>

    </div>


    <!-- =====================================================
         MODAL DETAIL
    ===================================================== -->

    <div
        class="modal-overlay"
        id="detailModal"
        onclick="closeDetailOutside(event)"
    >

        <div
            class="modal"
            onclick="event.stopPropagation()"
        >

            <div class="modal-header">

                <div class="modal-title">
                    Detail Catatan Dinas
                </div>


                <button
                    class="close"
                    type="button"
                    aria-label="Tutup"
                    onclick="closeDetailModal()"
                >
                    ×
                </button>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Tanggal
                </div>


                <div
                    class="detail-value"
                    id="detailTanggal"
                ></div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    PJL
                </div>


                <div
                    class="detail-value"
                    id="detailPjl"
                ></div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Shift
                </div>


                <div
                    class="detail-value"
                    id="detailShift"
                ></div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Catatan
                </div>


                <div
                    class="detail-value"
                    id="detailCatatan"
                ></div>

            </div>


            <div class="modal-footer">

                <button
                    class="modal-button cancel"
                    type="button"
                    onclick="closeDetailModal()"
                >
                    Tutup
                </button>

            </div>

        </div>

    </div>


    <!-- =====================================================
         JAVASCRIPT
    ===================================================== -->

    <script>

        /* =====================================================
           SIDE MENU
        ===================================================== */

        function toggleMobileMenu() {

            const menu =
                document.getElementById("mobileMenu");

            const overlay =
                document.getElementById("mobileMenuOverlay");


            menu.classList.toggle("show");

            overlay.classList.toggle("show");

        }


        /* =====================================================
           TAMBAH MODAL
        ===================================================== */

        function openAddModal() {

            document
                .getElementById("addModal")
                .classList.add("show");

        }


        function closeAddModal() {

            document
                .getElementById("addModal")
                .classList.remove("show");

        }


        function closeOutside(event) {

            if (
                event.target.id === "addModal"
            ) {

                closeAddModal();

            }

        }


        /* =====================================================
           SIMPAN CATATAN
        ===================================================== */

        function saveCatatan() {

            const tanggal =
                document
                    .getElementById("tanggal")
                    .value
                    .trim();


            const pjl =
                document
                    .getElementById("pjl")
                    .value
                    .trim();


            const shift =
                document
                    .getElementById("shift")
                    .value
                    .trim();


            const catatan =
                document
                    .getElementById("catatan")
                    .value
                    .trim();


            if (
                !tanggal ||
                !pjl ||
                !shift ||
                !catatan
            ) {

                alert(
                    "Mohon lengkapi semua data catatan dinas."
                );

                return;

            }


            const dateObject =
                new Date(tanggal + "T00:00:00");


            const formattedDate =
                dateObject.toLocaleDateString(
                    "id-ID",
                    {
                        day: "2-digit",
                        month: "short",
                        year: "numeric"
                    }
                );


            const table =
                document.getElementById(
                    "catatanTable"
                );


            const number =
                table.rows.length + 1;


            const row =
                table.insertRow(0);


            const cellNo =
                row.insertCell(0);

            cellNo.textContent = number;


            const cellDate =
                row.insertCell(1);

            cellDate.className = "date";
            cellDate.textContent = formattedDate;


            const cellPjl =
                row.insertCell(2);

            cellPjl.className = "pjl";
            cellPjl.textContent = pjl;


            const cellShift =
                row.insertCell(3);

            cellShift.className = "shift-text";
            cellShift.textContent = shift;


            const cellNote =
                row.insertCell(4);

            cellNote.className = "note";
            cellNote.textContent = catatan;


            const cellAction =
                row.insertCell(5);


            const viewButton =
                document.createElement("button");


            viewButton.type = "button";

            viewButton.className =
                "view-button";

            viewButton.textContent =
                "Lihat";


            viewButton.addEventListener(
                "click",
                function () {

                    viewDetail(
                        formattedDate,
                        pjl,
                        shift,
                        catatan
                    );

                }
            );


            cellAction.appendChild(
                viewButton
            );


            closeAddModal();


            document
                .getElementById("tanggal")
                .value = "";


            document
                .getElementById("pjl")
                .value = "";


            document
                .getElementById("shift")
                .value = "";


            document
                .getElementById("catatan")
                .value = "";


            alert(
                "Catatan dinas berhasil ditambahkan."
            );

        }


        /* =====================================================
           DETAIL
        ===================================================== */

        function viewDetail(
            tanggal,
            pjl,
            shift,
            catatan
        ) {

            document
                .getElementById("detailTanggal")
                .textContent = tanggal;


            document
                .getElementById("detailPjl")
                .textContent = pjl;


            document
                .getElementById("detailShift")
                .textContent = shift;


            document
                .getElementById("detailCatatan")
                .textContent = catatan;


            document
                .getElementById("detailModal")
                .classList.add("show");

        }


        function closeDetailModal() {

            document
                .getElementById("detailModal")
                .classList.remove("show");

        }


        function closeDetailOutside(event) {

            if (
                event.target.id === "detailModal"
            ) {

                closeDetailModal();

            }

        }


        /* =====================================================
           PAGINATION
        ===================================================== */

        function changePage(page) {

            const buttons =
                document.querySelectorAll(
                    ".page-button"
                );


            if (page === "prev") {

                alert(
                    "Halaman sebelumnya."
                );

                return;

            }


            if (page === "next") {

                alert(
                    "Halaman berikutnya."
                );

                return;

            }


            buttons.forEach(
                button => {

                    button.classList.remove(
                        "active"
                    );

                }
            );


            if (
                buttons[page]
            ) {

                buttons[page]
                    .classList
                    .add("active");

            }

        }


        /* =====================================================
           ESC
        ===================================================== */

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key !== "Escape"
                ) {
                    return;
                }


                closeAddModal();

                closeDetailModal();


                document
                    .getElementById("mobileMenu")
                    .classList.remove("show");


                document
                    .getElementById("mobileMenuOverlay")
                    .classList.remove("show");

            }
        );

    </script>

</body>

</html>
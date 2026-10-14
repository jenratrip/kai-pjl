<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Catatan Dinas - Sistem PJL</title>

    <style>
        :root {
            --primary: #1e3156;
            --primary-hover: #29436f;
            --primary-light: #edf3fb;
            --page-bg: #f4f6f9;
            --text-main: #26344d;
            --text-muted: #647084;
            --border: #e5e9f0;
            --white: #ffffff;
            --radius: 10px;
            --header-height: 64px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--page-bg);
            color: var(--text-main);
            overflow-x: hidden;
            font-size: 14px;
            line-height: 1.5;
        }

        body.modal-open {
            overflow: hidden;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        button {
            -webkit-tap-highlight-color: transparent;
        }

        button:focus-visible,
        a:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 2px solid #6098d5;
            outline-offset: 3px;
        }

        /* =========================
           HEADER
        ========================= */

        .header {
            position: sticky;
            top: 0;
            z-index: 900;
            width: 100%;
            height: var(--header-height);
            padding: 0 28px;
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--primary);
            color: var(--white);
            box-shadow: 0 2px 10px rgba(15, 23, 42, .08);
        }

        .menu-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: white;
            font-size: 25px;
            line-height: 1;
            cursor: pointer;
            transition: background .2s ease;
        }

        .menu-button:hover {
            background: rgba(255, 255, 255, .12);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .kai-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 42px;
            height: 32px;
            overflow: hidden;
            border-radius: 5px;
            background: white;
        }

        .kai-logo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-text {
            min-width: 0;
            line-height: 1.3;
        }

        .brand-title {
            overflow: hidden;
            color: white;
            font-size: 14px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .brand-subtitle {
            margin-top: 3px;
            color: #d2dae6;
            font-size: 10px;
            white-space: nowrap;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
        }

        .avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e89a36;
            color: white;
            font-size: 13px;
            font-weight: 700;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .mobile-menu {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1001;
            display: flex;
            flex-direction: column;
            width: 280px;
            max-width: 85vw;
            padding: 0 16px 16px;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: var(--primary);
            box-shadow: 8px 0 25px rgba(0, 0, 0, .18);
            transform: translateX(-105%);
            visibility: hidden;
            transition:
                transform .25s ease,
                visibility .25s ease;
        }

        .mobile-menu.show {
            transform: translateX(0);
            visibility: visible;
        }

        .side-brand {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            height: 84px;
            margin: 0 -16px 16px;
            padding: 0 22px;
            border-bottom: 1px solid rgba(255, 255, 255, .12);
        }

        .side-brand-title {
            color: white;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.3;
        }

        .side-brand-subtitle {
            margin-top: 5px;
            color: #d3dceb;
            font-size: 11px;
        }

        .mobile-menu a {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            min-height: 46px;
            margin-bottom: 6px;
            padding: 0 14px;
            border-radius: 9px;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: background .2s ease;
        }

        .mobile-menu a:hover {
            background: rgba(255, 255, 255, .09);
        }

        .mobile-menu a.active {
            background: #3677aa;
            box-shadow: inset 3px 0 #a9d8ff;
        }

        .menu-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 28px;
            margin-right: 8px;
            color: #e1e8f2;
            font-size: 18px;
        }

        .side-bottom {
            margin-top: auto;
            padding-top: 20px;
        }

        .active-user {
            margin-bottom: 10px;
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 9px;
            background: #304b78;
        }

        .active-user-label {
            margin-bottom: 4px;
            color: #d5ddec;
            font-size: 11px;
        }

        .active-user-name {
            margin-bottom: 5px;
            color: white;
            font-size: 14px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .active-user-info {
            color: #d9e1ed;
            font-size: 11px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        /* LOGOUT */

        .logout-form {
            display: block;
            width: 100%;
            margin: 0;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            width: 100%;
            min-height: 44px;
            margin-bottom: 4px;
            padding: 0 14px;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
            background: transparent;
            color: white;
            font-size: 13px;
            font-weight: 500;
            text-align: left;
            cursor: pointer;
            transition: background .2s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, .1);
        }

        .logout-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 28px;
            font-size: 18px;
        }

        /* OVERLAY SIDEBAR */

        .mobile-menu-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            background: rgba(15, 23, 42, .5);
            backdrop-filter: blur(2px);
        }

        .mobile-menu-overlay.show {
            display: block;
        }

        /* =========================
           KONTEN UTAMA
        ========================= */

        .main {
            width: 100%;
            min-height: calc(100vh - var(--header-height));
            padding: 30px 28px 40px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .page-heading {
            min-width: 0;
        }

        .page-title {
            color: var(--text-main);
            font-size: 24px;
            font-weight: 700;
            line-height: 1.3;
            letter-spacing: -.4px;
        }

        .page-description {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .add-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            gap: 8px;
            min-height: 40px;
            padding: 0 17px;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(30, 49, 86, .12);
            transition:
                background .2s ease,
                transform .2s ease;
        }

        .add-button:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .add-icon {
            font-size: 19px;
            line-height: 1;
        }

        /* =========================
           TABEL CATATAN
        ========================= */

        .table-card {
            width: 100%;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: white;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .04);
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            background: #f8fafc;
        }

        th {
            height: 48px;
            padding: 0 16px;
            border-bottom: 1px solid var(--border);
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .15px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            height: 54px;
            padding: 13px 16px;
            border-bottom: 1px solid #edf0f3;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.6;
            vertical-align: middle;
        }

        tbody tr {
            transition: background .18s ease;
        }

        tbody tr:hover {
            background: #f8faff;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        th:nth-child(1),
        td:nth-child(1) {
            width: 55px;
            text-align: center;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 125px;
        }

        th:nth-child(3),
        td:nth-child(3) {
            width: 165px;
        }

        th:nth-child(4),
        td:nth-child(4) {
            width: 120px;
        }

        th:nth-child(5),
        td:nth-child(5) {
            width: auto;
        }

        th:nth-child(6),
        td:nth-child(6) {
            width: 80px;
            text-align: center;
        }

        .date {
            color: var(--text-main);
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .pjl {
            color: #3d4758;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .shift-text {
            color: var(--text-muted);
            font-size: 12px;
        }

        .note {
            max-width: 0;
            overflow: hidden;
            color: #5b6574;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .view-button {
            padding: 7px 10px;
            border: none;
            border-radius: 6px;
            background: transparent;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s ease;
        }

        .view-button:hover {
            background: #eff6ff;
        }

        .empty-state {
            padding: 36px 20px !important;
            color: var(--text-muted);
            font-size: 13px;
            text-align: center;
        }

        .empty-icon {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-size: 28px;
        }

        /* FOOTER TABEL */

        .table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 58px;
            padding: 12px 18px;
            border-top: 1px solid var(--border);
            background: white;
        }

        .showing {
            color: var(--text-muted);
            font-size: 12px;
        }

        /* =========================
           MODAL
        ========================= */

        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 1100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, .5);
            backdrop-filter: blur(3px);
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal {
            width: 480px;
            max-width: 100%;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 24px;
            border: 1px solid rgba(229, 233, 240, .9);
            border-radius: 14px;
            background: white;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .2);
            animation: modal-appear .18s ease-out;
        }

        @keyframes modal-appear {
            from {
                opacity: 0;
                transform: translateY(8px) scale(.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            color: var(--text-main);
            font-size: 18px;
            font-weight: 700;
        }

        .close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
        }

        .close:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        /* FORM */

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 7px;
            color: #344256;
            font-size: 13px;
            font-weight: 600;
        }

        .form-input,
        .form-select,
        .form-textarea {
            display: block;
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8e0e9;
            border-radius: 8px;
            background: white;
            color: #334155;
            font-size: 13px;
            outline: none;
            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .form-input,
        .form-select {
            height: 42px;
        }

        .form-input[readonly] {
            background: #f8fafc;
            color: #647084;
        }

        .form-textarea {
            min-height: 120px;
            height: 120px;
            resize: vertical;
            line-height: 1.6;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: #557da9;
            box-shadow: 0 0 0 3px rgba(85, 125, 169, .13);
        }

        .form-select option:disabled {
            color: #9ca3af;
        }

        .form-help {
            margin-top: 7px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .form-error {
            margin-bottom: 14px;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #dc2626;
            font-size: 12px;
            line-height: 1.7;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }

        .modal-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 18px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s ease;
        }

        .cancel {
            border-color: #d8dee6;
            background: white;
            color: #475569;
        }

        .cancel:hover {
            background: #f7f8fa;
        }

        .save {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .save:hover {
            background: var(--primary-hover);
        }

        /* DETAIL MODAL */

        .detail-row {
            display: grid;
            grid-template-columns: 105px minmax(0, 1fr);
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid #edf0f3;
        }

        .detail-label {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .detail-value {
            color: #374151;
            font-size: 13px;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        /* =========================
           TABLET
        ========================= */

        @media (max-width: 900px) {
            .header {
                padding: 0 18px;
            }

            .main {
                padding: 24px 18px 32px;
            }

            .page-title {
                font-size: 22px;
            }
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 650px) {
            :root {
                --header-height: 58px;
            }

            .header {
                gap: 8px;
                padding: 0 12px;
            }

            .menu-button {
                width: 36px;
                height: 36px;
                font-size: 23px;
            }

            .brand {
                gap: 7px;
            }

            .kai-logo {
                width: 36px;
                height: 28px;
            }

            .brand-title {
                font-size: 11px;
            }

            .brand-subtitle {
                margin-top: 2px;
                font-size: 8px;
            }

            .header-right {
                gap: 4px;
            }

            .avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .main {
                padding: 22px 12px 28px;
            }

            .page-header {
                align-items: center;
                gap: 10px;
                margin-bottom: 18px;
            }

            .page-title {
                font-size: 20px;
            }

            .page-description {
                font-size: 12px;
            }

            .add-button {
                min-height: 38px;
                gap: 5px;
                padding: 0 11px;
                border-radius: 7px;
                font-size: 11px;
            }

            .add-icon {
                font-size: 17px;
            }

            .table-card {
                border-radius: 10px;
            }

            table {
                min-width: 850px;
            }

            th {
                height: 44px;
                padding: 0 12px;
                font-size: 11px;
            }

            td {
                padding: 13px 12px;
                font-size: 12px;
            }

            .table-footer {
                padding: 12px;
            }

            .showing {
                font-size: 11px;
            }

            .mobile-menu {
                width: 280px;
                max-width: 84vw;
                padding: 0 14px 14px;
            }

            .side-brand {
                height: 84px;
                margin-right: -14px;
                margin-left: -14px;
                padding: 0 20px;
            }

            .mobile-menu a {
                min-height: 48px;
                font-size: 13px;
            }

            .modal-overlay {
                padding: 12px;
            }

            .modal {
                width: 100%;
                max-width: 480px;
                max-height: calc(100dvh - 24px);
                padding: 18px;
                border-radius: 12px;
            }

            .modal-header {
                margin-bottom: 16px;
            }

            .modal-title {
                font-size: 16px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-label {
                font-size: 12px;
            }

            .form-input,
            .form-select {
                height: 42px;
                font-size: 14px;
            }

            .form-textarea {
                min-height: 110px;
                font-size: 14px;
            }

            .modal-button {
                min-height: 40px;
                padding: 0 15px;
                font-size: 12px;
            }

            .detail-row {
                grid-template-columns: 78px minmax(0, 1fr);
                gap: 8px;
            }
        }

        /* HP KECIL */

        @media (max-width: 400px) {
            .header {
                gap: 5px;
                padding: 0 8px;
            }

            .menu-button {
                width: 32px;
                height: 34px;
                font-size: 22px;
            }

            .kai-logo {
                width: 32px;
                height: 26px;
            }

            .brand {
                gap: 6px;
            }

            .brand-title {
                font-size: 10px;
            }

            .brand-subtitle {
                font-size: 7px;
            }

            .main {
                padding: 18px 8px 24px;
            }

            .page-title {
                font-size: 17px;
            }

            .page-description {
                font-size: 11px;
            }

            .add-button {
                min-height: 36px;
                gap: 4px;
                padding: 0 8px;
                font-size: 10px;
            }

            .avatar {
                width: 30px;
                height: 30px;
            }

            .mobile-menu {
                max-width: 84vw;
            }

            .modal {
                padding: 15px;
            }

            .modal-title {
                font-size: 15px;
            }

            .detail-row {
                grid-template-columns: 68px minmax(0, 1fr);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header class="header">
        <button
            class="menu-button"
            id="menuButton"
            type="button"
            aria-label="Buka menu"
            aria-controls="mobileMenu"
            aria-expanded="false"
            onclick="toggleMobileMenu()"
        >
            ☰
        </button>

        <div class="brand">
            <div class="kai-logo">
                <img src="{{ asset('images/logo-kai.jpg') }}" alt="Logo KAI">
            </div>

            <div class="brand-text">
                <div class="brand-title">KAI OPERASIONAL</div>
                <div class="brand-subtitle">SISTEM DIGITAL PJL</div>
            </div>
        </div>

        <div class="header-right">
            <div class="avatar" aria-label="Avatar pengguna">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- OVERLAY SIDEBAR -->
    <div
        class="mobile-menu-overlay"
        id="mobileMenuOverlay"
        onclick="closeMobileMenu()"
        aria-hidden="true"
    ></div>

    <!-- SIDE MENU -->
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

        <a href="{{ url('/user/catatan-dinas') }}" class="active" aria-current="page">
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

            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                @csrf

                <button type="submit" class="logout-btn">
                    <span class="logout-icon">↪</span>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </nav>

    <!-- KONTEN UTAMA -->
    <main class="main">
        <div class="page-header">
            <div class="page-heading">
                <h1 class="page-title">Catatan Dinas</h1>
                <p class="page-description">
                    Kelola dan lihat catatan selama pelaksanaan dinas.
                </p>
            </div>

            <button
                class="add-button"
                type="button"
                onclick="openAddModal()"
            >
                <span class="add-icon">+</span>
                <span>Tambah Catatan</span>
            </button>
        </div>

        <!-- TABEL CATATAN -->
        <section class="table-card" aria-label="Daftar catatan dinas">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>PJL</th>
                            <th>Shift</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="catatanTable">
                        @forelse ($catatan as $item)
                            @php
                                $tanggalMentahCatatan = $item->dinas?->getRawOriginal('tanggal_dinas');

                                $tanggalCatatan = $tanggalMentahCatatan
                                    ? \Carbon\Carbon::parse($tanggalMentahCatatan)->translatedFormat('d M Y')
                                    : '-';

                                $namaPjl = $item->dinas?->user?->name
                                    ?? $item->user?->name
                                    ?? '-';

                                $namaShift = $item->dinas?->shift?->nama_shift ?? '-';
                            @endphp

                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td class="date">
                                    {{ $tanggalCatatan }}
                                </td>

                                <td class="pjl">
                                    {{ $namaPjl }}
                                </td>

                                <td class="shift-text">
                                    {{ $namaShift }}
                                </td>

                                <td class="note" title="{{ $item->isi_catatan }}">
                                    {{ $item->isi_catatan }}
                                </td>

                                <td>
                                    <button
                                        class="view-button"
                                        type="button"
                                        aria-label="Lihat detail catatan"
                                        onclick="viewDetail(
                                            @js($tanggalCatatan),
                                            @js($namaPjl),
                                            @js($namaShift),
                                            @js($item->isi_catatan)
                                        )"
                                    >
                                        Lihat
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <span class="empty-icon">▤</span>
                                    Belum ada catatan dinas.
                                    <br>
                                    Klik tombol Tambah Catatan untuk membuat catatan baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="showing">
                    Menampilkan {{ $catatan->count() }} catatan dinas
                </div>
            </div>
        </section>
    </main>

    <!-- MODAL TAMBAH CATATAN -->
    <div
        class="modal-overlay"
        id="addModal"
        onclick="closeOutside(event)"
        aria-hidden="true"
    >
        <div
            class="modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="addModalTitle"
            onclick="event.stopPropagation()"
        >
            <div class="modal-header">
                <h2 class="modal-title" id="addModalTitle">
                    Tambah Catatan Dinas
                </h2>

                <button
                    class="close"
                    type="button"
                    aria-label="Tutup modal"
                    onclick="closeAddModal()"
                >
                    ×
                </button>
            </div>

            <form
                id="catatanForm"
                action="{{ route('user.catatan-dinas.store') }}"
                method="POST"
            >
                @csrf

                <!-- TANGGAL -->
                <div class="form-group">
                    <label class="form-label" for="tanggal">
                        Tanggal
                    </label>

                    <input
                        type="date"
                        class="form-input"
                        id="tanggal"
                        name="tanggal"
                        value="{{ old('tanggal') }}"
                        required
                        onchange="updateShiftOptions(true)"
                    >
                </div>

                <!-- PJL -->
                <div class="form-group">
                    <label class="form-label" for="pjl">
                        PJL
                    </label>

                    <input
                        type="text"
                        class="form-input"
                        id="pjl"
                        value="{{ auth()->user()->name ?? '' }}"
                        readonly
                    >
                </div>

                <!-- SHIFT -->
                <div class="form-group">
                    <label class="form-label" for="shift">
                        Shift
                    </label>

                    <select
                        class="form-select"
                        id="shift"
                        name="shift_id"
                        required
                    >
                        <option value="">Pilih tanggal terlebih dahulu</option>

                        @foreach ($daftarDinas as $dinas)
                            @php
                                $tanggalMentah = $dinas->getRawOriginal('tanggal_dinas');

                                $tanggalDinas = $tanggalMentah
                                    ? substr(trim((string) $tanggalMentah), 0, 10)
                                    : '';

                                $namaShiftOption = $dinas->shift?->nama_shift;
                            @endphp

                            @if ($dinas->shift && $tanggalDinas !== '')
                                <option
                                    value="{{ $dinas->shift_id }}"
                                    data-tanggal="{{ $tanggalDinas }}"
                                    @selected(
                                        (string) old('shift_id') === (string) $dinas->shift_id
                                        && old('tanggal') === $tanggalDinas
                                    )
                                >
                                    {{ $namaShiftOption }} — {{ $tanggalDinas }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <div class="form-help" id="shiftHelp" aria-live="polite">
                        Pilih tanggal dinas untuk menampilkan shift yang tersedia.
                    </div>
                </div>

                <!-- CATATAN -->
                <div class="form-group">
                    <label class="form-label" for="catatan">
                        Catatan
                    </label>

                    <textarea
                        class="form-textarea"
                        id="catatan"
                        name="catatan"
                        maxlength="5000"
                        required
                        placeholder="Tuliskan catatan selama pelaksanaan dinas..."
                    >{{ old('catatan') }}</textarea>

                    <div class="form-help">
                        Maksimal 5.000 karakter.
                    </div>
                </div>

                <!-- ERROR VALIDASI -->
                @if ($errors->any())
                    <div class="form-error" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <!-- TOMBOL FORM -->
                <div class="modal-footer">
                    <button
                        class="modal-button cancel"
                        type="button"
                        onclick="closeAddModal()"
                    >
                        Batal
                    </button>

                    <button class="modal-button save" type="submit">
                        Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DETAIL CATATAN -->
    <div
        class="modal-overlay"
        id="detailModal"
        onclick="closeDetailOutside(event)"
        aria-hidden="true"
    >
        <div
            class="modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="detailModalTitle"
            onclick="event.stopPropagation()"
        >
            <div class="modal-header">
                <h2 class="modal-title" id="detailModalTitle">
                    Detail Catatan Dinas
                </h2>

                <button
                    class="close"
                    type="button"
                    aria-label="Tutup modal"
                    onclick="closeDetailModal()"
                >
                    ×
                </button>
            </div>

            <div class="detail-row">
                <div class="detail-label">Tanggal</div>
                <div class="detail-value" id="detailTanggal"></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">PJL</div>
                <div class="detail-value" id="detailPjl"></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Shift</div>
                <div class="detail-value" id="detailShift"></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Catatan</div>
                <div class="detail-value" id="detailCatatan"></div>
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

    <!-- JAVASCRIPT -->
    <script>
        let activeModal = null;
        let previousFocusedElement = null;

        /* =========================
           SIDEBAR
        ========================= */

        function toggleMobileMenu() {
            const menu = document.getElementById("mobileMenu");

            if (menu.classList.contains("show")) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        }

        function openMobileMenu() {
            const menu = document.getElementById("mobileMenu");
            const overlay = document.getElementById("mobileMenuOverlay");
            const button = document.getElementById("menuButton");

            menu.classList.add("show");
            overlay.classList.add("show");

            menu.removeAttribute("inert");
            menu.setAttribute("aria-hidden", "false");

            overlay.setAttribute("aria-hidden", "false");
            button.setAttribute("aria-expanded", "true");
            button.setAttribute("aria-label", "Tutup menu");
        }

        function closeMobileMenu() {
            const menu = document.getElementById("mobileMenu");
            const overlay = document.getElementById("mobileMenuOverlay");
            const button = document.getElementById("menuButton");

            menu.classList.remove("show");
            overlay.classList.remove("show");

            menu.setAttribute("inert", "");
            menu.setAttribute("aria-hidden", "true");

            overlay.setAttribute("aria-hidden", "true");
            button.setAttribute("aria-expanded", "false");
            button.setAttribute("aria-label", "Buka menu");
        }

        document.querySelectorAll("#mobileMenu a").forEach(link => {
            link.addEventListener("click", closeMobileMenu);
        });

        /* =========================
           MODAL UMUM
        ========================= */

        function showModal(modalId) {
            const modal = document.getElementById(modalId);

            if (!modal) return;

            if (!activeModal) {
                previousFocusedElement = document.activeElement;
            }

            if (activeModal && activeModal !== modal) {
                activeModal.classList.remove("show");
                activeModal.setAttribute("aria-hidden", "true");
            }

            activeModal = modal;
            modal.classList.add("show");
            modal.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open");

            const firstInput = modal.querySelector(
                "input:not([readonly]), select, textarea, button"
            );

            if (firstInput) {
                setTimeout(() => firstInput.focus(), 50);
            }
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);

            if (!modal) return;

            modal.classList.remove("show");
            modal.setAttribute("aria-hidden", "true");

            if (activeModal === modal) {
                activeModal = null;
                document.body.classList.remove("modal-open");

                if (previousFocusedElement && previousFocusedElement.isConnected) {
                    previousFocusedElement.focus();
                }

                previousFocusedElement = null;
            }
        }

        /* =========================
           TAMBAH CATATAN
        ========================= */

        function openAddModal() {
            updateShiftOptions(false);
            showModal("addModal");

            const tanggal = document.getElementById("tanggal");

            if (tanggal && !tanggal.value) {
                tanggal.focus();
            }
        }

        function closeAddModal() {
            hideModal("addModal");
        }

        function closeOutside(event) {
            if (event.target.id === "addModal") {
                closeAddModal();
            }
        }

        /* =========================
           FILTER SHIFT BERDASARKAN TANGGAL
        ========================= */

        function updateShiftOptions(resetShift = false) {
            const tanggal = document.getElementById("tanggal");
            const shift = document.getElementById("shift");
            const help = document.getElementById("shiftHelp");

            if (!tanggal || !shift || !help || !shift.options.length) {
                return;
            }

            const selectedDate = tanggal.value;
            const previousShift = shift.value;

            let jumlahShiftCocok = 0;
            let shiftMasihSesuai = false;

            Array.from(shift.options).forEach((option, index) => {
                if (index === 0 || option.value === "") {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const cocok =
                    selectedDate !== "" &&
                    option.dataset.tanggal === selectedDate;

                option.hidden = !cocok;
                option.disabled = !cocok;

                if (cocok) {
                    jumlahShiftCocok++;

                    if (option.value === previousShift) {
                        shiftMasihSesuai = true;
                    }
                }
            });

            if (resetShift || !shiftMasihSesuai) {
                shift.value = "";
            }

            const placeholder = shift.options[0];

            if (!selectedDate) {
                placeholder.textContent = "Pilih tanggal terlebih dahulu";

                help.textContent =
                    "Pilih tanggal dinas untuk menampilkan shift yang tersedia.";
            } else if (jumlahShiftCocok === 0) {
                placeholder.textContent = "Shift tidak tersedia";

                help.textContent =
                    "Tidak ada jadwal dinas pada tanggal ini. Periksa jadwal dinas akun PJL kamu.";
            } else {
                placeholder.textContent = "Pilih Shift";

                help.textContent =
                    jumlahShiftCocok +
                    " pilihan jadwal tersedia untuk tanggal ini.";
            }
        }

        /* =========================
           DETAIL CATATAN
        ========================= */

        function viewDetail(tanggal, pjl, shift, catatan) {
            document.getElementById("detailTanggal").textContent = tanggal;
            document.getElementById("detailPjl").textContent = pjl;
            document.getElementById("detailShift").textContent = shift;
            document.getElementById("detailCatatan").textContent = catatan;

            showModal("detailModal");
        }

        function closeDetailModal() {
            hideModal("detailModal");
        }

        function closeDetailOutside(event) {
            if (event.target.id === "detailModal") {
                closeDetailModal();
            }
        }

        /* =========================
           PULIHKAN FORM VALIDASI
        ========================= */

        document.addEventListener("DOMContentLoaded", function () {
            const tanggal = document.getElementById("tanggal");
            const shift = document.getElementById("shift");

            updateShiftOptions(false);

            @if (old('shift_id'))
                const oldShiftId = @js((string) old('shift_id'));
                const oldTanggal = @js((string) old('tanggal'));

                if (tanggal.value === oldTanggal) {
                    const oldOption = Array.from(shift.options).find(option =>
                        option.value === oldShiftId &&
                        option.dataset.tanggal === oldTanggal
                    );

                    if (oldOption) {
                        shift.value = oldShiftId;
                        updateShiftOptions(false);
                    }
                }
            @endif

            @if ($errors->any())
                openAddModal();
            @endif
        });

        /* =========================
           TUTUP DENGAN ESCAPE
        ========================= */

        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape") return;

            if (activeModal) {
                if (activeModal.id === "addModal") {
                    closeAddModal();
                } else if (activeModal.id === "detailModal") {
                    closeDetailModal();
                }

                return;
            }

            closeMobileMenu();
        });
    </script>

</body>

</html>